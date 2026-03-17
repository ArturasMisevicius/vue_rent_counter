<?php

namespace App\Filament\Pages;

use App\Filament\Support\Auth\LoginRedirector;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static bool $shouldRegisterNavigation = false;

    public function mount(): void
    {
        $this->redirect(app(LoginRedirector::class)->for(auth()->user()), navigate: true);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdminLike() ?? false;
    }
}
