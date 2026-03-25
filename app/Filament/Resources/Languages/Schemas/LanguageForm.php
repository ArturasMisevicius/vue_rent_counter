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
                Section::make(__('superadmin.languages_resource.sections.details'))
                    ->schema([
                        TextInput::make('code')->label(__('superadmin.languages_resource.fields.code'))->required()->maxLength(10),
                        TextInput::make('name')->label(__('superadmin.languages_resource.fields.name'))->required()->maxLength(255),
                        TextInput::make('native_name')->label(__('superadmin.languages_resource.fields.native_name'))->required()->maxLength(255),
                        Select::make('status')
                            ->label(__('superadmin.languages_resource.fields.status'))
                            ->options(LanguageStatus::options())
                            ->required(),
                        Toggle::make('is_default')->label(__('superadmin.languages_resource.columns.default')),
                    ])
                    ->columns(2),
            ]);
    }
}
