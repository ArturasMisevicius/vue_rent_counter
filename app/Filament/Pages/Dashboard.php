<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Widgets\DashboardStatsWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $view = 'filament.pages.dashboard';

    protected static ?string $title = 'Dashboard';

    public function getWidgets(): array
    {
        return [
            DashboardStatsWidget::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return 2;
    }
}
