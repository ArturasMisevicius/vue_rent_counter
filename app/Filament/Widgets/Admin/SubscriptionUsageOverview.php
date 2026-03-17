<?php

namespace App\Filament\Widgets\Admin;

use App\Filament\Support\Admin\Dashboard\AdminDashboardStats;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SubscriptionUsageOverview extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '30s';

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Subscription Usage';

    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    protected function getHeading(): ?string
    {
        return __('dashboard.organization_usage.heading');
    }

    protected function getStats(): array
    {
        return collect(app(AdminDashboardStats::class)->subscriptionUsageFor(auth()->user()))
            ->map(fn (array $item): Stat => Stat::make($item['label'], $item['value'])->color('gray'))
            ->all();
    }
}
