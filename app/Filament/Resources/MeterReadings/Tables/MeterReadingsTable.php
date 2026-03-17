<?php

namespace App\Filament\Resources\MeterReadings\Tables;

use App\Enums\MeterReadingValidationStatus;
use App\Models\Meter;
use App\Models\Property;
use App\Support\Admin\OrganizationContext;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
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
                    ->formatStateUsing(fn ($state): string => __('admin.meter_readings.methods.'.($state->value ?? $state))),
            ])
            ->filters([
                SelectFilter::make('meter_id')
                    ->label(__('admin.meter_readings.fields.meter'))
                    ->options(fn (): array => Meter::query()
                        ->select(['id', 'name', 'organization_id'])
                        ->where('organization_id', app(OrganizationContext::class)->currentOrganizationId())
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all()),
                SelectFilter::make('property_id')
                    ->label(__('admin.meter_readings.fields.property'))
                    ->options(fn (): array => Property::query()
                        ->select(['id', 'name', 'organization_id'])
                        ->where('organization_id', app(OrganizationContext::class)->currentOrganizationId())
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all()),
                SelectFilter::make('validation_status')
                    ->label(__('admin.meter_readings.fields.validation_status'))
                    ->options([
                        MeterReadingValidationStatus::PENDING->value => __('admin.meter_readings.statuses.pending'),
                        MeterReadingValidationStatus::VALID->value => __('admin.meter_readings.statuses.valid'),
                        MeterReadingValidationStatus::FLAGGED->value => __('admin.meter_readings.statuses.flagged'),
                        MeterReadingValidationStatus::REJECTED->value => __('admin.meter_readings.statuses.rejected'),
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->defaultSort('reading_date', 'desc');
    }
}
