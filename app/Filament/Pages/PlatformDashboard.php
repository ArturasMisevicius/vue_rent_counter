<?php

namespace App\Filament\Pages;

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
}
