<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use BackedEnum;
use App\Filament\Widgets\DashboardStatsWidget;
use App\Filament\Widgets\ExpiringSubscriptionsWidget;
use App\Filament\Widgets\OrganizationStatsWidget;
use App\Filament\Widgets\PlatformUsageWidget;
use App\Filament\Widgets\RecentActivityWidget;
use App\Filament\Widgets\SubscriptionStatsWidget;
use App\Filament\Widgets\SystemHealthWidget;
use App\Filament\Widgets\TopOrganizationsWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-home';

    protected static ?string $title = 'Dashboard';
    
    protected string $view = 'filament.pages.dashboard';

    public function getWidgets(): array
    {
        $user = auth()->user();
        
        // Show different widgets based on user role
        if ($user?->isSuperadmin()) {
            return [
                SubscriptionStatsWidget::class,
                OrganizationStatsWidget::class,
                SystemHealthWidget::class,
                ExpiringSubscriptionsWidget::class,
                RecentActivityWidget::class,
                TopOrganizationsWidget::class,
                PlatformUsageWidget::class,
            ];
        }
        
        // Default widgets for non-superadmin users
        return [
            DashboardStatsWidget::class,
        ];
    }

    public function getColumns(): array|int
    {
        $user = auth()->user();
        
        // Use 3-column grid for superadmin as per requirements
        if ($user?->isSuperadmin()) {
            return [
                'sm' => 1,
                'md' => 2,
                'lg' => 3,
            ];
        }
        
        return 2;
    }
}
