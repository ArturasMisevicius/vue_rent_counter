<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Livewire\Pages\Reports\ReportsPage;

class Reports extends ReportsPage
{
    protected static ?string $slug = 'reports';

    protected static ?string $navigationLabel = null;

    protected static string|\UnitEnum|null $navigationGroup = null;

    public static function getNavigationGroup(): ?string
    {
        return __('shell.navigation.groups.reports');
    }

    public static function canAccess(): bool
    {
        return parent::canAccess();
    }

    public function getTitle(): string
    {
        return __('admin.reports.title');
    }
}
