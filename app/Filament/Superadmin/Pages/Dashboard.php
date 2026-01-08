<?php

declare(strict_types=1);

namespace App\Filament\Superadmin\Pages;

use App\Filament\Superadmin\Widgets\RecentUsersWidget;
use App\Filament\Superadmin\Widgets\SystemOverviewWidget;
use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets;

/**
 * Superadmin Dashboard Page
 * 
 * Main dashboard for the superadmin panel.
 * Extends Filament's Dashboard to properly register routes.
 */
final class Dashboard extends BaseDashboard
{
    /**
     * Navigation icon for the dashboard.
     */
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-home';

    /**
     * Get the page title.
     */
    public function getTitle(): string
    {
        return __('superadmin.dashboard.title');
    }

    /**
     * Get the heading for the page.
     */
    public function getHeading(): string
    {
        return __('superadmin.dashboard.title');
    }

    /**
     * Get the subheading for the page.
     */
    public function getSubheading(): ?string
    {
        return __('superadmin.dashboard.subtitle');
    }

    /**
     * Get the widgets for the dashboard.
     * 
     * @return array<class-string<\Filament\Widgets\Widget>>
     */
    public function getWidgets(): array
    {
        return [
            Widgets\AccountWidget::class,
            SystemOverviewWidget::class,
            RecentUsersWidget::class,
        ];
    }

    /**
     * Get the number of columns for the widgets.
     */
    public function getColumns(): int | array
    {
        return 2;
    }
}