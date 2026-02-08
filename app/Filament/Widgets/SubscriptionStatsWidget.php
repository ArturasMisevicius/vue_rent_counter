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
    
    // Enable lazy loading for better performance
    protected static bool $isLazy = true;

    protected function getStats(): array
    {
        $cacheService = app(\App\Services\DashboardCacheService::class);
        $stats = $cacheService->getSubscriptionStats();

        return [
            Stat::make(__('superadmin.dashboard.stats.total_subscriptions'), $stats['total'])
                ->description(__('superadmin.dashboard.stats_descriptions.total_subscriptions'))
                ->descriptionIcon('heroicon-o-credit-card')
                ->color('gray')
                ->url(route('filament.admin.resources.subscriptions.index')),

            Stat::make(__('superadmin.dashboard.stats.active_subscriptions'), $stats['active'])
                ->description(__('superadmin.dashboard.stats_descriptions.active_subscriptions'))
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success')
                ->url(route('filament.admin.resources.subscriptions.index')),

            Stat::make(__('superadmin.dashboard.stats.expired_subscriptions'), $stats['expired'])
                ->description(__('superadmin.dashboard.stats_descriptions.expired_subscriptions'))
                ->descriptionIcon('heroicon-o-x-circle')
                ->color('danger')
                ->url(route('filament.admin.resources.subscriptions.index')),

            Stat::make(__('superadmin.dashboard.stats.suspended_subscriptions'), $stats['suspended'])
                ->description(__('superadmin.dashboard.stats_descriptions.suspended_subscriptions'))
                ->descriptionIcon('heroicon-o-pause-circle')
                ->color('warning')
                ->url(route('filament.admin.resources.subscriptions.index')),

            Stat::make(__('superadmin.dashboard.stats.cancelled_subscriptions'), $stats['cancelled'])
                ->description(__('superadmin.dashboard.stats_descriptions.cancelled_subscriptions'))
                ->descriptionIcon('heroicon-o-no-symbol')
                ->color('gray')
                ->url(route('filament.admin.resources.subscriptions.index')),

            Stat::make(__('superadmin.dashboard.stats.expiring_soon'), $stats['expiring_soon'])
                ->description(__('superadmin.dashboard.stats_descriptions.expiring_soon'))
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning')
                ->url(route('filament.admin.resources.subscriptions.index')),
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }
}
