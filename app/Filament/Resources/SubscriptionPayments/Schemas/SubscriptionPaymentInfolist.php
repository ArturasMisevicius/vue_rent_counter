<?php

namespace App\Filament\Resources\SubscriptionPayments\Schemas;

use App\Models\SubscriptionPayment;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class SubscriptionPaymentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('organization.name')->label(__('superadmin.relation_resources.subscription_payments.fields.organization')),
                TextEntry::make('subscription.id')->label(__('superadmin.relation_resources.subscription_payments.fields.subscription')),
                TextEntry::make('duration')
                    ->label(__('superadmin.relation_resources.subscription_payments.fields.duration'))
                    ->state(fn (SubscriptionPayment $record): string => $record->durationLabel())
                    ->badge(),
                TextEntry::make('amount')
                    ->label(__('superadmin.relation_resources.subscription_payments.fields.amount'))
                    ->numeric(),
                TextEntry::make('currency')
                    ->label(__('superadmin.relation_resources.subscription_payments.fields.currency')),
                TextEntry::make('paid_at')
                    ->label(__('superadmin.relation_resources.subscription_payments.fields.paid_at'))
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('reference')
                    ->label(__('superadmin.relation_resources.subscription_payments.fields.reference'))
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->label(__('superadmin.relation_resources.shared.fields.created_at'))
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->label(__('superadmin.relation_resources.shared.fields.updated_at'))
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
