<?php

namespace App\Filament\Resources\PlatformNotifications\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PlatformNotificationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Notification Overview')
                    ->schema([
                        TextEntry::make('title')->label('Title'),
                        TextEntry::make('severity')->label('Severity')->formatStateUsing(fn ($state): string => ucfirst($state->value ?? (string) $state)),
                        TextEntry::make('status')->label('Status')->formatStateUsing(fn ($state): string => ucfirst($state->value ?? (string) $state)),
                        TextEntry::make('body')->label('Message')->html(),
                    ])
                    ->columns(2),
            ]);
    }
}
