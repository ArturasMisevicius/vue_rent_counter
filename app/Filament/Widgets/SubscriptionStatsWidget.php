<?php

namespace App\Filament\Widgets;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class SubscriptionStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Cache for 60 seconds as per requirements
        $stats = Cache::remember('superadmin.subscription_stats', 60, function () {
            $total = Subscription::count();
            $active = Subscription::where('status', SubscriptionStatus::ACTIVE->value)->count();
            $expired = Subscription::where('status', SubscriptionStatus::EXPIRED->value)->count();
            $suspended = Subscription::where('status', SubscriptionStatus::SUSPENDED->value)->count();
            $cancelled = Subscription::where('status', SubscriptionStatus::CANCELLED->value)->count();

            return compact('total', 'active', 'expired', 'suspended', 'cancelled');
        });

        return [
            Stat::make('Total Subscriptions', $stats['total'])
                ->description('All subscriptions in the system')
                ->descriptionIcon('heroicon-o-credit-card')
                ->color('gray'),

            Stat::make('Active Subscriptions', $stats['active'])
                ->description('Currently active')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Expired Subscriptions', $stats['expired'])
                ->description('Require renewal')
                ->descriptionIcon('heroicon-o-x-circle')
                ->color('danger'),

            Stat::make('Suspended Subscriptions', $stats['suspended'])
                ->description('Temporarily suspended')
                ->descriptionIcon('heroicon-o-pause-circle')
                ->color('warning'),

            Stat::make('Cancelled Subscriptions', $stats['cancelled'])
                ->description('Permanently cancelled')
                ->descriptionIcon('heroicon-o-no-symbol')
                ->color('gray'),
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }
}
