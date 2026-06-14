<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\RefreshesOnShellLocaleUpdate;
use App\Filament\Support\Workspace\WorkspaceResolver;
use App\Models\User;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

abstract class TenantPortalPage extends Page
{
    use RefreshesOnShellLocaleUpdate;

    public function getTitle(): string|Htmlable
    {
        return '';
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    /**
     * @return array<string>
     */
    public function getBreadcrumbs(): array
    {
        return [];
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && (app(WorkspaceResolver::class)->current()?->isTenant() ?? false)
            && Gate::forUser($user)->allows('accessTenantPortal', $user);
    }

    public static function getNavigationGroup(): ?string
    {
        return __('shell.navigation.groups.my_home');
    }
}
