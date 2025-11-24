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
            Stat::make(__('superadmin.dashboard.stats.total_subscriptions'), $stats['total'])
                ->description(__('superadmin.dashboard.stats_descriptions.total_subscriptions'))
                ->descriptionIcon('heroicon-o-credit-card')
                ->color('gray'),

            Stat::make(__('superadmin.dashboard.stats.active_subscriptions'), $stats['active'])
                ->description(__('superadmin.dashboard.stats_descriptions.active_subscriptions'))
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make(__('superadmin.dashboard.stats.expired_subscriptions'), $stats['expired'])
                ->description(__('superadmin.dashboard.stats_descriptions.expired_subscriptions'))
                ->descriptionIcon('heroicon-o-x-circle')
                ->color('danger'),

            Stat::make(__('superadmin.dashboard.stats.suspended_subscriptions'), $stats['suspended'])
                ->description(__('superadmin.dashboard.stats_descriptions.suspended_subscriptions'))
                ->descriptionIcon('heroicon-o-pause-circle')
                ->color('warning'),

            Stat::make(__('superadmin.dashboard.stats.cancelled_subscriptions'), $stats['cancelled'])
                ->description(__('superadmin.dashboard.stats_descriptions.cancelled_subscriptions'))
                ->descriptionIcon('heroicon-o-no-symbol')
                ->color('gray'),
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }
}
