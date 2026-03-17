<?php

namespace App\Filament\Resources\Buildings\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BuildingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.buildings.sections.details'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('admin.buildings.columns.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('address_line_1')
                            ->label(__('admin.buildings.columns.address_line_1'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('address_line_2')
                            ->label(__('admin.buildings.columns.address_line_2'))
                            ->maxLength(255),
                        TextInput::make('city')
                            ->label(__('admin.buildings.columns.city'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('postal_code')
                            ->label(__('admin.buildings.columns.postal_code'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('country_code')
                            ->label(__('admin.buildings.columns.country_code'))
                            ->required()
                            ->maxLength(2)
                            ->minLength(2),
                    ])
                    ->columns(2),
            ]);
    }
}
