<?php

namespace App\Filament\Resources\SubscriptionPayments\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class SubscriptionPaymentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('organization.name')->label(__('superadmin.organizations.singular')),
                TextEntry::make('subscription.id')->label(__('superadmin.subscriptions_resource.singular')),
                TextEntry::make('duration')
                    ->badge(),
                TextEntry::make('amount')
                    ->numeric(),
                TextEntry::make('currency'),
                TextEntry::make('paid_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('reference')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
