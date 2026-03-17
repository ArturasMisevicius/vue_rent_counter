<?php

namespace App\Filament\Widgets\Superadmin;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class ExpiringSubscriptionsWidget extends TableWidget
{
    protected static bool $isLazy = false;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Expiring Subscriptions')
            ->description('Subscriptions that need renewal attention soonest.')
            ->poll('60s')
            ->paginated(false)
            ->query(fn (): Builder => Subscription::query()
                ->select([
                    'id',
                    'organization_id',
                    'plan_name_snapshot',
                    'status',
                    'expires_at',
                ])
                ->with([
                    'organization:id,name,slug',
                ])
                ->whereIn('status', [
                    SubscriptionStatus::ACTIVE,
                    SubscriptionStatus::TRIALING,
                ])
                ->whereNotNull('expires_at')
                ->where('expires_at', '>=', now())
                ->orderBy('expires_at')
                ->limit(5))
            ->columns([
                TextColumn::make('organization.name')
                    ->label('Organization')
                    ->weight('medium'),
                TextColumn::make('plan_name_snapshot')
                    ->label('Plan')
                    ->badge(),
                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->formatStateUsing(fn ($state): string => $state?->format('M j, Y') ?? 'No expiry'),
            ]);
    }
}
