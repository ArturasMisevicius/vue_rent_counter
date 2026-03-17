<?php

namespace App\Filament\Resources\Languages\Schemas;

use App\Enums\LanguageStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LanguageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Language')
                    ->schema([
                        TextInput::make('code')
                            ->label('Code')
                            ->required()
                            ->maxLength(8)
                            ->unique(ignoreRecord: true),
                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('native_name')
                            ->label('Native name')
                            ->required()
                            ->maxLength(255),
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                LanguageStatus::ACTIVE->value => 'Active',
                                LanguageStatus::INACTIVE->value => 'Inactive',
                            ])
                            ->default(LanguageStatus::ACTIVE->value)
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }
}
