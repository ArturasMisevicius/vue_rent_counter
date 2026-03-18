<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Livewire\Pages\Reports\ReportsPage;

class Reports extends ReportsPage
{
    protected static ?string $slug = 'reports';

    protected static ?string $navigationLabel = null;

    public function getTitle(): string
    {
        return __('admin.reports.title');
    }
}
