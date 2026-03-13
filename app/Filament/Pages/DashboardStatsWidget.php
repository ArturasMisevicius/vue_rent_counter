<?php

declare(strict_types=1);

namespace App\Filament\Pages;

class DashboardStatsWidget extends \App\Filament\Widgets\DashboardStatsWidget
{
    /**
     * Expose stats for testing by proxying to the widget implementation.
     */
    public function getStats(): array
    {
        return parent::getStats();
    }
}
