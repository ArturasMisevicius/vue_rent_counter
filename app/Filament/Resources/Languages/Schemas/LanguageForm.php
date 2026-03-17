<?php

namespace App\Filament\Resources\Languages\Schemas;

use App\Enums\LanguageStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LanguageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Language Details')
                    ->schema([
                        TextInput::make('code')->label('Code')->required()->maxLength(10),
                        TextInput::make('name')->label('Name')->required()->maxLength(255),
                        TextInput::make('native_name')->label('Native Name')->required()->maxLength(255),
                        Select::make('status')
                            ->label('Status')
                            ->options(LanguageStatus::options())
                            ->required(),
                        Toggle::make('is_default')->label('Default Language'),
                    ])
                    ->columns(2),
            ]);
    }
}
