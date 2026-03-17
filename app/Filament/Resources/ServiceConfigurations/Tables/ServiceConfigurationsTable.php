<?php

namespace App\Filament\Resources\ServiceConfigurations\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ServiceConfigurationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('property.name')
                    ->label(__('admin.service_configurations.columns.property'))
                    ->searchable(),
                TextColumn::make('utilityService.name')
                    ->label(__('admin.service_configurations.columns.utility_service'))
                    ->searchable(),
                TextColumn::make('provider.name')
                    ->label(__('admin.service_configurations.columns.provider'))
                    ->toggleable(),
                TextColumn::make('pricing_model')
                    ->label(__('admin.service_configurations.columns.pricing_model'))
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('property_id')
                    ->relationship('property', 'name')
                    ->label(__('admin.service_configurations.fields.property')),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
