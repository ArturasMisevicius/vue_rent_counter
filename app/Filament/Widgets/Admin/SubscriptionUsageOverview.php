<?php

namespace App\Filament\Widgets\Admin;

use App\Filament\Support\Admin\Dashboard\AdminDashboardStats;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class SubscriptionUsageOverview extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '30s';

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = null;

    public static function canView(): bool
    {
        return self::currentUser()?->isAdmin() ?? false;
    }

    protected function getHeading(): ?string
    {
        return __('dashboard.organization_usage.heading');
    }

    protected function getStats(): array
    {
        return collect(app(AdminDashboardStats::class)->subscriptionUsageFor(self::currentUser()))
            ->map(fn (array $item): Stat => Stat::make($item['label'], $item['value'])->color('gray'))
            ->all();
    }

    private static function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }
}
