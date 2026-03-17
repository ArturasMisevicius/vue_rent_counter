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
                            ->label(__('admin.buildings.fields.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('address_line_1')
                            ->label(__('admin.buildings.fields.address_line_1'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('address_line_2')
                            ->label(__('admin.buildings.fields.address_line_2'))
                            ->maxLength(255),
                        TextInput::make('city')
                            ->label(__('admin.buildings.fields.city'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('postal_code')
                            ->label(__('admin.buildings.fields.postal_code'))
                            ->required()
                            ->maxLength(20),
                        TextInput::make('country_code')
                            ->label(__('admin.buildings.fields.country_code'))
                            ->required()
                            ->minLength(2)
                            ->maxLength(2),
                    ])
                    ->columns(2),
            ]);
    }
}
