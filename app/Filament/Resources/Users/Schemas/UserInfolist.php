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
                Section::make('Account overview')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Name'),
                        TextEntry::make('email')
                            ->label('Email'),
                        TextEntry::make('role')
                            ->label('Role')
                            ->badge()
                            ->formatStateUsing(fn ($state): string => $state->label()),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn ($state): string => str($state->value)->headline()->toString()),
                        TextEntry::make('organization.name')
                            ->label('Organization')
                            ->placeholder('Platform'),
                        TextEntry::make('last_login_at')
                            ->label('Last login')
                            ->since()
                            ->placeholder('Never'),
                    ])
                    ->columns(2),
            ]);
    }
}
