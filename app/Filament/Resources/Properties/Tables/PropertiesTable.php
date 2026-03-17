<?php

namespace App\Filament\Resources\Properties\Tables;

use App\Actions\Admin\Properties\DeletePropertyAction;
use App\Enums\PropertyType;
use App\Models\Building;
use App\Models\Property;
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
                    ->sortable(),
                TextColumn::make('unit_number')
                    ->label(__('admin.properties.columns.unit_number'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('type')
                    ->label(__('admin.properties.columns.type'))
                    ->badge()
                    ->formatStateUsing(fn (PropertyType $state): string => __("admin.properties.types.{$state->value}")),
                TextColumn::make('currentAssignment.tenant.name')
                    ->label(__('admin.properties.columns.tenant'))
                    ->default(__('admin.properties.empty.vacant')),
                TextColumn::make('created_at')
                    ->label(__('admin.properties.columns.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('building_id')
                    ->label(__('admin.properties.columns.building'))
                    ->options(fn (): array => Building::query()
                        ->select(['id', 'name', 'organization_id'])
                        ->where('organization_id', auth()->user()?->organization_id)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all()),
                SelectFilter::make('type')
                    ->label(__('admin.properties.columns.type'))
                    ->options([
                        PropertyType::APARTMENT->value => __('admin.properties.types.apartment'),
                        PropertyType::HOUSE->value => __('admin.properties.types.house'),
                        PropertyType::OFFICE->value => __('admin.properties.types.office'),
                        PropertyType::STORAGE->value => __('admin.properties.types.storage'),
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->using(fn (Property $record) => app(DeletePropertyAction::class)->handle($record))
                    ->authorize(fn (Property $record): bool => auth()->user()?->can('delete', $record) ?? false),
            ])
            ->defaultSort('name');
    }
}
