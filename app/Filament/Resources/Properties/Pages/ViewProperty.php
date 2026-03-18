<?php

namespace App\Filament\Resources\Properties\Pages;

use App\Filament\Actions\Admin\Properties\AssignTenantToPropertyAction;
use App\Filament\Resources\Properties\PropertyResource;
use App\Models\Property;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewProperty extends ViewRecord
{
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
        return __('admin.properties.view_title');
    }

    public function getContentTabLabel(): ?string
    {
        return __('admin.properties.tabs.tenant');
    }

    protected function getHeaderActions(): array
    {
        return [
            ...(
                PropertyResource::canEdit($this->record) && $this->record->currentAssignment === null
                    ? [
                        Action::make('assignTenant')
                            ->label(__('admin.properties.actions.assign_tenant'))
                            ->slideOver()
                            ->schema([
                                Select::make('tenant_id')
                                    ->label(__('admin.properties.actions.assign_tenant'))
                                    ->options(fn (): array => $this->availableTenantOptions())
                                    ->searchable()
                                    ->required(),
                                TextInput::make('unit_area_sqm')
                                    ->label(__('admin.tenants.fields.unit_area_sqm'))
                                    ->numeric()
                                    ->minValue(0),
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
                    ]
                    : []
            ),
            ...(
                PropertyResource::shouldInterceptGraceEditAction()
                    ? [
                        PropertyResource::makeSubscriptionInfoAction(
                            name: 'edit',
                            resource: 'properties',
                            label: __('filament-actions::edit.single.label', [
                                'label' => PropertyResource::getModelLabel(),
                            ]),
                        ),
                    ]
                    : (
                        PropertyResource::canEdit($this->record)
                            ? [EditAction::make()]
                            : []
                    )
            ),
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
