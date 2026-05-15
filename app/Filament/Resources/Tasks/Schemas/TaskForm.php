<?php

namespace App\Filament\Resources\Tasks\Schemas;

use App\Filament\Support\Localization\DatabaseContentLocalizer;
use App\Filament\Support\Localization\LocalizedCodeLabel;
use App\Models\Project;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('organization_id')
                    ->label(__('superadmin.organizations.singular'))
                    ->relationship('organization', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('project_id')
                    ->label(__('superadmin.relation_resources.tasks.fields.project'))
                    ->relationship('project', 'name')
                    ->getOptionLabelFromRecordUsing(fn (Project $record): string => app(DatabaseContentLocalizer::class)->projectName($record->name))
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('title')
                    ->label(__('superadmin.relation_resources.tasks.fields.title'))
                    ->required(),
                Textarea::make('description')
                    ->label(__('superadmin.relation_resources.tasks.fields.description'))
                    ->columnSpanFull(),
                Select::make('status')
                    ->label(__('superadmin.relation_resources.tasks.fields.status'))
                    ->options(LocalizedCodeLabel::options('superadmin.relation_resources.tasks.statuses', [
                        'pending',
                        'in_progress',
                        'review',
                        'completed',
                        'cancelled',
                    ]))
                    ->required()
                    ->default('pending'),
                Select::make('priority')
                    ->label(__('superadmin.relation_resources.tasks.fields.priority'))
                    ->options(LocalizedCodeLabel::options('superadmin.relation_resources.tasks.priorities', [
                        'low',
                        'medium',
                        'high',
                        'urgent',
                    ]))
                    ->required()
                    ->default('medium'),
                Select::make('created_by_user_id')
                    ->label(__('superadmin.relation_resources.tasks.fields.creator'))
                    ->relationship('creator', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                DatePicker::make('due_date')
                    ->label(__('superadmin.relation_resources.tasks.fields.due_date')),
                DateTimePicker::make('completed_at')
                    ->label(__('superadmin.relation_resources.tasks.fields.completed_at')),
                TextInput::make('estimated_hours')
                    ->label(__('superadmin.relation_resources.tasks.fields.estimated_hours'))
                    ->numeric(),
                TextInput::make('actual_hours')
                    ->label(__('superadmin.relation_resources.tasks.fields.actual_hours'))
                    ->required()
                    ->numeric()
                    ->default(0),
                KeyValue::make('checklist')
                    ->label(__('superadmin.relation_resources.tasks.fields.checklist'))
                    ->nullable()
                    ->columnSpanFull(),
            ]);
    }
}
