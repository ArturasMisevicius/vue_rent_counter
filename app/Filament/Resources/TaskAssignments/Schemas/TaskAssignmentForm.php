<?php

namespace App\Filament\Resources\TaskAssignments\Schemas;

use App\Filament\Support\Localization\LocalizedCodeLabel;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class TaskAssignmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('task_id')
                    ->label(__('superadmin.relation_resources.task_assignments.fields.task'))
                    ->relationship('task', 'title')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('user_id')
                    ->label(__('superadmin.relation_resources.task_assignments.fields.user'))
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('role')
                    ->label(__('superadmin.relation_resources.task_assignments.fields.role'))
                    ->options(LocalizedCodeLabel::options('superadmin.relation_resources.task_assignments.roles', [
                        'assignee',
                        'reviewer',
                        'observer',
                    ]))
                    ->required()
                    ->default('assignee'),
                DateTimePicker::make('assigned_at')
                    ->label(__('superadmin.relation_resources.task_assignments.fields.assigned_at'))
                    ->required(),
                DateTimePicker::make('completed_at')
                    ->label(__('superadmin.relation_resources.task_assignments.fields.completed_at')),
                Textarea::make('notes')
                    ->label(__('superadmin.relation_resources.task_assignments.fields.notes'))
                    ->columnSpanFull(),
            ]);
    }
}
