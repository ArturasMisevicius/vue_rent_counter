<?php

namespace App\Filament\Resources\Buildings\Tables;

use App\Actions\Admin\Buildings\DeleteBuildingAction;
use App\Models\Building;
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
                    ->label(__('admin.buildings.columns.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('city')
                    ->label(__('admin.buildings.columns.city'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('country_code')
                    ->label(__('admin.buildings.columns.country_code'))
                    ->sortable(),
                TextColumn::make('properties_count')
                    ->label(__('admin.buildings.columns.properties_count'))
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('admin.buildings.columns.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('city')
                    ->label(__('admin.buildings.columns.city'))
                    ->options(fn (): array => Building::query()
                        ->select(['city', 'organization_id'])
                        ->where('organization_id', auth()->user()?->organization_id)
                        ->orderBy('city')
                        ->pluck('city', 'city')
                        ->all()),
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
