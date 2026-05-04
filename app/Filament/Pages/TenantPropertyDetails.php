<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Support\Workspace\WorkspaceResolver;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class TenantPropertyDetails extends TenantPortalPage
{
    protected static ?string $slug = 'tenant-property-details';

    protected static ?string $navigationLabel = null;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected string $view = 'filament.pages.tenant-property-details';

    public static function getNavigationLabel(): string
    {
        return __('tenant.pages.property.title');
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
