<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class TenantPortalRouteController extends Controller
{
    /**
     * @var array<string, string>
     */
    private const DESTINATION_ROUTES = [
        'home' => 'filament.admin.pages.tenant-dashboard',
        'readings.create' => 'filament.admin.pages.tenant-submit-meter-reading',
        'invoices.index' => 'filament.admin.pages.tenant-invoice-history',
        'property.show' => 'filament.admin.pages.tenant-property-details',
        'profile.edit' => 'filament.admin.pages.profile',
    ];

    public function __invoke(string $destination): RedirectResponse
    {
        $routeName = self::DESTINATION_ROUTES[$destination] ?? null;

        abort_if($routeName === null, 404);

        return to_route($routeName);
    }
}
