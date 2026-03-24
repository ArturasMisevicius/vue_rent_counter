<?php

namespace App\Filament\Resources\PropertyAssignments\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PropertyAssignmentForm
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
                    ->preload()
                    ->required(),
                Select::make('tenant_user_id')
                    ->relationship('tenant', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('unit_area_sqm')
                    ->numeric(),
                DateTimePicker::make('assigned_at')
                    ->required(),
                DateTimePicker::make('unassigned_at'),
            ]);
    }
}
