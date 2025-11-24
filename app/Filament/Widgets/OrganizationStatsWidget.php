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
            Stat::make('Total Organizations', $stats['total'])
                ->description('All organizations in the system')
                ->descriptionIcon('heroicon-o-building-office-2')
                ->color('gray'),

            Stat::make('Active Organizations', $stats['active'])
                ->description('Currently active')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Inactive Organizations', $stats['inactive'])
                ->description('Suspended or inactive')
                ->descriptionIcon('heroicon-o-x-circle')
                ->color('danger'),

            Stat::make('New This Month', $stats['lastMonth'])
                ->description($stats['growthRate'] >= 0 
                    ? "↑ {$stats['growthRate']}% from last month" 
                    : "↓ " . abs($stats['growthRate']) . "% from last month")
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
