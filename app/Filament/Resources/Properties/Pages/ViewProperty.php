<?php

namespace App\Filament\Resources\Properties\Pages;

use App\Enums\PortalAccessAfterMoveOut;
use App\Enums\PropertyAssignmentStatus;
use App\Filament\Actions\Admin\Properties\AssignTenantToPropertyAction;
use App\Filament\Actions\Admin\Properties\DeletePropertyAction;
use App\Filament\Actions\Admin\Properties\UnassignTenantFromPropertyAction;
use App\Filament\Actions\Admin\TenantMoveOut\CompleteTenantMoveOut;
use App\Filament\Actions\Admin\TenantMoveOut\GenerateFinalInvoice;
use App\Filament\Actions\Admin\TenantMoveOut\ScheduleTenantMoveOut;
use App\Filament\Resources\Pages\Concerns\HasDeferredRelationManagerTabBadges;
use App\Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\Properties\PropertyResource;
use App\Filament\Resources\Tenants\TenantResource;
use App\Models\MoveOutProcess;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;

class ViewProperty extends ViewRecord
{
    use HasDeferredRelationManagerTabBadges;

    protected static string $resource = PropertyResource::class;

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    public function getBreadcrumbs(): array
    {
        return [
            PropertyResource::getUrl('index') => PropertyResource::getPluralModelLabel(),
            $this->record->displayName(),
        ];
    }

    public function getTitle(): string
    {
        return $this->record->displayName();
    }

    public function getSubheading(): ?string
    {
        $buildingName = $this->record->building?->displayName();
        $buildingAddress = $this->record->building?->address;

        return collect([$buildingName, $buildingAddress])->filter()->implode(' · ');
    }

    public function getContentTabLabel(): ?string
    {
        return __('admin.properties.tabs.tenant');
    }

    protected function getHeaderActions(): array
    {
        return [
            ...(
                PropertyResource::canEdit($this->record)
                    ? [
                        EditAction::make()
                            ->label(__('admin.actions.edit')),
                    ]
                    : []
            ),
            ...(
                PropertyResource::canEdit($this->record)
                    ? [
                        Action::make('createTenantForProperty')
                            ->label(__('admin.properties.actions.create_tenant_for_property'))
                            ->icon('heroicon-m-user-plus')
                            ->url(fn (): string => TenantResource::getUrl('create', [
                                'property_id' => $this->record->id,
                            ]))
                            ->visible(fn (): bool => $this->record->currentAssignment === null && TenantResource::canCreate())
                            ->button(),
                        Action::make('assignTenant')
                            ->label($this->record->currentAssignment === null
                                ? __('admin.properties.actions.assign_tenant')
                                : __('admin.properties.actions.reassign_tenant'))
                            ->slideOver()
                            ->modalDescription($this->record->currentAssignment === null
                                ? null
                                : __('admin.properties.messages.reassign_tenant_warning', [
                                    'tenant' => $this->record->currentAssignment->tenant?->name ?? __('admin.properties.empty.vacant'),
                                ]))
                            ->schema([
                                Select::make('tenant_id')
                                    ->label(__('admin.properties.fields.tenant'))
                                    ->options(fn (): array => $this->availableTenantOptions())
                                    ->searchable()
                                    ->required(),
                                TextInput::make('unit_area_sqm')
                                    ->label(__('admin.tenants.fields.unit_area_sqm'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(fn (): ?float => $this->record->currentAssignment?->unit_area_sqm !== null
                                        ? (float) $this->record->currentAssignment->unit_area_sqm
                                        : ($this->record->floor_area_sqm !== null ? (float) $this->record->floor_area_sqm : null)),
                                DatePicker::make('move_in_date')
                                    ->label(__('admin.tenants.fields.move_in_date'))
                                    ->default(today()->toDateString())
                                    ->required(),
                            ])
                            ->action(function (array $data, AssignTenantToPropertyAction $assignTenantToPropertyAction): void {
                                $tenant = User::query()
                                    ->select(['id', 'organization_id', 'name', 'role'])
                                    ->where('organization_id', $this->record->organization_id)
                                    ->tenants()
                                    ->whereDoesntHave('currentPropertyAssignment')
                                    ->findOrFail($data['tenant_id']);

                                $assignTenantToPropertyAction->handle(
                                    $this->record,
                                    $tenant,
                                    isset($data['unit_area_sqm']) ? (float) $data['unit_area_sqm'] : null,
                                    CarbonImmutable::parse((string) $data['move_in_date']),
                                );

                                $this->refreshRecord();

                                Notification::make()
                                    ->title(__('admin.properties.messages.tenant_assigned'))
                                    ->success()
                                    ->send();
                            }),
                        Action::make('scheduleMoveOut')
                            ->label(__('admin.move_out.actions.schedule'))
                            ->icon('heroicon-m-calendar-days')
                            ->color('warning')
                            ->slideOver()
                            ->schema(self::moveOutWizardSchema())
                            ->visible(fn (): bool => $this->currentAssignmentCanMoveOut())
                            ->action(function (array $data, ScheduleTenantMoveOut $scheduleTenantMoveOut): void {
                                $actor = auth()->user();
                                $assignment = $this->record->currentAssignment;

                                abort_if(! $actor instanceof User || ! $assignment instanceof PropertyAssignment, 403);

                                $scheduleTenantMoveOut->handle($actor, $assignment, $data);
                                $this->refreshRecord();

                                Notification::make()
                                    ->title(__('admin.move_out.messages.scheduled'))
                                    ->success()
                                    ->send();
                            }),
                        Action::make('generateFinalInvoice')
                            ->label(__('admin.move_out.actions.generate_final_invoice'))
                            ->icon('heroicon-m-document-text')
                            ->visible(fn (): bool => $this->currentMoveOutProcess() instanceof MoveOutProcess)
                            ->action(function (GenerateFinalInvoice $generateFinalInvoice): void {
                                $actor = auth()->user();
                                $process = $this->currentMoveOutProcess();

                                abort_if(! $actor instanceof User || ! $process instanceof MoveOutProcess, 403);

                                $generateFinalInvoice->handle($actor, $process, [
                                    'allow_without_final_readings' => ! $process->final_readings_required,
                                ]);
                                $this->refreshRecord();

                                Notification::make()
                                    ->title(__('admin.move_out.messages.final_invoice_generated'))
                                    ->success()
                                    ->send();
                            }),
                        Action::make('completeMoveOut')
                            ->label(__('admin.move_out.actions.complete'))
                            ->icon('heroicon-m-check-circle')
                            ->color('success')
                            ->requiresConfirmation()
                            ->schema([
                                Toggle::make('allow_without_final_readings')
                                    ->label(__('admin.move_out.fields.allow_without_final_readings')),
                                Toggle::make('allow_without_final_invoice')
                                    ->label(__('admin.move_out.fields.allow_without_final_invoice')),
                            ])
                            ->visible(fn (): bool => $this->currentMoveOutProcess() instanceof MoveOutProcess)
                            ->action(function (array $data, CompleteTenantMoveOut $completeTenantMoveOut): void {
                                $actor = auth()->user();
                                $process = $this->currentMoveOutProcess();

                                abort_if(! $actor instanceof User || ! $process instanceof MoveOutProcess, 403);

                                $completeTenantMoveOut->handle($actor, $process, $data);
                                $this->refreshRecord();

                                Notification::make()
                                    ->title(__('admin.move_out.messages.completed'))
                                    ->success()
                                    ->send();
                            }),
                        Action::make('unassignTenant')
                            ->label(__('admin.properties.actions.unassign_tenant'))
                            ->color('danger')
                            ->visible(fn (): bool => $this->record->currentAssignment !== null)
                            ->requiresConfirmation()
                            ->modalDescription(__('admin.properties.messages.unassign_tenant_confirmation'))
                            ->action(function (UnassignTenantFromPropertyAction $unassignTenantFromPropertyAction): void {
                                $unassignTenantFromPropertyAction->handle($this->record);
                                $this->refreshRecord();

                                Notification::make()
                                    ->title(__('admin.properties.messages.tenant_unassigned'))
                                    ->success()
                                    ->send();
                            }),
                    ]
                    : []
            ),
            DeleteAction::make()
                ->label(__('admin.actions.delete'))
                ->using(fn (Property $record) => app(DeletePropertyAction::class)->handle($record))
                ->disabled(fn (Property $record): bool => ! $record->canBeDeletedFromAdminWorkspace())
                ->tooltip(fn (Property $record): ?string => $record->adminDeletionBlockedReason()),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function availableTenantOptions(): array
    {
        return User::query()
            ->select(['id', 'organization_id', 'name', 'role'])
            ->where('organization_id', $this->record->organization_id)
            ->tenants()
            ->whereDoesntHave('currentPropertyAssignment')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @return array<int, Wizard>
     */
    private static function moveOutWizardSchema(): array
    {
        return [
            Wizard::make([
                Step::make(__('admin.move_out.wizard.steps.schedule'))
                    ->schema([
                        DatePicker::make('move_out_date')
                            ->label(__('admin.tenants.fields.move_out_date'))
                            ->default(today()->toDateString())
                            ->required(),
                        Textarea::make('reason')
                            ->label(__('admin.move_out.fields.reason'))
                            ->rows(3),
                    ]),
                Step::make(__('admin.move_out.wizard.steps.final_readings'))
                    ->schema([
                        Toggle::make('final_readings_required')
                            ->label(__('admin.move_out.fields.final_readings_required'))
                            ->default(true),
                    ]),
                Step::make(__('admin.move_out.wizard.steps.outstanding_balance'))
                    ->schema([
                        Textarea::make('internal_note')
                            ->label(__('admin.move_out.fields.internal_note'))
                            ->rows(3),
                    ]),
                Step::make(__('admin.move_out.wizard.steps.contract_documents'))
                    ->schema([
                        Textarea::make('contract_note')
                            ->label(__('admin.move_out.fields.contract_note'))
                            ->rows(2),
                    ]),
                Step::make(__('admin.move_out.wizard.steps.portal_access'))
                    ->schema([
                        Select::make('portal_access_after_move_out')
                            ->label(__('admin.move_out.fields.portal_access_after_move_out'))
                            ->options(PortalAccessAfterMoveOut::options())
                            ->default(PortalAccessAfterMoveOut::KEEP_HISTORICAL_ACCESS->value)
                            ->required(),
                    ]),
            ]),
        ];
    }

    private function currentAssignmentCanMoveOut(): bool
    {
        return $this->record->currentAssignment instanceof PropertyAssignment
            && $this->record->currentAssignment->status === PropertyAssignmentStatus::ACTIVE
            && ! ($this->currentMoveOutProcess() instanceof MoveOutProcess);
    }

    private function currentMoveOutProcess(): ?MoveOutProcess
    {
        $assignment = $this->record->currentAssignment;

        if (! $assignment instanceof PropertyAssignment) {
            return null;
        }

        return $assignment->activeMoveOutProcess()
            ->select(['id', 'organization_id', 'tenant_id', 'property_id', 'property_assignment_id', 'status', 'move_out_date', 'final_readings_required', 'final_readings_completed_at', 'final_invoice_id', 'contract_id', 'portal_access_after_move_out', 'reason'])
            ->first();
    }

    private function refreshRecord(): void
    {
        $record = Property::query()
            ->forOrganizationWorkspace($this->record->organization_id)
            ->findOrFail($this->record->getKey());

        $this->record = $record;
    }
}
