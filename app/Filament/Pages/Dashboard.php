<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\RefreshesOnShellLocaleUpdate;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends BaseDashboard
{
    use RefreshesOnShellLocaleUpdate;

    protected string $view = 'filament.pages.dashboard';

    public function getHeading(): string|Htmlable|null
    {
        if (auth()->user()?->isTenant()) {
            return null;
        }

        return parent::getHeading();
    }

    public function getTitle(): string
    {
        if (auth()->user()?->isTenant()) {
            return '';
        }

        return __('dashboard.title');
    }

    /**
     * @return array<string>
     */
    public function getBreadcrumbs(): array
    {
        if (auth()->user()?->isTenant()) {
            return [];
        }

        return parent::getBreadcrumbs();
    }

    public static function canAccess(): bool
    {
        return auth()->check();
    }
}
