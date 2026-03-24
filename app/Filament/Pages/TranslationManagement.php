<?php

namespace App\Filament\Pages;

use App\Filament\Actions\Superadmin\Translations\ExportMissingTranslationsAction;
use App\Filament\Actions\Superadmin\Translations\ImportTranslationsAction;
use App\Filament\Actions\Superadmin\Translations\UpdateTranslationValueAction;
use App\Filament\Support\Superadmin\Translations\TranslationCatalogService;
use App\Filament\Support\Superadmin\Translations\TranslationRowData;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class TranslationManagement extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'translation-management';

    protected string $view = 'filament.pages.translation-management';

    /**
     * @var array<string, mixed>
     */
    public array $draftValues = [];

    public function getTitle(): string
    {
        return __('superadmin.translation_management.title');
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $this->synchronizeDraftValues(app(TranslationCatalogService::class)->rows());
    }

    protected function getViewData(): array
    {
        $service = app(TranslationCatalogService::class);
        $rows = $service->rows();

        $this->synchronizeDraftValues($rows);

        return [
            'rows' => $rows,
            'locales' => $service->locales(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportMissingTranslations')
                ->label(__('superadmin.translation_management.actions.export_missing_csv'))
                ->action(function (ExportMissingTranslationsAction $exportMissingTranslationsAction) {
                    $path = $exportMissingTranslationsAction->handle(app(TranslationCatalogService::class));

                    return response()->download($path, basename($path), [
                        'Content-Type' => 'text/csv',
                    ])->deleteFileAfterSend(true);
                }),
            Action::make('importTranslations')
                ->label(__('superadmin.translation_management.actions.import_csv'))
                ->schema([
                    FileUpload::make('csv')
                        ->label(__('superadmin.translation_management.fields.translation_csv'))
                        ->disk('local')
                        ->directory('imports/translations')
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                        ->required(),
                ])
                ->action(function (array $data, ImportTranslationsAction $importTranslationsAction): void {
                    $path = Storage::disk('local')->path((string) $data['csv']);

                    $importTranslationsAction->handle(app(TranslationCatalogService::class), $path);

                    $this->synchronizeDraftValues(app(TranslationCatalogService::class)->rows(), reset: true);

                    Notification::make()
                        ->title(__('superadmin.translation_management.messages.imported'))
                        ->success()
                        ->send();
                }),
        ];
    }

    public function saveValue(string $group, string $key, string $locale): void
    {
        abort_unless(static::canAccess(), 403);

        $value = (string) data_get($this->draftValues, "{$group}.{$key}.{$locale}", '');

        app(UpdateTranslationValueAction::class)->handle(
            app(TranslationCatalogService::class),
            $group,
            $key,
            $locale,
            $value,
        );
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }

    /**
     * @param  Collection<int, TranslationRowData>  $rows
     */
    private function synchronizeDraftValues(Collection $rows, bool $reset = false): void
    {
        if ($reset) {
            $this->draftValues = [];
        }

        foreach ($rows as $row) {
            foreach ($row->values as $locale => $value) {
                $path = "{$row->group}.{$row->key}.{$locale}";

                if (! $reset && data_get($this->draftValues, $path) !== null) {
                    continue;
                }

                data_set($this->draftValues, $path, $value ?? '');
            }
        }
    }
}
