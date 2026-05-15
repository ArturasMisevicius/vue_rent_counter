<?php

namespace App\Filament\Resources\TimeEntries\Schemas;

use App\Filament\Support\Localization\DatabaseContentLocalizer;
use App\Models\TimeEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class TimeEntryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('user.name')->label(__('superadmin.relation_resources.time_entries.fields.user')),
                TextEntry::make('task.title')
                    ->label(__('superadmin.relation_resources.time_entries.fields.task'))
                    ->state(fn (TimeEntry $record): string => app(DatabaseContentLocalizer::class)->taskTitle($record->task?->title)),
                TextEntry::make('assignment.id')
                    ->label(__('superadmin.relation_resources.time_entries.fields.assignment'))
                    ->placeholder('-'),
                TextEntry::make('hours')
                    ->label(__('superadmin.relation_resources.time_entries.fields.hours'))
                    ->numeric(),
                TextEntry::make('description')
                    ->label(__('superadmin.relation_resources.time_entries.fields.description'))
                    ->state(fn (TimeEntry $record): ?string => app(DatabaseContentLocalizer::class)->timeEntryDescription($record->description))
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('metadata')
                    ->label(__('superadmin.relation_resources.time_entries.fields.metadata'))
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('logged_at')
                    ->label(__('superadmin.relation_resources.time_entries.fields.logged_at'))
                    ->dateTime(),
                TextEntry::make('created_at')
                    ->label(__('superadmin.relation_resources.shared.fields.created_at'))
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->label(__('superadmin.relation_resources.shared.fields.updated_at'))
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
