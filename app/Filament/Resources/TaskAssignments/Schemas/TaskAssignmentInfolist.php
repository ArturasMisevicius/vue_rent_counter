<?php

namespace App\Filament\Resources\TaskAssignments\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class TaskAssignmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('task.title')
                    ->label(__('superadmin.audit_logs.record_types.task')),
                TextEntry::make('user.name')->label(__('superadmin.users.singular')),
                TextEntry::make('role'),
                TextEntry::make('assigned_at')
                    ->dateTime(),
                TextEntry::make('completed_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
