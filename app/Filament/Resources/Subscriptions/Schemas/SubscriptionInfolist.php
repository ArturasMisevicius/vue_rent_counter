<?php

namespace App\Filament\Resources\Subscriptions\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SubscriptionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Subscription overview')
                    ->schema([
                        TextEntry::make('organization.name')
                            ->label('Organization'),
                        TextEntry::make('plan_name_snapshot')
                            ->label('Plan')
                            ->badge(),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn ($state): string => str($state->value)->headline()->toString()),
                        TextEntry::make('starts_at')
                            ->label('Starts')
                            ->dateTime(),
                        TextEntry::make('expires_at')
                            ->label('Expires')
                            ->dateTime(),
                        IconEntry::make('is_trial')
                            ->label('Trial')
                            ->boolean(),
                        TextEntry::make('limits_snapshot')
                            ->label('Limits snapshot')
                            ->formatStateUsing(fn (array $state): string => collect($state)
                                ->map(fn (int $value, string $key): string => "{$key}: {$value}")
                                ->implode(', ')),
                    ])
                    ->columns(2),
            ]);
    }
}
