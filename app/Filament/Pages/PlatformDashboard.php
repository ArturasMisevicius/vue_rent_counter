<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Superadmin\ExpiringSubscriptionsWidget;
use App\Filament\Widgets\Superadmin\PlatformStatsOverview;
use App\Filament\Widgets\Superadmin\RecentlyCreatedOrganizationsWidget;
use App\Filament\Widgets\Superadmin\RecentSecurityViolationsWidget;
use App\Filament\Widgets\Superadmin\RevenueByPlanChart;
use Filament\Pages\Page;

class PlatformDashboard extends Page
{
    protected static ?string $slug = 'platform-dashboard';

    protected static ?string $navigationLabel = null;

    protected string $view = 'filament.pages.platform-dashboard';

    public function getTitle(): string
    {
        return __('dashboard.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('dashboard.title');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PlatformStatsOverview::class,
            RevenueByPlanChart::class,
            ExpiringSubscriptionsWidget::class,
            RecentSecurityViolationsWidget::class,
            RecentlyCreatedOrganizationsWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return [
            'md' => 2,
            'xl' => 3,
        ];
    }
}
