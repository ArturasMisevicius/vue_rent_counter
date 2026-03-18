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
        ->assertSeeText('Profile')
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

it('keeps the home navigation item active on the secondary property page', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->create();

    $this->actingAs($tenant->user)
        ->get(route('filament.admin.pages.tenant-property-details'))
        ->assertSuccessful();
});
