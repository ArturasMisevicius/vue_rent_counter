<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\LeadSourceType;
use App\Filament\Actions\Admin\Leads\ImportLeadCsv;
use App\Filament\Actions\Admin\Leads\ValidateLeadCsv;
use App\Filament\Support\Admin\Leads\LeadCsvMappingPreset;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\LeadImportBatch;
use App\Models\LeadSource;
use App\Models\Organization;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;

class LeadImport extends Page
{
    protected static ?string $slug = 'leads/import';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected string $view = 'filament.pages.lead-import';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $preview = null;

    public ?int $pendingSourceId = null;

    /**
     * @var array{name?: string|null, privacy_note?: string|null, retention_days?: int|null}
     */
    public array $pendingSource = [];

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav_groups.leads');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.leads.import.navigation');
    }

    public function getTitle(): string
    {
        return __('admin.leads.import.title');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('create', LeadImportBatch::class) ?? false;
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('previewCsv')
                ->label(__('admin.leads.actions.preview_csv'))
                ->schema($this->previewSchema())
                ->action(function (array $data, ValidateLeadCsv $validateLeadCsv): void {
                    $path = Storage::disk('local')->path((string) $data['csv']);
                    $this->pendingSourceId = filled($data['lead_source_id'] ?? null) ? (int) $data['lead_source_id'] : null;
                    $this->pendingSource = [
                        'name' => $data['source_name'] ?? null,
                        'privacy_note' => $data['privacy_note'] ?? null,
                        'retention_days' => filled($data['retention_days'] ?? null) ? (int) $data['retention_days'] : null,
                    ];
                    $this->preview = $validateLeadCsv->handle(
                        $this->user(),
                        $this->organization(),
                        $path,
                        is_array($data['mapping'] ?? null) ? $data['mapping'] : [],
                    );

                    Notification::make()
                        ->success()
                        ->title(__('admin.leads.import.preview_ready'))
                        ->send();
                }),
            Action::make('confirmImport')
                ->label(__('admin.leads.actions.import_valid_rows'))
                ->visible(fn (): bool => $this->preview !== null)
                ->requiresConfirmation()
                ->schema([
                    Select::make('duplicate_strategy')
                        ->label(__('admin.leads.fields.duplicate_strategy'))
                        ->options([
                            'flag' => __('admin.leads.import.duplicate_strategy.flag'),
                            'skip' => __('admin.leads.import.duplicate_strategy.skip'),
                        ])
                        ->default('flag')
                        ->required(),
                ])
                ->action(function (array $data, ImportLeadCsv $importLeadCsv): void {
                    if ($this->preview === null) {
                        return;
                    }

                    $batch = $importLeadCsv->handle(
                        $this->user(),
                        $this->organization(),
                        $this->leadSource(),
                        $this->preview,
                        (string) ($data['duplicate_strategy'] ?? 'flag'),
                    );

                    $this->preview = null;

                    Notification::make()
                        ->success()
                        ->title(__('admin.leads.import.imported', ['count' => $batch->rows_imported]))
                        ->send();
                }),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function previewSchema(): array
    {
        return [
            FileUpload::make('csv')
                ->label(__('admin.leads.import.fields.csv'))
                ->disk('local')
                ->directory('imports/leads')
                ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                ->required(),
            Select::make('lead_source_id')
                ->label(__('admin.leads.fields.lead_source'))
                ->options(fn (): array => $this->sourceOptions())
                ->searchable()
                ->preload(),
            TextInput::make('source_name')
                ->label(__('admin.lead_sources.fields.name'))
                ->default('Aruodas CSV')
                ->maxLength(255),
            TextInput::make('retention_days')
                ->label(__('admin.lead_sources.fields.retention_days'))
                ->integer()
                ->default(180),
            Textarea::make('privacy_note')
                ->label(__('admin.lead_sources.fields.privacy_note'))
                ->rows(3)
                ->default(__('admin.leads.import.default_privacy_note'))
                ->required()
                ->columnSpanFull(),
            ...$this->mappingFields(),
        ];
    }

    /**
     * @return array<int, TextInput>
     */
    private function mappingFields(): array
    {
        return collect(LeadCsvMappingPreset::aruodasDefault())
            ->map(fn (string $defaultHeader, string $field): TextInput => TextInput::make("mapping.{$field}")
                ->label(__('admin.leads.fields.'.$field))
                ->default($defaultHeader)
                ->maxLength(255))
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function sourceOptions(): array
    {
        return LeadSource::query()
            ->select(['id', 'organization_id', 'name'])
            ->forOrganization((int) $this->organization()->id)
            ->ordered()
            ->pluck('name', 'id')
            ->all();
    }

    private function leadSource(): LeadSource
    {
        if ($this->pendingSourceId !== null) {
            return LeadSource::query()
                ->select(['id', 'organization_id', 'name', 'type', 'description', 'source_url', 'privacy_note', 'retention_days', 'created_by_user_id', 'imported_at', 'created_at', 'updated_at'])
                ->forOrganization((int) $this->organization()->id)
                ->findOrFail($this->pendingSourceId);
        }

        return LeadSource::query()->create([
            'organization_id' => $this->organization()->id,
            'name' => $this->pendingSource['name'] ?: 'Aruodas CSV',
            'type' => LeadSourceType::ARUODAS_CSV,
            'privacy_note' => $this->pendingSource['privacy_note'] ?? __('admin.leads.import.default_privacy_note'),
            'retention_days' => $this->pendingSource['retention_days'] ?? 180,
            'created_by_user_id' => $this->user()->id,
        ]);
    }

    private function organization(): Organization
    {
        $organization = app(OrganizationContext::class)->currentOrganization();

        abort_unless($organization instanceof Organization, 403);

        return $organization;
    }

    private function user(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }
}
