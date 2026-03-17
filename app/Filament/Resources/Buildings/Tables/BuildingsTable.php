<?php

namespace App\Filament\Resources\Buildings\Tables;

use App\Actions\Admin\Buildings\DeleteBuildingAction;
use App\Filament\Resources\Buildings\BuildingResource;
use App\Models\Building;
use App\Support\Admin\OrganizationContext;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BuildingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.buildings.fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('city')
                    ->label(__('admin.buildings.fields.city'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('address_line_1')
                    ->label(__('admin.buildings.fields.address_line_1'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('properties_count')
                    ->label(__('admin.buildings.fields.properties_count'))
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label(__('admin.buildings.fields.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('city')
                    ->label(__('admin.buildings.fields.city'))
                    ->options(fn (): array => Building::query()
                        ->select(['city', 'organization_id'])
                        ->where('organization_id', app(OrganizationContext::class)->currentOrganizationId())
                        ->orderBy('city')
                        ->pluck('city', 'city')
                        ->all()),
            ])
            ->emptyStateHeading(__('admin.buildings.empty_state.heading'))
            ->emptyStateDescription(__('admin.buildings.empty_state.description'))
            ->emptyStateActions([
                Action::make('createBuilding')
                    ->label(__('admin.buildings.empty_state.action'))
                    ->url(BuildingResource::getUrl('create'))
                    ->icon('heroicon-m-plus')
                    ->button(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->using(fn (Building $record) => app(DeleteBuildingAction::class)->handle($record))
                    ->authorize(fn (Building $record): bool => auth()->user()?->can('delete', $record) ?? false),
            ])
            ->defaultSort('name');
    }
}
