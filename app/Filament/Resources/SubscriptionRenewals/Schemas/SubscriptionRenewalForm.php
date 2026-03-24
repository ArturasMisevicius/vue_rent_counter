<?php

namespace App\Filament\Resources\SubscriptionRenewals\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SubscriptionRenewalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('subscription_id')
                    ->relationship('subscription', 'id')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                TextInput::make('method')
                    ->required()
                    ->default('manual'),
                TextInput::make('period')
                    ->required()
                    ->default('annually'),
                DateTimePicker::make('old_expires_at')
                    ->required(),
                DateTimePicker::make('new_expires_at')
                    ->required(),
                TextInput::make('duration_days')
                    ->required()
                    ->numeric(),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
