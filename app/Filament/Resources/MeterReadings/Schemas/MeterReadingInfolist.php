<?php

namespace App\Filament\Resources\MeterReadings\Schemas;

use App\Filament\Support\Localization\DatabaseContentLocalizer;
use App\Models\MeterReading;
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
                            ->label(__('admin.meter_readings.fields.meter'))
                            ->state(fn (MeterReading $record): string => $record->meter?->displayName() ?? '—'),
                        TextEntry::make('property.name')
                            ->label(__('admin.meter_readings.fields.property'))
                            ->state(fn (MeterReading $record): string => $record->property?->displayName() ?? '—'),
                        TextEntry::make('property.building.name')
                            ->label(__('admin.meter_readings.fields.building'))
                            ->state(fn (MeterReading $record): string => $record->property?->building?->displayName() ?? '—'),
                        TextEntry::make('reading_value')
                            ->label(__('admin.meter_readings.fields.reading_value'))
                            ->formatStateUsing(fn ($state): string => self::formatDecimal((float) $state, 3)),
                        TextEntry::make('reading_date')
                            ->label(__('admin.meter_readings.fields.reading_date'))
                            ->date(),
                        TextEntry::make('validation_status')
                            ->label(__('admin.meter_readings.fields.validation_status'))
                            ->badge(),
                        TextEntry::make('submission_method')
                            ->label(__('admin.meter_readings.fields.submission_method'))
                            ->badge(),
                        TextEntry::make('submittedBy.name')
                            ->label(__('admin.meter_readings.fields.submitted_by'))
                            ->default(__('admin.meter_readings.empty.submitted_by')),
                        TextEntry::make('notes')
                            ->label(__('admin.meter_readings.fields.notes'))
                            ->state(fn (MeterReading $record): string => app(DatabaseContentLocalizer::class)->meterReadingNotes($record->notes) ?? __('admin.meter_readings.empty.notes'))
                            ->default(__('admin.meter_readings.empty.notes')),
                    ])
                    ->columns(2),
            ]);
    }

    private static function formatDecimal(float $value, int $precision): string
    {
        $formatter = new \NumberFormatter(app()->getLocale(), \NumberFormatter::DECIMAL);
        $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, 0);
        $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $precision);

        return (string) $formatter->format($value);
    }
}
