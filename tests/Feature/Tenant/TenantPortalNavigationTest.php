<?php

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
