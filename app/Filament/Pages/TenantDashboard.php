<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Support\Workspace\WorkspaceResolver;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class TenantDashboard extends TenantPortalPage
{
    protected static ?string $slug = 'tenant-dashboard';

    protected static ?string $navigationLabel = null;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected string $view = 'filament.pages.tenant-dashboard';

    public function getTitle(): string
    {
        return __('tenant.navigation.home');
    }

    public static function getNavigationLabel(): string
    {
        return __('tenant.navigation.home');
    }

    public static function canAccess(): bool
    {
        $workspaceResolver = app(WorkspaceResolver::class);

        if (! $workspaceResolver instanceof WorkspaceResolver) {
            return false;
        }

        $workspace = $workspaceResolver->current();

        return $workspace?->isTenant() ?? false;
    }
}
