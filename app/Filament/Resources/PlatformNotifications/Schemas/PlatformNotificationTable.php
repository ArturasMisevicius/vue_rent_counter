<?php

namespace App\Filament\Resources\PlatformNotifications\Schemas;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PlatformNotificationTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Title')->searchable(),
                TextColumn::make('severity')->label('Severity')->badge(),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('sent_at')->label('Sent At')->dateTime()->placeholder('Draft'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
