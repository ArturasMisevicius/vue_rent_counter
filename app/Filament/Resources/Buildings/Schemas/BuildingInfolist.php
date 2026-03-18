<?php

namespace App\Filament\Resources\Buildings\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BuildingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.buildings.sections.details'))
                    ->schema([
                        TextEntry::make('name')
                            ->label(__('admin.buildings.fields.name')),
                        TextEntry::make('address_line_1')
                            ->label(__('admin.buildings.fields.address_line_1')),
                        TextEntry::make('address_line_2')
                            ->label(__('admin.buildings.fields.address_line_2'))
                            ->default(__('admin.buildings.empty.address_line_2')),
                        TextEntry::make('city')
                            ->label(__('admin.buildings.fields.city')),
                        TextEntry::make('postal_code')
                            ->label(__('admin.buildings.fields.postal_code')),
                        TextEntry::make('country_code')
                            ->label(__('admin.buildings.fields.country_code')),
                    ])
                    ->columns(2),
                Section::make(__('admin.buildings.sections.activity'))
                    ->schema([
                        TextEntry::make('properties_count')
                            ->label(__('admin.buildings.fields.properties_count')),
                        TextEntry::make('meters_count')
                            ->label(__('admin.buildings.fields.meters_count')),
                        TextEntry::make('updated_at')
                            ->label(__('admin.buildings.fields.updated_at'))
                            ->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }
}
