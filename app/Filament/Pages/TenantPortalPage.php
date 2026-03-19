<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Support\Workspace\WorkspaceResolver;
use Filament\Pages\Page;

abstract class TenantPortalPage extends Page
{
    public static function canAccess(): bool
    {
        return app(WorkspaceResolver::class)->current()?->isTenant() ?? false;
    }

    public static function getNavigationGroup(): ?string
    {
        return __('shell.navigation.groups.my_home');
    }
}
