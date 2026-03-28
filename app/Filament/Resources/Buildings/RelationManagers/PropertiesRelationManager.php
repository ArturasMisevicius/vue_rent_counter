<?php

namespace App\Filament\Resources\Buildings\RelationManagers;

use App\Filament\Resources\Buildings\BuildingResource;
use App\Filament\Resources\Properties\PropertyResource;
use App\Filament\Resources\Tenants\TenantResource;
use App\Models\Property;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PropertiesRelationManager extends RelationManager
{
    protected static string $relationship = 'properties';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return BuildingResource::canView($ownerRecord);
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.buildings.tabs.properties');
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->getAttribute('properties_count');

        return $count === null ? (string) $ownerRecord->properties()->count() : (string) $count;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->select([
                    'id',
                    'organization_id',
                    'building_id',
                    'name',
                    'floor',
                    'type',
                    'floor_area_sqm',
                ])
                ->withCurrentAssignmentSummary()
                ->ordered())
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.properties.columns.property_name'))
                    ->url(fn (Property $record): string => PropertyResource::getUrl('view', ['record' => $record]))
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
                    ->label(__('admin.properties.columns.tenant'))
                    ->default(__('admin.properties.empty.vacant'))
                    ->url(fn (Property $record): ?string => $record->currentAssignment?->tenant !== null
                        ? TenantResource::getUrl('view', ['record' => $record->currentAssignment->tenant])
                        : null)
                    ->searchable(),
                TextColumn::make('occupancy_status')
                    ->label(__('admin.properties.columns.status'))
                    ->state(fn (Property $record): string => $record->occupancyStatusLabel())
                    ->badge()
                    ->color(fn (Property $record): string => $record->isOccupied() ? 'success' : 'gray'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label(__('admin.actions.view'))
                    ->url(fn (Property $record): string => PropertyResource::getUrl('view', ['record' => $record])),
                EditAction::make()
                    ->label(__('admin.actions.edit'))
                    ->url(fn (Property $record): string => PropertyResource::getUrl('edit', ['record' => $record])),
            ])
            ->defaultSort('name');
    }
}
