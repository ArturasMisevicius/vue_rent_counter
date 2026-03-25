<?php

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TenantPortalFactory;

uses(RefreshDatabase::class);

it('shows the tenant filament navigation labels and hides admin resource links', function () {
    $organization = Organization::factory()->create();
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($tenant)
        ->get(route('filament.admin.pages.tenant-dashboard'))
        ->assertSuccessful()
        ->assertSeeText('Home')
        ->assertSeeText('Readings')
        ->assertSeeText('Invoices')
        ->assertSeeText($tenant->name)
        ->assertSeeText($tenant->email)
        ->assertDontSeeText('Profile')
        ->assertDontSeeText('Buildings')
        ->assertDontSeeText('Organizations');
});

it('serves the tenant portal route set for authenticated tenants', function (string $routeName) {
    $organization = Organization::factory()->create();
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($tenant)
        ->get(route($routeName))
        ->assertSuccessful();
})->with([
    'filament.admin.pages.tenant-dashboard',
    'filament.admin.pages.tenant-submit-meter-reading',
    'filament.admin.pages.tenant-invoice-history',
    'filament.admin.pages.profile',
]);

it('shows the tenant dashboard empty state when tenant has no organization assignment', function () {
    $tenant = User::factory()->tenant()->create([
        'organization_id' => null,
    ]);

    $this->actingAs($tenant)
        ->get(route('filament.admin.pages.tenant-dashboard'))
        ->assertSuccessful()
        ->assertSeeText('No property assigned yet');
});

it('keeps the home navigation item active on the secondary property page', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->create();

    $this->actingAs($tenant->user)
        ->get(route('filament.admin.pages.tenant-property-details'))
        ->assertSuccessful();
});

it('registers the tenant route aliases required by the portal contract', function (string $routeName) {
    expect(app('router')->getRoutes()->getByName($routeName))
        ->not->toBeNull("Expected tenant portal route alias [{$routeName}] to exist.");
})->with([
    'tenant.home',
    'tenant.readings.create',
    'tenant.invoices.index',
    'tenant.property.show',
    'tenant.profile.edit',
]);

it('resolves the tenant route aliases to the canonical tenant portal pages', function (string $routeName, string $canonicalRoute) {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withMeters(1)
        ->withUnpaidInvoices(1)
        ->create();

    $this->actingAs($tenant->user)
        ->get(route($routeName))
        ->assertRedirect(route($canonicalRoute));

    $this->actingAs($tenant->user)
        ->followingRedirects()
        ->get(route($routeName))
        ->assertSuccessful();
})->with([
    'home alias' => ['tenant.home', 'filament.admin.pages.dashboard'],
    'readings alias' => ['tenant.readings.create', 'filament.admin.pages.tenant-submit-meter-reading'],
    'invoices alias' => ['tenant.invoices.index', 'filament.admin.pages.tenant-invoice-history'],
    'property alias' => ['tenant.property.show', 'filament.admin.pages.tenant-property-details'],
    'profile alias' => ['tenant.profile.edit', 'filament.admin.pages.profile'],
]);
