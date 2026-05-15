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
                    ->label(__('superadmin.relation_resources.subscription_payments.fields.organization'))
                    ->relationship('organization', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('subscription_id')
                    ->label(__('superadmin.relation_resources.subscription_payments.fields.subscription'))
                    ->relationship('subscription', 'id')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('duration')
                    ->label(__('superadmin.relation_resources.subscription_payments.fields.duration'))
                    ->options(SubscriptionDuration::options())
                    ->default('monthly')
                    ->required(),
                TextInput::make('amount')
                    ->label(__('superadmin.relation_resources.subscription_payments.fields.amount'))
                    ->required()
                    ->numeric(),
                TextInput::make('currency')
                    ->label(__('superadmin.relation_resources.subscription_payments.fields.currency'))
                    ->required()
                    ->default('EUR'),
                DateTimePicker::make('paid_at')
                    ->label(__('superadmin.relation_resources.subscription_payments.fields.paid_at')),
                TextInput::make('reference')
                    ->label(__('superadmin.relation_resources.subscription_payments.fields.reference')),
            ]);
    }
}
