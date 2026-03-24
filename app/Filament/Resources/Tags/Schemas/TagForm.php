<?php

namespace App\Filament\Resources\Tags\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TagForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('organization_id')
                    ->relationship('organization', 'name')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('color'),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('type'),
                Toggle::make('is_system')
                    ->required(),
            ]);
    }
}
