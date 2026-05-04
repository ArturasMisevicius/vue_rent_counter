<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TenantPortalFactory;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('renders tenant pages without the default filament header heading and with the shared tenant layout standard', function (string $routeName, bool $usesSharedPageHeader, bool $usesSplitLayout) {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->withUnpaidInvoices(1)
        ->create();

    actingAs($tenant->user);

    $response = get(route($routeName))
        ->assertSuccessful()
        ->assertDontSee('fi-header-heading', false)
        ->assertDontSee('fi-breadcrumbs', false)
        ->assertSee('data-tenant-layout="standard"', false);

    preg_match('/<title>\s*(.*?)\s*<\/title>/s', $response->getContent(), $matches);

    expect(trim(preg_replace('/\s+/', ' ', html_entity_decode($matches[1] ?? ''))))->toBe(config('app.name', 'Tenanto'));

    if ($usesSharedPageHeader) {
        $response->assertSee('mb-8 rounded-[2rem] border border-white/60 bg-white/92 px-6 py-6 shadow-[0_24px_70px_rgba(15,23,42,0.14)] backdrop-blur sm:px-8', false);
    }

    if (! $usesSplitLayout) {
        return;
    }

    $response
        ->assertSee('data-tenant-layout-section="split"', false)
        ->assertSee('data-tenant-panel="main"', false)
        ->assertSee('data-tenant-panel="aside"', false)
        ->assertSee('xl:w-[24rem] 2xl:w-[28rem]', false);
})->with([
    'shared tenant app entry' => ['filament.admin.pages.dashboard', false, true],
    'tenant dashboard' => ['filament.admin.pages.tenant-dashboard', false, true],
    'tenant property details' => ['filament.admin.pages.tenant-property-details', true, true],
    'tenant invoice history' => ['filament.admin.pages.tenant-invoice-history', true, true],
    'tenant submit reading' => ['filament.admin.pages.tenant-submit-meter-reading', false, true],
    'tenant profile' => ['filament.admin.pages.profile', true, false],
]);
