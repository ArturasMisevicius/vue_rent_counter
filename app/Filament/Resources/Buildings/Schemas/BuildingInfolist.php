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
                Section::make(__('admin.buildings.sections.information'))
                    ->schema([
                        TextEntry::make('name')
                            ->label(__('admin.buildings.fields.building_name')),
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
                            ->date('F j, Y'),
                    ])
                    ->columns(3),
            ]);
    }
}
