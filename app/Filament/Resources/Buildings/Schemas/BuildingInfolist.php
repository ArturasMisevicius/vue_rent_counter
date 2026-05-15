<?php

namespace App\Filament\Resources\Buildings\Schemas;

use App\Filament\Support\Formatting\LocalizedDateFormatter;
use App\Models\Building;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BuildingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.buildings.sections.information'))
                    ->schema([
                        TextEntry::make('name')
                            ->label(__('admin.buildings.fields.building_name'))
                            ->state(fn (Building $record): string => $record->displayName()),
                        TextEntry::make('address')
                            ->label(__('admin.buildings.fields.full_address')),
                    ])
                    ->columns(1),
                Section::make(__('admin.buildings.sections.summary'))
                    ->schema([
                        TextEntry::make('properties_count')
                            ->label(__('admin.buildings.fields.properties_count')),
                        TextEntry::make('meters_count')
                            ->label(__('admin.buildings.fields.meters_count')),
                        TextEntry::make('created_at')
                            ->label(__('admin.buildings.fields.created_at'))
                            ->state(fn ($record): string => $record->created_at?->locale(app()->getLocale())->translatedFormat(LocalizedDateFormatter::dateFormat()) ?? '—'),
                    ])
                    ->columns(3),
            ]);
    }
}
