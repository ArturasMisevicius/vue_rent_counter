<?php

declare(strict_types=1);

namespace App\Filament\Clusters\SuperAdmin\Widgets;

use App\Enums\TenantStatus;
use App\Models\Organization;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

final class TenantOverviewWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $stats = Cache::remember('superadmin.tenant_overview_stats', 300, function () {
            $totalTenants = Organization::count();
            $activeTenants = Organization::where('status', TenantStatus::ACTIVE)->count();
            $suspendedTenants = Organization::where('status', TenantStatus::SUSPENDED)->count();
            $trialTenants = Organization::where('status', TenantStatus::TRIAL)->count();

            return [
                'total' => $totalTenants,
                'active' => $activeTenants,
                'suspended' => $suspendedTenants,
                'trial' => $trialTenants,
            ];
        });

        return [
            Stat::make(__('superadmin.dashboard.widgets.tenant_overview.total_tenants'), $stats['total'])
                ->description(__('superadmin.dashboard.widgets.tenant_overview.total_tenants'))
                ->descriptionIcon('heroicon-m-building-office')
                ->color('primary')
                ->chart($this->getTenantTrendData()),

            Stat::make(__('superadmin.dashboard.widgets.tenant_overview.active_tenants'), $stats['active'])
                ->description($this->getPercentage($stats['active'], $stats['total']) . '% ' . __('common.status.active'))
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make(__('superadmin.dashboard.widgets.tenant_overview.suspended_tenants'), $stats['suspended'])
                ->description($this->getPercentage($stats['suspended'], $stats['total']) . '% ' . __('common.status.suspended'))
                ->descriptionIcon('heroicon-m-pause-circle')
                ->color('warning'),

            Stat::make(__('superadmin.dashboard.widgets.tenant_overview.trial_tenants'), $stats['trial'])
                ->description($this->getPercentage($stats['trial'], $stats['total']) . '% ' . __('common.status.trial'))
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),
        ];
    }

    private function getTenantTrendData(): array
    {
        return Cache::remember('superadmin.tenant_trend_data', 3600, function () {
            $data = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i)->startOfDay();
                $count = Organization::where('created_at', '<=', $date->endOfDay())->count();
                $data[] = $count;
            }
            return $data;
        });
    }

    private function getPercentage(int $value, int $total): string
    {
        if ($total === 0) {
            return '0';
        }

        return number_format(($value / $total) * 100, 1);
    }

    public static function canView(): bool
    {
        return auth()->user()?->is_super_admin ?? false;
    }
}