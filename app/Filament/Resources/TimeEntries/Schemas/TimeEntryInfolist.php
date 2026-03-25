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
                TextEntry::make('user.name')->label(__('superadmin.users.singular')),
                TextEntry::make('task.title')
                    ->label(__('superadmin.audit_logs.record_types.task')),
                TextEntry::make('assignment.id')
                    ->label(__('superadmin.audit_logs.record_types.task_assignment'))
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
