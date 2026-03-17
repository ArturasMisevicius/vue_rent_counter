<?php

namespace App\Filament\Resources\SecurityViolations\Schemas;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SecurityViolationTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('summary')->label('Summary')->wrap()->searchable(),
                TextColumn::make('severity')->label('Severity')->badge(),
                TextColumn::make('type')->label('Type')->badge(),
                TextColumn::make('ip_address')->label('IP Address'),
                TextColumn::make('organization.name')->label('Organization')->placeholder('Platform'),
                TextColumn::make('occurred_at')->label('Occurred At')->dateTime()->sortable(),
            ])
            ->defaultSort('occurred_at', 'desc');
    }
}
