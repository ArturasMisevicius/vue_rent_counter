<?php

namespace App\Filament\Resources\Tasks\Schemas;

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
                    ->relationship('organization', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('project_id')
                    ->relationship('project', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('title')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('status')
                    ->required()
                    ->default('pending'),
                TextInput::make('priority')
                    ->required()
                    ->default('medium'),
                Select::make('created_by_user_id')
                    ->relationship('creator', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                DatePicker::make('due_date'),
                DateTimePicker::make('completed_at'),
                TextInput::make('estimated_hours')
                    ->numeric(),
                TextInput::make('actual_hours')
                    ->required()
                    ->numeric()
                    ->default(0),
                KeyValue::make('checklist')
                    ->nullable()
                    ->columnSpanFull(),
            ]);
    }
}
