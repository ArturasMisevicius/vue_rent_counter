<?php

namespace App\Filament\Resources\MeterReadings\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MeterReadingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.meter_readings.sections.details'))
                    ->schema([
                        TextEntry::make('meter.name')
                            ->label(__('admin.meter_readings.fields.meter')),
                        TextEntry::make('property.name')
                            ->label(__('admin.meter_readings.fields.property')),
                        TextEntry::make('property.building.name')
                            ->label(__('admin.meter_readings.fields.building')),
                        TextEntry::make('reading_value')
                            ->label(__('admin.meter_readings.fields.reading_value'))
                            ->formatStateUsing(fn ($state): string => rtrim(rtrim(number_format((float) $state, 3, '.', ''), '0'), '.')),
                        TextEntry::make('reading_date')
                            ->label(__('admin.meter_readings.fields.reading_date'))
                            ->date(),
                        TextEntry::make('validation_status')
                            ->label(__('admin.meter_readings.fields.validation_status'))
                            ->formatStateUsing(fn ($state): string => __('admin.meter_readings.statuses.'.($state->value ?? $state))),
                        TextEntry::make('submission_method')
                            ->label(__('admin.meter_readings.fields.submission_method'))
                            ->formatStateUsing(fn ($state): string => __('admin.meter_readings.submission_methods.'.($state->value ?? $state))),
                        TextEntry::make('submittedBy.name')
                            ->label(__('admin.meter_readings.fields.submitted_by'))
                            ->default(__('admin.meter_readings.empty.submitted_by')),
                        TextEntry::make('notes')
                            ->label(__('admin.meter_readings.fields.notes'))
                            ->default(__('admin.meter_readings.empty.notes')),
                    ])
                    ->columns(2),
            ]);
    }
}
