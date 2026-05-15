<?php

namespace App\Filament\Resources\TaskAssignments\Schemas;

use App\Filament\Support\Localization\DatabaseContentLocalizer;
use App\Models\TaskAssignment;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class TaskAssignmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('task.title')
                    ->label(__('superadmin.relation_resources.task_assignments.fields.task'))
                    ->state(fn (TaskAssignment $record): string => app(DatabaseContentLocalizer::class)->taskTitle($record->task?->title)),
                TextEntry::make('user.name')->label(__('superadmin.relation_resources.task_assignments.fields.user')),
                TextEntry::make('role')
                    ->label(__('superadmin.relation_resources.task_assignments.fields.role'))
                    ->state(fn (TaskAssignment $record): string => $record->roleLabel()),
                TextEntry::make('assigned_at')
                    ->label(__('superadmin.relation_resources.task_assignments.fields.assigned_at'))
                    ->dateTime(),
                TextEntry::make('completed_at')
                    ->label(__('superadmin.relation_resources.task_assignments.fields.completed_at'))
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('notes')
                    ->label(__('superadmin.relation_resources.task_assignments.fields.notes'))
                    ->state(fn (TaskAssignment $record): ?string => app(DatabaseContentLocalizer::class)->taskAssignmentNotes($record->notes))
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
