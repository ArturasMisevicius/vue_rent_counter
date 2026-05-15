<?php

namespace App\Filament\Resources\TimeEntries\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TimeEntryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label(__('superadmin.relation_resources.time_entries.fields.user'))
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('task_id')
                    ->label(__('superadmin.relation_resources.time_entries.fields.task'))
                    ->relationship('task', 'title')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('assignment_id')
                    ->label(__('superadmin.relation_resources.time_entries.fields.assignment'))
                    ->relationship('assignment', 'id')
                    ->searchable()
                    ->preload(),
                TextInput::make('hours')
                    ->label(__('superadmin.relation_resources.time_entries.fields.hours'))
                    ->required()
                    ->numeric(),
                Textarea::make('description')
                    ->label(__('superadmin.relation_resources.time_entries.fields.description'))
                    ->columnSpanFull(),
                KeyValue::make('metadata')
                    ->label(__('superadmin.relation_resources.time_entries.fields.metadata'))
                    ->nullable()
                    ->columnSpanFull(),
                DateTimePicker::make('logged_at')
                    ->label(__('superadmin.relation_resources.time_entries.fields.logged_at'))
                    ->required(),
            ]);
    }
}
