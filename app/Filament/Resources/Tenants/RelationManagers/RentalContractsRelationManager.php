<?php

namespace App\Filament\Resources\Tenants\RelationManagers;

use App\Enums\RentalContractStatus;
use App\Filament\Actions\Admin\RentalContracts\CreateRentalContractAction;
use App\Filament\Actions\Admin\RentalContracts\RenewRentalContractAction;
use App\Filament\Actions\Admin\RentalContracts\TerminateRentalContractAction;
use App\Filament\Actions\Admin\RentalContracts\UpdateRentalContractAction;
use App\Filament\Actions\Admin\RentalContracts\UploadRentalContractFileAction;
use App\Filament\Resources\Tenants\TenantResource;
use App\Filament\Support\Formatting\EuMoneyFormatter;
use App\Filament\Support\Formatting\LocalizedDateFormatter;
use App\Filament\Support\RentalContracts\RentalContractFile;
use App\Filament\Support\RentalContracts\RentalContractFormSchema;
use App\Models\Attachment;
use App\Models\Property;
use App\Models\RentalContract;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class RentalContractsRelationManager extends RelationManager
{
    protected static string $relationship = 'rentalContracts';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return TenantResource::canView($ownerRecord);
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.tenants.tabs.rental_contracts');
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->getAttribute('rental_contracts_count');

        return (string) ($count ?? $ownerRecord->rentalContracts()->count());
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withContractSummary()->latest('start_date'))
            ->columns($this->columns())
            ->headerActions([
                Action::make('addContract')
                    ->label(__('admin.rental_contracts.actions.add_contract'))
                    ->authorize(fn (): bool => self::allows('create', RentalContract::class))
                    ->schema(RentalContractFormSchema::contract($this->contextTenant(), $this->contextProperty()))
                    ->action(function (array $data, CreateRentalContractAction $createRentalContract): void {
                        $createRentalContract->handle(self::currentUser(), $this->scopedData($data));

                        Notification::make()
                            ->success()
                            ->title(__('admin.rental_contracts.messages.created'))
                            ->send();
                    }),
            ])
            ->recordActions($this->recordActions())
            ->defaultSort('start_date', 'desc');
    }

    /**
     * @return array<int, TextColumn>
     */
    private function columns(): array
    {
        return [
            TextColumn::make('contract_number')
                ->label(__('admin.rental_contracts.columns.contract_number'))
                ->searchable()
                ->sortable(),
            TextColumn::make('property.name')
                ->label(__('admin.rental_contracts.columns.property'))
                ->state(fn (RentalContract $record): string => $record->property?->tenantAssignmentLabel() ?? '—'),
            TextColumn::make('status')
                ->label(__('admin.rental_contracts.columns.status'))
                ->badge(),
            TextColumn::make('period')
                ->label(__('admin.rental_contracts.columns.period'))
                ->state(fn (RentalContract $record): string => collect([
                    $record->start_date?->locale(app()->getLocale())->translatedFormat(LocalizedDateFormatter::dateFormat()),
                    $record->end_date?->locale(app()->getLocale())->translatedFormat(LocalizedDateFormatter::dateFormat()),
                ])->filter()->implode(' - ')),
            TextColumn::make('rent_amount')
                ->label(__('admin.rental_contracts.columns.rent_amount'))
                ->state(fn (RentalContract $record): string => $record->rent_amount === null
                    ? '—'
                    : EuMoneyFormatter::format((float) $record->rent_amount, $record->currency)),
            TextColumn::make('tenant_visible')
                ->label(__('admin.rental_contracts.columns.tenant_visible'))
                ->state(fn (RentalContract $record): string => $record->tenant_visible
                    ? __('admin.rental_contracts.visibility.visible')
                    : __('admin.rental_contracts.visibility.hidden')),
        ];
    }

    /**
     * @return array<int, Action>
     */
    private function recordActions(): array
    {
        return [
            Action::make('editContract')
                ->label(__('admin.rental_contracts.actions.edit_contract'))
                ->authorize(fn (RentalContract $record): bool => self::allows('update', $record))
                ->fillForm(fn (RentalContract $record): array => $this->formData($record))
                ->schema(RentalContractFormSchema::contract($this->contextTenant(), $this->contextProperty()))
                ->action(function (RentalContract $record, array $data, UpdateRentalContractAction $updateRentalContract): void {
                    $updateRentalContract->handle($record, self::currentUser(), $this->scopedData($data));

                    Notification::make()
                        ->success()
                        ->title(__('admin.rental_contracts.messages.updated'))
                        ->send();
                }),
            Action::make('uploadFile')
                ->label(__('admin.rental_contracts.actions.upload_file'))
                ->authorize(fn (RentalContract $record): bool => self::allows('upload', $record))
                ->schema([
                    FileUpload::make(RentalContractFile::FIELD)
                        ->label(__('admin.rental_contracts.fields.file'))
                        ->disk(RentalContractFile::DISK)
                        ->directory(RentalContractFile::DIRECTORY)
                        ->visibility('private')
                        ->acceptedFileTypes(RentalContractFile::acceptedFileTypes())
                        ->maxSize(RentalContractFile::MAX_SIZE_KB)
                        ->openable()
                        ->downloadable()
                        ->storeFileNamesIn(RentalContractFile::fileNamesStatePath()),
                ])
                ->action(function (RentalContract $record, array $data, UploadRentalContractFileAction $uploadRentalContractFile): void {
                    $uploadRentalContractFile->handle($record, self::currentUser(), $data);

                    Notification::make()
                        ->success()
                        ->title(__('admin.rental_contracts.messages.file_uploaded'))
                        ->send();
                }),
            Action::make('downloadFile')
                ->label(__('admin.rental_contracts.actions.download_file'))
                ->authorize(fn (RentalContract $record): bool => self::allows('download', $record))
                ->visible(fn (RentalContract $record): bool => $record->file instanceof Attachment)
                ->url(fn (RentalContract $record): string => route('tenant.rental-contracts.download', [$record, $record->file])),
            Action::make('toggleTenantVisible')
                ->label(fn (RentalContract $record): string => $record->tenant_visible
                    ? __('admin.rental_contracts.actions.mark_hidden')
                    : __('admin.rental_contracts.actions.mark_visible'))
                ->authorize(fn (RentalContract $record): bool => self::allows('update', $record))
                ->action(function (RentalContract $record, UpdateRentalContractAction $updateRentalContract): void {
                    $updateRentalContract->handle($record, self::currentUser(), [
                        'tenant_visible' => ! $record->tenant_visible,
                    ]);

                    Notification::make()
                        ->success()
                        ->title(__('admin.rental_contracts.messages.visibility_updated'))
                        ->send();
                }),
            Action::make('terminateContract')
                ->label(__('admin.rental_contracts.actions.terminate_contract'))
                ->authorize(fn (RentalContract $record): bool => self::allows('terminate', $record))
                ->visible(fn (RentalContract $record): bool => $record->canBeTerminated())
                ->requiresConfirmation()
                ->modalHeading(__('admin.rental_contracts.actions.terminate_contract'))
                ->schema([
                    Textarea::make('termination_reason')
                        ->label(__('admin.rental_contracts.fields.termination_reason'))
                        ->required()
                        ->rows(4),
                ])
                ->action(function (RentalContract $record, array $data, TerminateRentalContractAction $terminateRentalContract): void {
                    $terminateRentalContract->handle($record, self::currentUser(), (string) ($data['termination_reason'] ?? ''));

                    Notification::make()
                        ->success()
                        ->title(__('admin.rental_contracts.messages.terminated'))
                        ->send();
                }),
            Action::make('renewContract')
                ->label(__('admin.rental_contracts.actions.renew_contract'))
                ->authorize(fn (RentalContract $record): bool => self::allows('renew', $record))
                ->visible(fn (RentalContract $record): bool => $record->canBeRenewed())
                ->fillForm(fn (RentalContract $record): array => [
                    ...$this->formData($record),
                    'contract_number' => null,
                    'start_date' => $record->end_date?->copy()->addDay()?->toDateString(),
                    'end_date' => $record->end_date?->copy()->addYear()?->toDateString(),
                    'status' => RentalContractStatus::ACTIVE->value,
                ])
                ->schema(RentalContractFormSchema::contract($this->contextTenant(), $this->contextProperty()))
                ->action(function (RentalContract $record, array $data, RenewRentalContractAction $renewRentalContract): void {
                    $renewRentalContract->handle($record, self::currentUser(), $this->scopedData($data));

                    Notification::make()
                        ->success()
                        ->title(__('admin.rental_contracts.messages.renewed'))
                        ->send();
                }),
            Action::make('archiveContract')
                ->label(__('admin.rental_contracts.actions.archive_contract'))
                ->authorize(fn (RentalContract $record): bool => self::allows('update', $record))
                ->requiresConfirmation()
                ->action(function (RentalContract $record, UpdateRentalContractAction $updateRentalContract): void {
                    $updateRentalContract->handle($record, self::currentUser(), [
                        'status' => RentalContractStatus::CANCELLED->value,
                        'tenant_visible' => false,
                    ]);

                    Notification::make()
                        ->success()
                        ->title(__('admin.rental_contracts.messages.archived'))
                        ->send();
                }),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(RentalContract $record): array
    {
        return [
            'tenant_id' => $record->tenant_id,
            'property_id' => $record->property_id,
            'contract_number' => $record->contract_number,
            'status' => $record->status?->value,
            'start_date' => $record->start_date?->toDateString(),
            'end_date' => $record->end_date?->toDateString(),
            'signed_date' => $record->signed_date?->toDateString(),
            'rent_amount' => $record->rent_amount,
            'deposit_amount' => $record->deposit_amount,
            'currency' => $record->currency,
            'tenant_visible' => $record->tenant_visible,
            'internal_notes' => $record->internal_notes,
            'tenant_visible_notes' => $record->tenant_visible_notes,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function scopedData(array $data): array
    {
        $tenant = $this->contextTenant();
        $property = $this->contextProperty();

        if ($tenant instanceof User) {
            $data['tenant_id'] = $tenant->id;
        }

        if ($property instanceof Property) {
            $data['property_id'] = $property->id;
        }

        return $data;
    }

    protected function contextTenant(): ?User
    {
        $owner = $this->getOwnerRecord();

        return $owner instanceof User ? $owner : null;
    }

    protected function contextProperty(): ?Property
    {
        $owner = $this->getOwnerRecord();

        return $owner instanceof Property ? $owner : null;
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
