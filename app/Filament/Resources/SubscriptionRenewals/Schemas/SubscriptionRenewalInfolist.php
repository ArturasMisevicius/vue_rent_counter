<?php

namespace App\Filament\Resources\SubscriptionRenewals\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class SubscriptionRenewalInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('subscription.id')->label(__('superadmin.subscriptions_resource.singular')),
                TextEntry::make('user.name')->label(__('superadmin.users.singular'))
                    ->placeholder('-'),
                TextEntry::make('method'),
                TextEntry::make('period'),
                TextEntry::make('old_expires_at')
                    ->dateTime(),
                TextEntry::make('new_expires_at')
                    ->dateTime(),
                TextEntry::make('duration_days')
                    ->numeric(),
                TextEntry::make('notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
