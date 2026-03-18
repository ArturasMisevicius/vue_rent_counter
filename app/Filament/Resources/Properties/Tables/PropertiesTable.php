<?php

namespace App\Filament\Resources\Properties\Tables;

use App\Enums\PropertyType;
use App\Filament\Actions\Admin\Properties\DeletePropertyAction;
use App\Filament\Resources\Properties\PropertyResource;
use App\Filament\Support\Admin\OrganizationContext;
use App\Models\Building;
use App\Models\Property;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PropertiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.properties.columns.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('building.name')
                    ->label(__('admin.properties.columns.building'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('unit_number')
                    ->label(__('admin.properties.columns.unit_number'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('type')
                    ->label(__('admin.properties.columns.type'))
                    ->badge(),
                TextColumn::make('currentAssignment.tenant.name')
                    ->label(__('admin.properties.columns.tenant'))
                    ->default(__('admin.properties.empty.unassigned'))
                    ->searchable(),
                TextColumn::make('meters_count')
                    ->label(__('admin.properties.columns.meters_count'))
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('building_id')
                    ->label(__('admin.properties.columns.building'))
                    ->options(fn (): array => Building::query()
                        ->select(['id', 'name', 'organization_id'])
                        ->where('organization_id', app(OrganizationContext::class)->currentOrganizationId())
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all()),
                SelectFilter::make('type')
                    ->label(__('admin.properties.columns.type'))
                    ->options(PropertyType::options()),
                SelectFilter::make('occupancy_status')
                    ->label(__('admin.properties.filters.occupancy_status'))
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
                            label: __('admin.properties.empty_state.action'),
                        ),
                    ]
                    : (
                        PropertyResource::canCreate()
                            ? [
                                Action::make('createProperty')
                                    ->label(__('admin.properties.empty_state.action'))
                                    ->url(PropertyResource::getUrl('create'))
                                    ->icon('heroicon-m-plus')
                                    ->button(),
                            ]
                            : []
                    ),
            )
            ->recordActions([
                ViewAction::make(),
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
                                    EditAction::make(),
                                ]
                        )
                ),
                ...(
                    PropertyResource::canMutateSubscriptionScopedRecords()
                        ? [
                            DeleteAction::make()
                                ->using(fn (Property $record) => app(DeletePropertyAction::class)->handle($record))
                                ->authorize(fn (Property $record): bool => PropertyResource::canDelete($record)
                                    && (auth()->user()?->can('delete', $record) ?? false)),
                        ]
                        : []
                ),
            ])
            ->defaultSort('name');
    }
}
