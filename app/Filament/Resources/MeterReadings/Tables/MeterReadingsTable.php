<?php

namespace App\Filament\Resources\MeterReadings\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MeterReadingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('meter.name')
                    ->label(__('admin.meter_readings.columns.meter'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('property.name')
                    ->label(__('admin.meter_readings.columns.property'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('reading_value')
                    ->label(__('admin.meter_readings.columns.reading_value'))
                    ->formatStateUsing(fn ($state): string => rtrim(rtrim(number_format((float) $state, 3, '.', ''), '0'), '.'))
                    ->sortable(),
                TextColumn::make('reading_date')
                    ->label(__('admin.meter_readings.columns.reading_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('validation_status')
                    ->label(__('admin.meter_readings.columns.validation_status'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => __('admin.meter_readings.statuses.'.($state->value ?? $state))),
                TextColumn::make('submission_method')
                    ->label(__('admin.meter_readings.columns.submission_method'))
                    ->formatStateUsing(fn ($state): string => __('admin.meter_readings.submission_methods.'.($state->value ?? $state))),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->defaultSort('reading_date', 'desc');
    }
}
