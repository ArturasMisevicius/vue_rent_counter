<?php

namespace App\Filament\Resources\SubscriptionPayments\Schemas;

use App\Enums\SubscriptionDuration;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SubscriptionPaymentForm
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
                Select::make('subscription_id')
                    ->relationship('subscription', 'id')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('duration')
                    ->options(SubscriptionDuration::options())
                    ->default('monthly')
                    ->required(),
                TextInput::make('amount')
                    ->required()
                    ->numeric(),
                TextInput::make('currency')
                    ->required()
                    ->default('EUR'),
                DateTimePicker::make('paid_at'),
                TextInput::make('reference'),
            ]);
    }
}
