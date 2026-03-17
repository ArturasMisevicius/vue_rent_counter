<?php

namespace App\Filament\Resources\Subscriptions\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SubscriptionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Subscription Overview')
                    ->schema([
                        TextEntry::make('organization.name')->label('Organization'),
                        TextEntry::make('plan')->label('Plan')->formatStateUsing(fn ($state): string => $state->label()),
                        TextEntry::make('status')->label('Status')->formatStateUsing(fn ($state): string => $state->label()),
                        TextEntry::make('starts_at')->label('Starts At')->dateTime(),
                        TextEntry::make('expires_at')->label('Expires At')->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }
}
