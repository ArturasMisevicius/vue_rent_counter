<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use Filament\Pages\Page;

abstract class TenantPortalPage extends Page
{
    public static function canAccess(): bool
    {
        return auth()->user()?->isTenant() ?? false;
    }

    public static function getNavigationGroup(): ?string
    {
        return __('shell.navigation.groups.my_home');
    }
}
