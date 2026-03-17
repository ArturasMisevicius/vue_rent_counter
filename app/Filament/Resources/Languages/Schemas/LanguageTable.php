<?php

namespace App\Filament\Resources\Languages\Schemas;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LanguageTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Code')->searchable(),
                TextColumn::make('name')->label('Name')->searchable(),
                TextColumn::make('native_name')->label('Native Name'),
                TextColumn::make('status')->label('Status')->badge()->state(fn ($state): string => ucfirst($state->value ?? (string) $state)),
                IconColumn::make('is_default')->label('Default')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->defaultSort('name');
    }
}
