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
                TextColumn::make('code')->label(__('superadmin.languages_resource.columns.code'))->searchable(),
                TextColumn::make('name')->label(__('superadmin.languages_resource.columns.name'))->searchable(),
                TextColumn::make('native_name')->label(__('superadmin.languages_resource.columns.native_name')),
                TextColumn::make('status')->label(__('superadmin.languages_resource.columns.status'))->badge(),
                IconColumn::make('is_default')->label(__('superadmin.languages_resource.columns.default'))->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->defaultSort('name');
    }
}
