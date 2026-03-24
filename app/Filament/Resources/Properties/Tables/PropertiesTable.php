<?php

namespace App\Filament\Resources\Properties\Tables;

use App\Enums\PropertyType;
use App\Filament\Actions\Admin\Properties\DeletePropertyAction;
use App\Filament\Resources\Buildings\BuildingResource;
use App\Filament\Resources\Properties\PropertyResource;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\Building;
use App\Models\Organization;
use App\Models\Property;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Enums\FiltersResetActionPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Lang;

class PropertiesTable
{
    public static function configure(Table $table): Table
    {
        self::overrideFilterResetLabel();

        return $table
            ->columns([
                TextColumn::make('organization.name')
                    ->label(__('superadmin.organizations.singular'))
                    ->visible(fn (): bool => static::currentUser()?->isSuperadmin() ?? false)
                    ->toggleable(),
                TextColumn::make('name')
                    ->label(__('admin.properties.columns.property_name'))
                    ->url(fn (Property $record): string => PropertyResource::getUrl('view', ['record' => $record]))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('building.name')
                    ->label(__('admin.properties.columns.building'))
                    ->url(fn (Property $record): ?string => $record->building !== null
                        ? BuildingResource::getUrl('view', ['record' => $record->building])
                        : null)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('admin.properties.columns.type'))
                    ->badge(),
                TextColumn::make('floor')
                    ->label(__('admin.properties.columns.floor'))
                    ->state(fn (Property $record): string => $record->floorDisplay())
                    ->sortable(),
                TextColumn::make('floor_area_sqm')
                    ->label(__('admin.properties.columns.area'))
                    ->state(fn (Property $record): string => $record->areaDisplay()),
                TextColumn::make('currentAssignment.tenant.name')
                    ->label(__('admin.properties.columns.current_tenant'))
                    ->default('—')
                    ->searchable(),
                TextColumn::make('occupancy_status')
                    ->label(__('admin.properties.columns.status'))
                    ->state(fn (Property $record): string => $record->occupancyStatusLabel())
                    ->badge()
                    ->color(fn (Property $record): string => $record->isOccupied() ? 'success' : 'gray'),
                TextColumn::make('created_at')
                    ->label(__('admin.properties.columns.date_created'))
                    ->date('F j, Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('organization')
                    ->label(__('superadmin.organizations.singular'))
                    ->visible(fn (): bool => static::currentUser()?->isSuperadmin() ?? false)
                    ->options(fn (): array => Organization::query()
                        ->select(['id', 'name'])
                        ->ordered()
                        ->pluck('name', 'id')
                        ->all())
                    ->query(function (Builder $query, array $data): Builder {
                        $organizationId = $data['value'] ?? null;

                        if (blank($organizationId)) {
                            return $query;
                        }

                        return $query->forOrganization((int) $organizationId);
                    }),
                SelectFilter::make('building_id')
                    ->label(__('admin.properties.columns.building'))
                    ->placeholder(__('admin.properties.filters.all_buildings'))
                    ->options(function (): array {
                        $user = Auth::user();
                        $isSuperadmin = $user instanceof User && $user->isSuperadmin();

                        return Building::query()
                            ->select(['id', 'name', 'organization_id'])
                            ->when(
                                ! $isSuperadmin,
                                fn ($query) => $query->where('organization_id', app(OrganizationContext::class)->currentOrganizationId()),
                            )
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all();
                    }),
                SelectFilter::make('type')
                    ->label(__('admin.properties.columns.type'))
                    ->placeholder(__('admin.properties.filters.all_types'))
                    ->options(PropertyType::options()),
                SelectFilter::make('occupancy_status')
                    ->label(__('admin.properties.columns.status'))
                    ->placeholder(__('admin.properties.filters.all_statuses'))
                    ->options([
                        'occupied' => __('admin.properties.filters.occupancy.occupied'),
                        'vacant' => __('admin.properties.filters.occupancy.vacant'),
                    ])
                    ->query(function ($query, array $data): void {
                        match ($data['value'] ?? null) {
                            'occupied' => $query->whereHas('currentAssignment'),
                            'vacant' => $query->whereDoesntHave('currentAssignment'),
                            default => null,
                        };
                    }),
            ])
            ->emptyStateHeading(__('admin.properties.empty_state.heading'))
            ->emptyStateDescription(__('admin.properties.empty_state.description'))
            ->emptyStateActions(
                PropertyResource::shouldShowBlockedCreateAction('properties')
                    ? [
                        PropertyResource::makeSubscriptionInfoAction(
                            name: 'create',
                            resource: 'properties',
                            label: __('admin.properties.actions.new_property'),
                        ),
                    ]
                    : (
                        PropertyResource::canCreate()
                            ? [
                                Action::make('createProperty')
                                    ->label(__('admin.properties.actions.new_property'))
                                    ->url(PropertyResource::getUrl('create'))
                                    ->icon('heroicon-m-plus')
                                    ->button(),
                            ]
                            : []
                    ),
            )
            ->recordActions([
                ViewAction::make()
                    ->label(__('admin.actions.view')),
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
                            PropertyResource::hidesSubscriptionWriteActions()
                                ? []
                                : [
                                    EditAction::make()
                                        ->label(__('admin.actions.edit')),
                                ]
                        )
                ),
                ...(
                    PropertyResource::canMutateSubscriptionScopedRecords()
                        ? [
                            DeleteAction::make()
                                ->label(__('admin.actions.delete'))
                                ->using(fn (Property $record) => app(DeletePropertyAction::class)->handle($record))
                                ->authorize(function (Property $record): bool {
                                    $user = Auth::user();

                                    if (! $user instanceof User) {
                                        return false;
                                    }

                                    return PropertyResource::canDelete($record)
                                        && Gate::forUser($user)->check('delete', $record);
                                })
                                ->disabled(fn (Property $record): bool => ! $record->canBeDeletedFromAdminWorkspace())
                                ->tooltip(fn (Property $record): ?string => $record->adminDeletionBlockedReason()),
                        ]
                        : []
                ),
            ])
            ->searchPlaceholder(__('admin.properties.search_placeholder'))
            ->deferFilters(false)
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersResetActionPosition(FiltersResetActionPosition::Header)
            ->defaultSort('name');
    }

    private static function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }

    private static function overrideFilterResetLabel(): void
    {
        Lang::addLines([
            'table.filters.actions.reset.label' => 'Clear Filters',
        ], 'en', 'filament-tables');

        Lang::addLines([
            'table.filters.actions.reset.label' => 'Išvalyti filtrus',
        ], 'lt', 'filament-tables');
    }
}
