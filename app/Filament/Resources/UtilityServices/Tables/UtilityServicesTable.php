<?php

namespace App\Filament\Resources\UtilityServices\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UtilityServicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.utility_services.columns.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('unit_of_measurement')
                    ->label(__('admin.utility_services.columns.unit_of_measurement'))
                    ->sortable(),
                TextColumn::make('default_pricing_model')
                    ->label(__('admin.utility_services.columns.default_pricing_model'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => __('admin.utility_services.pricing_models.'.($state->value ?? $state))),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
