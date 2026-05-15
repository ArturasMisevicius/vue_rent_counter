<?php

namespace App\Filament\Resources\SubscriptionRenewals\Schemas;

use App\Filament\Support\Localization\DatabaseContentLocalizer;
use App\Models\SubscriptionRenewal;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class SubscriptionRenewalInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('subscription.id')->label(__('superadmin.relation_resources.subscription_renewals.fields.subscription')),
                TextEntry::make('user.name')->label(__('superadmin.relation_resources.subscription_renewals.fields.user'))
                    ->placeholder('-'),
                TextEntry::make('method')
                    ->label(__('superadmin.relation_resources.subscription_renewals.fields.method'))
                    ->state(fn (SubscriptionRenewal $record): string => $record->methodLabel()),
                TextEntry::make('period')
                    ->label(__('superadmin.relation_resources.subscription_renewals.fields.period'))
                    ->state(fn (SubscriptionRenewal $record): string => $record->periodLabel()),
                TextEntry::make('old_expires_at')
                    ->label(__('superadmin.relation_resources.subscription_renewals.fields.old_expires_at'))
                    ->dateTime(),
                TextEntry::make('new_expires_at')
                    ->label(__('superadmin.relation_resources.subscription_renewals.fields.new_expires_at'))
                    ->dateTime(),
                TextEntry::make('duration_days')
                    ->label(__('superadmin.relation_resources.subscription_renewals.fields.duration_days'))
                    ->numeric(),
                TextEntry::make('notes')
                    ->label(__('superadmin.relation_resources.subscription_renewals.fields.notes'))
                    ->state(fn (SubscriptionRenewal $record): ?string => app(DatabaseContentLocalizer::class)->subscriptionRenewalNotes($record->notes))
                    ->placeholder('-')
                    ->columnSpanFull(),
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
