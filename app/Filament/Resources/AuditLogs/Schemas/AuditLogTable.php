<?php

namespace App\Filament\Resources\AuditLogs\Schemas;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AuditLogTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('description')->label('Description')->searchable()->wrap(),
                TextColumn::make('actor.name')->label('Actor')->placeholder('System'),
                TextColumn::make('organization.name')->label('Organization')->placeholder('Platform'),
                TextColumn::make('action')->label('Action')->badge(),
                TextColumn::make('occurred_at')->label('Occurred At')->dateTime()->sortable(),
            ])
            ->defaultSort('occurred_at', 'desc');
    }
}
