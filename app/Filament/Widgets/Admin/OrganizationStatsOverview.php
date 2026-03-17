<?php

namespace App\Filament\Widgets\Admin;

use App\Filament\Support\Admin\Dashboard\AdminDashboardStats;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OrganizationStatsOverview extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '30s';

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $metrics = app(AdminDashboardStats::class)->metricsFor(auth()->user());

        return [
            Stat::make(__('dashboard.organization_metrics.total_properties'), (string) $metrics['total_properties'])
                ->color('primary'),
            Stat::make(__('dashboard.organization_metrics.active_tenants'), (string) $metrics['active_tenants'])
                ->color('success'),
            Stat::make(__('dashboard.organization_metrics.pending_invoices'), (string) $metrics['pending_invoices'])
                ->color('warning'),
            Stat::make(__('dashboard.organization_metrics.revenue_this_month'), $metrics['revenue_this_month'])
                ->color('info'),
        ];
    }
}
