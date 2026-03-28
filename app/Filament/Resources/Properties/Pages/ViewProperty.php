<?php

namespace App\Filament\Resources\Properties\Pages;

use App\Filament\Actions\Admin\Properties\AssignTenantToPropertyAction;
use App\Filament\Actions\Admin\Properties\DeletePropertyAction;
use App\Filament\Actions\Admin\Properties\UnassignTenantFromPropertyAction;
use App\Filament\Resources\Pages\Concerns\HasDeferredRelationManagerTabBadges;
use App\Filament\Resources\Properties\PropertyResource;
use App\Models\Property;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

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
            $this->record->name,
        ];
    }

    public function getTitle(): string
    {
        return $this->record->name;
    }

    public function getSubheading(): ?string
    {
        $buildingName = $this->record->building?->name;
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
                                );

                                $this->refreshRecord();

                                Notification::make()
                                    ->title(__('admin.properties.messages.tenant_assigned'))
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

    private function refreshRecord(): void
    {
        $record = Property::query()
            ->forOrganizationWorkspace($this->record->organization_id)
            ->findOrFail($this->record->getKey());

        $this->record = $record;
    }
}
