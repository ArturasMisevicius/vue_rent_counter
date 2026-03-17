<?php

namespace App\Filament\Resources\Subscriptions\Schemas;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SubscriptionTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('organization.name')->label('Organization')->searchable(),
                TextColumn::make('plan')->label('Plan')->badge()->state(fn ($state): string => $state->label()),
                TextColumn::make('status')->label('Status')->badge()->state(fn ($state): string => $state->label()),
                TextColumn::make('expires_at')->label('Expires At')->dateTime()->sortable(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->defaultSort('expires_at');
    }
}
