<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tenants\RelationManagers;

use App\Enums\TenantDocumentStatus;
use App\Enums\TenantDocumentType;
use App\Filament\Actions\Admin\TenantDocuments\ArchiveTenantDocument;
use App\Filament\Actions\Admin\TenantDocuments\RejectTenantDocument;
use App\Filament\Actions\Admin\TenantDocuments\ReplaceTenantDocumentFile;
use App\Filament\Actions\Admin\TenantDocuments\ToggleTenantDocumentVisibility;
use App\Filament\Actions\Admin\TenantDocuments\UpdateTenantDocumentMetadata;
use App\Filament\Actions\Admin\TenantDocuments\UploadTenantDocument;
use App\Filament\Actions\Admin\TenantDocuments\VerifyTenantDocument;
use App\Filament\Resources\Tenants\TenantResource;
use App\Filament\Support\Formatting\LocalizedDateFormatter;
use App\Filament\Support\TenantDocuments\TenantDocumentFile;
use App\Models\AuditLog;
use App\Models\Property;
use App\Models\TenantDocument;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class TenantDocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'tenantDocuments';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return TenantResource::canView($ownerRecord);
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.tenant_documents.panel_title');
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->getAttribute('tenant_documents_count');

        return $count === null ? null : (string) $count;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withAdminDocumentRelations()->latestActivityFirst())
            ->columns($this->columns())
            ->headerActions([
                Action::make('uploadDocument')
                    ->label(__('admin.tenant_documents.actions.upload'))
                    ->authorize(fn (): bool => Gate::forUser(self::currentUser())->allows('create', TenantDocument::class))
                    ->schema($this->formSchema(includeFile: true))
                    ->action(function (array $data, UploadTenantDocument $uploadTenantDocument): void {
                        $tenant = $this->contextTenant();

                        $uploadTenantDocument->handle(self::currentUser(), [
                            ...$this->scopedData($data),
                            'organization_id' => $tenant->organization_id,
                            'tenant_id' => $tenant->id,
                        ], $data);

                        Notification::make()
                            ->success()
                            ->title(__('admin.tenant_documents.messages.uploaded'))
                            ->send();
                    }),
            ])
            ->recordActions($this->recordActions())
            ->defaultSort('updated_at', 'desc');
    }

    /**
     * @return array<int, TextColumn>
     */
    private function columns(): array
    {
        return [
            TextColumn::make('title')
                ->label(__('admin.tenant_documents.columns.title'))
                ->searchable()
                ->sortable(),
            TextColumn::make('document_type')
                ->label(__('admin.tenant_documents.columns.type'))
                ->badge()
                ->sortable(),
            TextColumn::make('status')
                ->label(__('admin.tenant_documents.columns.status'))
                ->badge()
                ->sortable(),
            TextColumn::make('tenant_visible')
                ->label(__('admin.tenant_documents.columns.tenant_visible'))
                ->state(fn (TenantDocument $record): string => $record->tenant_visible
                    ? __('admin.tenant_documents.visibility.visible')
                    : __('admin.tenant_documents.visibility.hidden')),
            TextColumn::make('property.name')
                ->label(__('admin.tenant_documents.columns.property'))
                ->state(fn (TenantDocument $record): string => $record->property?->tenantAssignmentLabel() ?? '—'),
            TextColumn::make('expires_at')
                ->label(__('admin.tenant_documents.columns.expires_at'))
                ->state(fn (TenantDocument $record): string => $record->expires_at?->locale(app()->getLocale())->translatedFormat(LocalizedDateFormatter::dateFormat()) ?? '—')
                ->sortable(),
            TextColumn::make('updated_at')
                ->label(__('admin.tenant_documents.columns.updated_at'))
                ->state(fn (TenantDocument $record): string => $record->updated_at?->locale(app()->getLocale())->translatedFormat(LocalizedDateFormatter::dateTimeFormat()) ?? '—')
                ->sortable(),
        ];
    }

    /**
     * @return array<int, Action>
     */
    private function recordActions(): array
    {
        return [
            Action::make('editMetadata')
                ->label(__('admin.tenant_documents.actions.edit_metadata'))
                ->authorize(fn (TenantDocument $record): bool => self::allows('update', $record))
                ->fillForm(fn (TenantDocument $record): array => $this->formData($record))
                ->schema($this->formSchema())
                ->action(function (TenantDocument $record, array $data, UpdateTenantDocumentMetadata $updateTenantDocumentMetadata): void {
                    $updateTenantDocumentMetadata->handle($record, self::currentUser(), $this->scopedData($data));

                    Notification::make()
                        ->success()
                        ->title(__('admin.tenant_documents.messages.updated'))
                        ->send();
                }),
            Action::make('replaceFile')
                ->label(__('admin.tenant_documents.actions.replace_file'))
                ->authorize(fn (TenantDocument $record): bool => self::allows('replace', $record))
                ->schema($this->fileSchema())
                ->action(function (TenantDocument $record, array $data, ReplaceTenantDocumentFile $replaceTenantDocumentFile): void {
                    $replaceTenantDocumentFile->handle($record, self::currentUser(), $data);

                    Notification::make()
                        ->success()
                        ->title(__('admin.tenant_documents.messages.replaced'))
                        ->send();
                }),
            Action::make('toggleVisibility')
                ->label(fn (TenantDocument $record): string => $record->tenant_visible
                    ? __('admin.tenant_documents.actions.mark_hidden')
                    : __('admin.tenant_documents.actions.mark_visible'))
                ->authorize(fn (TenantDocument $record): bool => self::allows('update', $record))
                ->action(function (TenantDocument $record, ToggleTenantDocumentVisibility $toggleTenantDocumentVisibility): void {
                    $toggleTenantDocumentVisibility->handle($record, self::currentUser(), ! $record->tenant_visible);

                    Notification::make()
                        ->success()
                        ->title(__('admin.tenant_documents.messages.visibility_updated'))
                        ->send();
                }),
            Action::make('verify')
                ->label(__('admin.tenant_documents.actions.verify'))
                ->authorize(fn (TenantDocument $record): bool => self::allows('verify', $record))
                ->visible(fn (TenantDocument $record): bool => $record->isKycDocument() && $record->status !== TenantDocumentStatus::VERIFIED)
                ->action(function (TenantDocument $record, VerifyTenantDocument $verifyTenantDocument): void {
                    $verifyTenantDocument->handle($record, self::currentUser());

                    Notification::make()
                        ->success()
                        ->title(__('admin.tenant_documents.messages.verified'))
                        ->send();
                }),
            Action::make('reject')
                ->label(__('admin.tenant_documents.actions.reject'))
                ->authorize(fn (TenantDocument $record): bool => self::allows('reject', $record))
                ->visible(fn (TenantDocument $record): bool => $record->isKycDocument())
                ->schema([
                    Textarea::make('rejection_reason')
                        ->label(__('admin.tenant_documents.fields.rejection_reason'))
                        ->required()
                        ->rows(4),
                ])
                ->action(function (TenantDocument $record, array $data, RejectTenantDocument $rejectTenantDocument): void {
                    $rejectTenantDocument->handle($record, self::currentUser(), (string) ($data['rejection_reason'] ?? ''));

                    Notification::make()
                        ->success()
                        ->title(__('admin.tenant_documents.messages.rejected'))
                        ->send();
                }),
            Action::make('archive')
                ->label(__('admin.tenant_documents.actions.archive'))
                ->authorize(fn (TenantDocument $record): bool => self::allows('archive', $record))
                ->requiresConfirmation()
                ->modalHeading(__('admin.tenant_documents.actions.archive'))
                ->modalDescription(__('admin.tenant_documents.messages.archive_confirmation'))
                ->action(function (TenantDocument $record, ArchiveTenantDocument $archiveTenantDocument): void {
                    $archiveTenantDocument->handle($record, self::currentUser());

                    Notification::make()
                        ->success()
                        ->title(__('admin.tenant_documents.messages.archived'))
                        ->send();
                }),
            Action::make('download')
                ->label(__('admin.tenant_documents.actions.download'))
                ->authorize(fn (TenantDocument $record): bool => self::allows('download', $record))
                ->url(fn (TenantDocument $record): string => route('tenant.documents.download', $record)),
            Action::make('history')
                ->label(__('admin.tenant_documents.actions.history'))
                ->modalHeading(__('admin.tenant_documents.history.title'))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel(__('admin.actions.close'))
                ->modalContent(fn (TenantDocument $record) => view('filament.resources.tenants.relation-managers.tenant-document-history', [
                    'events' => AuditLog::query()
                        ->forSubject($record)
                        ->withActorSummary()
                        ->recent()
                        ->limit(10)
                        ->get(),
                ])),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function formSchema(bool $includeFile = false): array
    {
        return [
            ...($includeFile ? $this->fileSchema() : []),
            Select::make('document_type')
                ->label(__('admin.tenant_documents.fields.document_type'))
                ->options(TenantDocumentType::options())
                ->required(),
            TextInput::make('title')
                ->label(__('admin.tenant_documents.fields.title'))
                ->required()
                ->maxLength(255),
            Textarea::make('description_for_tenant')
                ->label(__('admin.tenant_documents.fields.description_for_tenant'))
                ->rows(3),
            Textarea::make('internal_note')
                ->label(__('admin.tenant_documents.fields.internal_note'))
                ->rows(3),
            Select::make('status')
                ->label(__('admin.tenant_documents.fields.status'))
                ->options(TenantDocumentStatus::options())
                ->default(TenantDocumentStatus::ACTIVE->value)
                ->required(),
            Toggle::make('tenant_visible')
                ->label(__('admin.tenant_documents.fields.tenant_visible'))
                ->default(false),
            Select::make('property_id')
                ->label(__('admin.tenant_documents.fields.property'))
                ->options(fn (): array => $this->propertyOptions())
                ->searchable(),
            DatePicker::make('expires_at')
                ->label(__('admin.tenant_documents.fields.expires_at')),
        ];
    }

    /**
     * @return array<int, FileUpload>
     */
    private function fileSchema(): array
    {
        $tenant = $this->contextTenant();

        return [
            FileUpload::make(TenantDocumentFile::FIELD)
                ->label(__('admin.tenant_documents.fields.file'))
                ->disk(TenantDocumentFile::DISK)
                ->directory(TenantDocumentFile::directory((int) $tenant->organization_id, (int) $tenant->id))
                ->visibility('private')
                ->acceptedFileTypes(TenantDocumentFile::acceptedFileTypes())
                ->maxSize(TenantDocumentFile::MAX_SIZE_KB)
                ->openable()
                ->downloadable()
                ->storeFileNamesIn(TenantDocumentFile::fileNamesStatePath())
                ->required(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(TenantDocument $record): array
    {
        return [
            'tenant_id' => $record->tenant_id,
            'property_id' => $record->property_id,
            'related_type' => $record->related_type,
            'related_id' => $record->related_id,
            'document_type' => $record->document_type?->value,
            'title' => $record->title,
            'description_for_tenant' => $record->description_for_tenant,
            'internal_note' => $record->internal_note,
            'status' => $record->status?->value,
            'tenant_visible' => $record->tenant_visible,
            'expires_at' => $record->expires_at?->toDateString(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function scopedData(array $data): array
    {
        $tenant = $this->contextTenant();

        return [
            ...$data,
            'organization_id' => (int) $tenant->organization_id,
            'tenant_id' => (int) $tenant->id,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function propertyOptions(): array
    {
        $tenant = $this->contextTenant();

        return Property::query()
            ->select(['id', 'organization_id', 'building_id', 'name', 'unit_number', 'type'])
            ->with(['building:id,organization_id,name,address_line_1,city'])
            ->forOrganization((int) $tenant->organization_id)
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Property $property): array => [
                $property->id => $property->tenantAssignmentLabel(),
            ])
            ->all();
    }

    private function contextTenant(): User
    {
        $owner = $this->getOwnerRecord();

        abort_unless($owner instanceof User, 404);

        return $owner;
    }

    private static function currentUser(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }

    private static function allows(string $ability, mixed $subject): bool
    {
        return Gate::forUser(self::currentUser())->allows($ability, $subject);
    }
}
