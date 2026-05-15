<?php

namespace App\Filament\Resources\Tasks\Schemas;

use App\Filament\Support\Localization\DatabaseContentLocalizer;
use App\Models\Task;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class TaskInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('organization.name')->label(__('superadmin.organizations.singular')),
                TextEntry::make('project.name')
                    ->label(__('superadmin.relation_resources.tasks.fields.project'))
                    ->state(fn (Task $record): string => app(DatabaseContentLocalizer::class)->projectName($record->project?->name)),
                TextEntry::make('title')
                    ->label(__('superadmin.relation_resources.tasks.fields.title'))
                    ->state(fn (Task $record): string => app(DatabaseContentLocalizer::class)->taskTitle($record->title)),
                TextEntry::make('description')
                    ->label(__('superadmin.relation_resources.tasks.fields.description'))
                    ->state(fn (Task $record): ?string => app(DatabaseContentLocalizer::class)->taskDescription($record->description))
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('status')
                    ->label(__('superadmin.relation_resources.tasks.fields.status'))
                    ->state(fn (Task $record): string => $record->statusLabel()),
                TextEntry::make('priority')
                    ->label(__('superadmin.relation_resources.tasks.fields.priority'))
                    ->state(fn (Task $record): string => $record->priorityLabel()),
                TextEntry::make('created_by_user_id')
                    ->label(__('superadmin.relation_resources.tasks.fields.creator'))
                    ->numeric(),
                TextEntry::make('due_date')
                    ->label(__('superadmin.relation_resources.tasks.fields.due_date'))
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('completed_at')
                    ->label(__('superadmin.relation_resources.tasks.fields.completed_at'))
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('estimated_hours')
                    ->label(__('superadmin.relation_resources.tasks.fields.estimated_hours'))
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('actual_hours')
                    ->label(__('superadmin.relation_resources.tasks.fields.actual_hours'))
                    ->numeric(),
                TextEntry::make('checklist')
                    ->label(__('superadmin.relation_resources.tasks.fields.checklist'))
                    ->placeholder('-')
                    ->columnSpanFull(),
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
