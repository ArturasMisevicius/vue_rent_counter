<?php

namespace App\Filament\Resources\Projects\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ProjectForm
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
                Select::make('property_id')
                    ->relationship('property', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('building_id')
                    ->relationship('building', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('created_by_user_id')
                    ->relationship('creator', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('assigned_to_user_id')
                    ->relationship('assignedTo', 'name')
                    ->searchable()
                    ->preload(),
                TextInput::make('name')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('type')
                    ->required()
                    ->default('maintenance'),
                TextInput::make('status')
                    ->required()
                    ->default('draft'),
                TextInput::make('priority')
                    ->required()
                    ->default('medium'),
                DatePicker::make('start_date'),
                DatePicker::make('due_date'),
                DateTimePicker::make('completed_at'),
                TextInput::make('budget')
                    ->numeric(),
                TextInput::make('actual_cost')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->prefix('$'),
                KeyValue::make('metadata')
                    ->nullable()
                    ->columnSpanFull(),
            ]);
    }
}
