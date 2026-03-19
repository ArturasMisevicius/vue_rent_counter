<?php

declare(strict_types=1);

use Illuminate\Support\Str;

it('keeps the highest-risk workspace entrypoints on the shared workspace contract', function () {
    $workspaceResolverFiles = [
        'app/Filament/Support/Admin/OrganizationContext.php',
        'app/Providers/Filament/AppPanelProvider.php',
        'app/Filament/Pages/TenantPortalPage.php',
        'app/Http/Controllers/TenantPortalRouteController.php',
        'app/Filament/Support/Tenant/Portal/TenantInvoiceIndexQuery.php',
        'app/Filament/Support/Tenant/Portal/TenantHomePresenter.php',
        'app/Filament/Support/Tenant/Portal/TenantPropertyPresenter.php',
        'app/Filament/Actions/Tenant/Readings/SubmitTenantReadingAction.php',
    ];

    foreach ($workspaceResolverFiles as $path) {
        $contents = file_get_contents(base_path($path));

        expect($contents)->not->toBeFalse()
            ->and(Str::contains((string) $contents, 'WorkspaceResolver'))->toBeTrue("Expected {$path} to depend on WorkspaceResolver.");
    }

    $tenantComponentFiles = [
        'app/Livewire/Tenant/InvoiceHistory.php',
        'app/Livewire/Tenant/PropertyDetails.php',
        'app/Livewire/Tenant/SubmitReadingPage.php',
        'app/Livewire/Pages/Dashboard/TenantDashboard.php',
    ];

    foreach ($tenantComponentFiles as $path) {
        $contents = file_get_contents(base_path($path));

        expect($contents)->not->toBeFalse()
            ->and(Str::contains((string) $contents, 'ResolvesTenantWorkspace'))->toBeTrue("Expected {$path} to use the tenant workspace guard.")
            ->and(Str::contains((string) $contents, 'auth()->id()'))->toBeFalse("Expected {$path} to avoid direct auth()->id() lookups.");
    }
});
