<?php

namespace App\Filament\Widgets\Admin;

use App\Filament\Support\Admin\Dashboard\AdminDashboardStats;
use Filament\Widgets\Widget;

class RecentInvoicesWidget extends Widget
{
    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.admin.recent-invoices-widget';

    protected int|string|array $columnSpan = 1;

    protected function getViewData(): array
    {
        return [
            'invoices' => app(AdminDashboardStats::class)->recentInvoicesFor(auth()->user()),
        ];
    }
}
