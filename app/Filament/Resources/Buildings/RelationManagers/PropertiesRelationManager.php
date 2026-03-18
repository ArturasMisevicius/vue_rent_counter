<?php

namespace App\Filament\Resources\Buildings\RelationManagers;

use App\Filament\Resources\Buildings\BuildingResource;
use App\Filament\Resources\Properties\PropertyResource;
use App\Models\Property;
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

        return $count === null ? null : (string) $count;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withWorkspaceSummary()->ordered())
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.properties.columns.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('unit_number')
                    ->label(__('admin.properties.columns.unit_number'))
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('admin.properties.columns.type'))
                    ->badge(),
                TextColumn::make('currentAssignment.tenant.name')
                    ->label(__('admin.properties.columns.tenant'))
                    ->default(__('admin.properties.empty.unassigned'))
                    ->searchable(),
                TextColumn::make('meters_count')
                    ->label(__('admin.properties.columns.meters_count'))
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (Property $record): string => PropertyResource::getUrl('view', ['record' => $record])),
            ])
            ->defaultSort('name');
    }
}
