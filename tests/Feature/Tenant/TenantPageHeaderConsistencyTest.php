<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TenantPortalFactory;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('renders tenant pages with the custom rounded page header and without the default filament header heading', function (string $routeName) {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->withUnpaidInvoices(1)
        ->create();

    actingAs($tenant->user);

    get(route($routeName))
        ->assertSuccessful()
        ->assertDontSee('fi-header-heading', false)
        ->assertSee('mb-8 rounded-[2rem] border border-white/60 bg-white/92 px-6 py-6 shadow-[0_24px_70px_rgba(15,23,42,0.14)] backdrop-blur sm:px-8', false);
})->with([
    'tenant dashboard' => 'filament.admin.pages.tenant-dashboard',
    'tenant property details' => 'filament.admin.pages.tenant-property-details',
    'tenant invoice history' => 'filament.admin.pages.tenant-invoice-history',
    'tenant submit reading' => 'filament.admin.pages.tenant-submit-meter-reading',
]);
