<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class PlatformNotifications extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'platform-notifications';

    protected string $view = 'filament.pages.platform-notifications';

    public function getTitle(): string
    {
        return 'Platform Notifications';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }
}
