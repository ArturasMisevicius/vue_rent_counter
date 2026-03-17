<?php

namespace App\Filament\Widgets\Superadmin;

use App\Enums\SubscriptionStatus;
use App\Models\Organization;
use App\Models\SecurityViolation;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class PlatformStatsOverview extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected ?string $pollingInterval = '60s';

    protected ?string $heading = 'Platform Snapshot';

    protected ?string $description = 'Commercial, subscription, and security signals refreshed every minute.';

    protected function getStats(): array
    {
        $monthStart = now()->startOfMonth();
        $weekStart = now()->subDays(7);

        $organizationCount = Organization::query()->count();
        $activeSubscriptionCount = Subscription::query()
            ->where('status', SubscriptionStatus::ACTIVE)
            ->count();
        $monthlyRevenue = SubscriptionPayment::query()
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$monthStart, now()])
            ->sum('amount');
        $recentViolationCount = SecurityViolation::query()
            ->where('occurred_at', '>=', $weekStart)
            ->count();

        return [
            Stat::make('Total Organizations', Number::format($organizationCount))
                ->description('Organizations onboarded to the platform')
                ->descriptionIcon(Heroicon::OutlinedBuildingOffice2)
                ->color('primary'),
            Stat::make('Active Subscriptions', Number::format($activeSubscriptionCount))
                ->description('Paying organizations currently in service')
                ->descriptionIcon(Heroicon::OutlinedCreditCard)
                ->color('success'),
            Stat::make('Platform Revenue This Month', Number::currency($monthlyRevenue / 100, 'EUR'))
                ->description('Captured subscription payments since month start')
                ->descriptionIcon(Heroicon::OutlinedBanknotes)
                ->color('warning'),
            Stat::make('Security Violations (7 Days)', Number::format($recentViolationCount))
                ->description('Recent risk events requiring platform attention')
                ->descriptionIcon(Heroicon::OutlinedShieldExclamation)
                ->color('danger'),
        ];
    }
}
