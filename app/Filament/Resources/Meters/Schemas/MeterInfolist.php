<?php

namespace App\Filament\Resources\Meters\Schemas;

use App\Models\Meter;
use App\Models\MeterReading;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MeterInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.meters.sections.details'))
                    ->schema([
                        TextEntry::make('property.name')
                            ->label(__('admin.meters.fields.property')),
                        TextEntry::make('property.building.name')
                            ->label(__('admin.meters.fields.building')),
                        TextEntry::make('name')
                            ->label(__('admin.meters.fields.name')),
                        TextEntry::make('identifier')
                            ->label(__('admin.meters.fields.identifier')),
                        TextEntry::make('type')
                            ->label(__('admin.meters.fields.type'))
                            ->formatStateUsing(fn ($state): string => __('admin.meters.types.'.($state->value ?? $state))),
                        TextEntry::make('unit')
                            ->label(__('admin.meters.fields.unit')),
                        TextEntry::make('status')
                            ->label(__('admin.meters.fields.status'))
                            ->formatStateUsing(fn ($state): string => __('admin.meters.statuses.'.($state->value ?? $state))),
                        TextEntry::make('installed_at')
                            ->label(__('admin.meters.fields.installed_at'))
                            ->date(),
                    ])
                    ->columns(2),
                Section::make(__('admin.meters.sections.history'))
                    ->schema([
                        TextEntry::make('reading_history')
                            ->label(__('admin.meters.fields.reading_history'))
                            ->state(function (Meter $record): string {
                                $history = $record->readings
                                    ->sortByDesc('reading_date')
                                    ->map(function (MeterReading $reading): string {
                                        return implode(' · ', array_filter([
                                            $reading->reading_date?->format('Y-m-d'),
                                            (string) $reading->reading_value,
                                        ]));
                                    })
                                    ->implode("\n");

                                return $history !== '' ? $history : __('admin.meters.empty.readings');
                            }),
                    ]),
                Section::make(__('admin.meters.sections.chart'))
                    ->schema([
                        TextEntry::make('usage_chart')
                            ->label(__('admin.meters.fields.usage_chart'))
                            ->state(__('admin.meters.empty.chart')),
                    ]),
            ]);
    }
}
