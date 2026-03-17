<?php

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TenantPortalFactory;

uses(RefreshDatabase::class);

it('shows the four tenant bottom navigation items and hides admin navigation', function () {
    $organization = Organization::factory()->create();
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($tenant)
        ->get(route('tenant.home'))
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
    'tenant.home',
    'tenant.readings.create',
    'tenant.invoices.index',
    'tenant.profile.edit',
]);

it('keeps the home navigation item active on the secondary property page', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->create();

    $this->actingAs($tenant->user)
        ->get(route('tenant.property.show'))
        ->assertSuccessful()
        ->assertSee('data-shell-current="tenant.home"', false);
});
