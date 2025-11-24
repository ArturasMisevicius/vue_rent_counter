<?php

namespace App\Filament\Widgets;

use App\Models\Organization;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class OrganizationStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        // Cache for 5 minutes as per requirements
        $stats = Cache::remember('superadmin.organization_stats', 300, function () {
            $total = Organization::count();
            $active = Organization::where('is_active', true)->count();
            $inactive = Organization::where('is_active', false)->count();
            
            // Calculate growth (new orgs in last 30 days)
            $lastMonth = Organization::where('created_at', '>=', now()->subDays(30))->count();
            $previousMonth = Organization::whereBetween('created_at', [
                now()->subDays(60),
                now()->subDays(30)
            ])->count();
            
            $growthRate = $previousMonth > 0 
                ? round((($lastMonth - $previousMonth) / $previousMonth) * 100, 1)
                : 0;

            return compact('total', 'active', 'inactive', 'lastMonth', 'growthRate');
        });

        return [
            Stat::make(__('superadmin.dashboard.organizations_widget.total'), $stats['total'])
                ->description(__('superadmin.dashboard.stats_descriptions.total_organizations'))
                ->descriptionIcon('heroicon-o-building-office-2')
                ->color('gray'),

            Stat::make(__('superadmin.dashboard.organizations_widget.active'), $stats['active'])
                ->description(__('superadmin.dashboard.stats_descriptions.active_organizations'))
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make(__('superadmin.dashboard.organizations_widget.inactive'), $stats['inactive'])
                ->description(__('superadmin.dashboard.stats_descriptions.inactive_organizations'))
                ->descriptionIcon('heroicon-o-x-circle')
                ->color('danger'),

            Stat::make(__('superadmin.dashboard.organizations_widget.new_this_month'), $stats['lastMonth'])
                ->description($stats['growthRate'] >= 0 
                    ? __('superadmin.dashboard.organizations_widget.growth_up', ['value' => $stats['growthRate']]) 
                    : __('superadmin.dashboard.organizations_widget.growth_down', ['value' => abs($stats['growthRate'])]))
                ->descriptionIcon($stats['growthRate'] >= 0 
                    ? 'heroicon-o-arrow-trending-up' 
                    : 'heroicon-o-arrow-trending-down')
                ->color($stats['growthRate'] >= 0 ? 'success' : 'danger'),
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }
}
