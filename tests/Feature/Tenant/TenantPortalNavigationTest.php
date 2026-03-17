<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows the four tenant bottom navigation items and hides admin navigation', function () {
    $tenant = User::factory()->tenant()->create();

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

it('registers tenant portal routes that return successful placeholder pages', function (string $routeName) {
    $tenant = User::factory()->tenant()->create();

    $this->actingAs($tenant)
        ->get(route($routeName))
        ->assertSuccessful();
})->with([
    'tenant.home',
    'tenant.readings.create',
    'tenant.invoices.index',
    'tenant.profile.edit',
]);
