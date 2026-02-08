<?php

namespace App\Filament\Widgets;

use App\Models\Organization;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class OrganizationStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    
    // Enable lazy loading for better performance
    protected static bool $isLazy = true;

    protected function getStats(): array
    {
        $cacheService = app(\App\Services\DashboardCacheService::class);
        $stats = $cacheService->getOrganizationStats();

        return [
            Stat::make(__('superadmin.dashboard.organizations_widget.total'), $stats['total'])
                ->description(__('superadmin.dashboard.stats_descriptions.total_organizations'))
                ->descriptionIcon('heroicon-o-building-office-2')
                ->color('gray')
                ->url(route('filament.admin.resources.organizations.index')),

            Stat::make(__('superadmin.dashboard.organizations_widget.active'), $stats['active'])
                ->description(__('superadmin.dashboard.stats_descriptions.active_organizations'))
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success')
                ->url(route('filament.admin.resources.organizations.index')),

            Stat::make(__('superadmin.dashboard.organizations_widget.inactive'), $stats['inactive'])
                ->description(__('superadmin.dashboard.stats_descriptions.inactive_organizations'))
                ->descriptionIcon('heroicon-o-x-circle')
                ->color('danger')
                ->url(route('filament.admin.resources.organizations.index')),

            Stat::make(__('superadmin.dashboard.organizations_widget.new_this_month'), $stats['new_this_month'])
                ->description($stats['growth_rate'] >= 0
                    ? __('superadmin.dashboard.organizations_widget.growth_up', ['value' => $stats['growth_rate']])
                    : __('superadmin.dashboard.organizations_widget.growth_down', ['value' => abs($stats['growth_rate'])]))
                ->descriptionIcon($stats['growth_rate'] >= 0
                    ? 'heroicon-o-arrow-trending-up'
                    : 'heroicon-o-arrow-trending-down')
                ->color($stats['growth_rate'] >= 0 ? 'success' : 'danger')
                ->url(route('filament.admin.resources.organizations.index')),
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }
}
