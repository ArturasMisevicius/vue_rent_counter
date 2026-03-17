<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Name')->searchable()->sortable(),
                TextColumn::make('email')->label('Email')->searchable()->sortable(),
                TextColumn::make('role')->label('Role')->badge()->state(fn ($state): string => $state->label()),
                TextColumn::make('status')->label('Status')->badge()->state(fn ($state): string => ucfirst($state->value ?? (string) $state)),
                TextColumn::make('organization.name')->label('Organization')->placeholder('Platform user'),
                TextColumn::make('last_login_at')->label('Last Login')->dateTime()->toggleable(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->defaultSort('name');
    }
}
