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
            Stat::make(__('dashboard.platform_metrics.total_organizations'), (string) Organization::query()->count())->color('primary'),
            Stat::make(__('dashboard.platform_metrics.active_subscriptions'), (string) Subscription::query()
                ->where('status', SubscriptionStatus::ACTIVE)
                ->count())->color('success'),
            Stat::make(__('dashboard.platform_metrics.platform_revenue_this_month'), $this->formatCurrency($revenue))->color('info'),
            Stat::make(__('dashboard.platform_metrics.security_violations_last_7_days'), (string) SecurityViolation::query()
                ->where('occurred_at', '>=', now()->subDays(7))
                ->count())->color('danger'),
        ];
    }

    private function formatCurrency(float $amount, string $currency = 'EUR'): string
    {
        $formatter = new \NumberFormatter(app()->getLocale(), \NumberFormatter::CURRENCY);

        return (string) $formatter->formatCurrency($amount, $currency);
    }
}
