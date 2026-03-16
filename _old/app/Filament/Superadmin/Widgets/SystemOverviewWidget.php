<?php

declare(strict_types=1);

namespace App\Filament\Superadmin\Widgets;

use App\Models\Subscription;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

/**
 * System Overview Widget
 *
 * Provides key system metrics for superadmin dashboard including
 * user counts and subscription status.
 */
final class SystemOverviewWidget extends BaseWidget
{
    /**
     * Widget column span configuration.
     *
     * @var string
     */
    protected int|string|array $columnSpan = 'full';

    /**
     * Widget sort order.
     */
    protected static ?int $sort = 1;

    /**
     * Get the stats for the widget.
     *
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        return [
            Stat::make(__('app.labels.total_users'), $this->getTotalUsers())
                ->description(__('app.labels.system_wide_users'))
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make(__('app.labels.active_subscriptions'), $this->getActiveSubscriptions())
                ->description(__('app.labels.currently_active'))
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make(__('app.labels.total_organizations'), $this->getTotalOrganizations())
                ->description(__('app.labels.registered_organizations'))
                ->descriptionIcon('heroicon-m-building-office')
                ->color('info'),
        ];
    }

    /**
     * Get total number of users across all organizations.
     */
    private function getTotalUsers(): int
    {
        return Cache::remember(
            'superadmin.stats.total_users',
            300, // 5 minutes
            fn () => User::count()
        );
    }

    /**
     * Get number of active subscriptions.
     */
    private function getActiveSubscriptions(): int
    {
        return Cache::remember(
            'superadmin.stats.active_subscriptions',
            300, // 5 minutes
            fn () => Subscription::where('status', 'active')->count()
        );
    }

    /**
     * Get total number of organizations.
     */
    private function getTotalOrganizations(): int
    {
        return Cache::remember(
            'superadmin.stats.total_organizations',
            300, // 5 minutes
            function () {
                // For now, return a placeholder count
                // This will be replaced with actual organization model when implemented
                return 0;
            }
        );
    }
}
