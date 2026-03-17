<?php

namespace App\Filament\Resources\Properties\Pages;

use App\Actions\Admin\Properties\AssignTenantToPropertyAction;
use App\Actions\Admin\Properties\DeletePropertyAction;
use App\Actions\Admin\Properties\UnassignTenantFromPropertyAction;
use App\Enums\UserRole;
use App\Filament\Resources\Properties\PropertyResource;
use App\Models\Property;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ViewRecord;

class ViewProperty extends ViewRecord
{
    protected static string $resource = PropertyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('assignTenant')
                ->label(__('admin.properties.actions.assign_tenant'))
                ->schema([
                    Select::make('tenant_user_id')
                        ->label(__('admin.properties.columns.tenant'))
                        ->options(fn (): array => User::query()
                            ->select(['id', 'name', 'organization_id', 'role'])
                            ->where('organization_id', auth()->user()?->organization_id)
                            ->where('role', UserRole::TENANT->value)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable()
                        ->preload()
                        ->required(),
                    TextInput::make('unit_area_sqm')
                        ->label(__('admin.properties.columns.floor_area_sqm'))
                        ->numeric()
                        ->suffix('sqm'),
                ])
                ->action(function (array $data): void {
                    $tenant = User::query()
                        ->select(['id', 'name', 'organization_id', 'role'])
                        ->whereKey($data['tenant_user_id'])
                        ->firstOrFail();

                    app(AssignTenantToPropertyAction::class)->handle(
                        $this->getRecord(),
                        $tenant,
                        $data['unit_area_sqm'] ?? null,
                    );

                    $this->record->refresh();
                }),
            Action::make('unassignTenant')
                ->label(__('admin.properties.actions.unassign_tenant'))
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->getRecord()->currentAssignment()->exists())
                ->action(function (): void {
                    app(UnassignTenantFromPropertyAction::class)->handle($this->getRecord());

                    $this->record->refresh();
                }),
            DeleteAction::make()
                ->using(fn (Property $record) => app(DeletePropertyAction::class)->handle($record)),
        ];
    }
}
