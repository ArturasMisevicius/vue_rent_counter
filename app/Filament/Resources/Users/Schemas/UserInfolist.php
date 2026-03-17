<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('User Overview')
                    ->schema([
                        TextEntry::make('name')->label('Name'),
                        TextEntry::make('email')->label('Email'),
                        TextEntry::make('role')->label('Role')->formatStateUsing(fn ($state): string => $state->label()),
                        TextEntry::make('status')->label('Status')->formatStateUsing(fn ($state): string => ucfirst($state->value ?? (string) $state)),
                        TextEntry::make('organization.name')->label('Organization')->default('Platform user'),
                    ])
                    ->columns(2),
            ]);
    }
}
