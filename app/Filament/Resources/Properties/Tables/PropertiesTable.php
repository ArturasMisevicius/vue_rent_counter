<?php

namespace App\Filament\Resources\Properties\Tables;

use App\Actions\Admin\Properties\DeletePropertyAction;
use App\Enums\PropertyType;
use App\Models\Building;
use App\Models\Property;
use App\Support\Admin\OrganizationContext;
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
                    ->label(__('admin.properties.fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('building.name')
                    ->label(__('admin.properties.fields.building'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('unit_number')
                    ->label(__('admin.properties.fields.unit_number'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('type')
                    ->label(__('admin.properties.fields.type'))
                    ->badge()
                    ->formatStateUsing(
                        fn (PropertyType|string $state): string => __('admin.properties.types.'.($state instanceof PropertyType ? $state->value : $state)),
                    ),
                TextColumn::make('currentAssignment.tenant.name')
                    ->label(__('admin.properties.fields.current_tenant'))
                    ->default(__('admin.properties.empty.unassigned'))
                    ->searchable(),
                TextColumn::make('meters_count')
                    ->label(__('admin.properties.fields.meters_count'))
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('building_id')
                    ->label(__('admin.properties.fields.building'))
                    ->options(fn (): array => Building::query()
                        ->select(['id', 'name', 'organization_id'])
                        ->where('organization_id', app(OrganizationContext::class)->currentOrganizationId())
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all()),
                SelectFilter::make('type')
                    ->label(__('admin.properties.fields.type'))
                    ->options(
                        collect(PropertyType::cases())
                            ->mapWithKeys(fn (PropertyType $type): array => [
                                $type->value => __('admin.properties.types.'.$type->value),
                            ])
                            ->all(),
                    ),
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
