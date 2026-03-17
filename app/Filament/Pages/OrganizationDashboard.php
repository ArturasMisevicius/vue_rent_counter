<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Admin\OrganizationStatsOverview;
use App\Filament\Widgets\Admin\RecentInvoicesWidget;
use App\Filament\Widgets\Admin\SubscriptionUsageOverview;
use App\Filament\Widgets\Admin\UpcomingReadingDeadlinesWidget;
use Filament\Pages\Page;

class OrganizationDashboard extends Page
{
    protected static ?string $slug = 'organization-dashboard';

    protected static ?string $navigationLabel = null;

    protected string $view = 'filament.pages.organization-dashboard';

    public function getTitle(): string
    {
        return __('dashboard.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('dashboard.title');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            OrganizationStatsOverview::class,
            SubscriptionUsageOverview::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }

    protected function getFooterWidgets(): array
    {
        return [
            RecentInvoicesWidget::class,
            UpcomingReadingDeadlinesWidget::class,
        ];
    }

    public function getFooterWidgetsColumns(): int|array
    {
        return [
            'md' => 2,
        ];
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return ($user?->isAdmin() || $user?->isManager()) ?? false;
    }
}
