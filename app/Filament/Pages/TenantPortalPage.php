<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\RefreshesOnShellLocaleUpdate;
use App\Filament\Support\Workspace\WorkspaceResolver;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

abstract class TenantPortalPage extends Page
{
    use RefreshesOnShellLocaleUpdate;

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public static function canAccess(): bool
    {
        return app(WorkspaceResolver::class)->current()?->isTenant() ?? false;
    }

    public static function getNavigationGroup(): ?string
    {
        return __('shell.navigation.groups.my_home');
    }
}
