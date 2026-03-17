<?php

namespace App\Filament\Widgets\Superadmin;

use App\Enums\SubscriptionStatus;
use App\Models\Organization;
use App\Models\SecurityViolation;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformStatsOverview extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '60s';

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $revenue = (float) SubscriptionPayment::query()
            ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('amount');

        return [
            Stat::make('Total Organizations', (string) Organization::query()->count())->color('primary'),
            Stat::make('Active Subscriptions', (string) Subscription::query()
                ->where('status', SubscriptionStatus::ACTIVE)
                ->count())->color('success'),
            Stat::make('Platform Revenue This Month', 'EUR '.number_format($revenue, 2))->color('info'),
            Stat::make('Security Violations (7 Days)', (string) SecurityViolation::query()
                ->where('occurred_at', '>=', now()->subDays(7))
                ->count())->color('danger'),
        ];
    }
}
