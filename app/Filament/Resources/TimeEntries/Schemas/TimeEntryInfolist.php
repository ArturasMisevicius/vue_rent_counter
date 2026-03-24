<?php

namespace App\Filament\Resources\TimeEntries\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class TimeEntryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('user.name')
                    ->label('User'),
                TextEntry::make('task.title')
                    ->label('Task'),
                TextEntry::make('assignment.id')
                    ->label('Assignment')
                    ->placeholder('-'),
                TextEntry::make('hours')
                    ->numeric(),
                TextEntry::make('description')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('metadata')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('logged_at')
                    ->dateTime(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
