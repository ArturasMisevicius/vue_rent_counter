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
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('task_id')
                    ->relationship('task', 'title')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('assignment_id')
                    ->relationship('assignment', 'id')
                    ->searchable()
                    ->preload(),
                TextInput::make('hours')
                    ->required()
                    ->numeric(),
                Textarea::make('description')
                    ->columnSpanFull(),
                KeyValue::make('metadata')
                    ->nullable()
                    ->columnSpanFull(),
                DateTimePicker::make('logged_at')
                    ->required(),
            ]);
    }
}
