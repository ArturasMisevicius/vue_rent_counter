<?php

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders tenant pages with topbar and route-safe bottom navigation', function () {
    $tenant = User::factory()->tenant()->create();

    $this->actingAs($tenant)
        ->get(route('tenant.home'))
        ->assertSuccessful()
        ->assertSeeText('Search anything')
        ->assertSeeText('Home')
        ->assertSeeText('Readings')
        ->assertSeeText('Invoices')
        ->assertSeeText('Profile')
        ->assertDontSeeText('Platform')
        ->assertDontSee('data-navigation-route="filament.admin.resources.organizations.index"', false)
        ->assertSee('data-navigation-route="tenant.readings.create"', false)
        ->assertSee('data-navigation-route="tenant.invoices.index"', false)
        ->assertSee('data-navigation-route="tenant.profile.edit"', false);
});

it('renders grouped sidebar navigation for admin roles', function (Closure $userFactory, string $expectedSection, string $currentRoute) {
    $user = $userFactory();

    $this->actingAs($user)
        ->get(route($currentRoute))
        ->assertSuccessful()
        ->assertSeeText('Search anything')
        ->assertSeeText($expectedSection)
        ->assertSeeText('Account')
        ->assertSeeText('Profile')
        ->assertSee("data-navigation-state=\"{$currentRoute}:true\"", false);
})->with([
    'superadmin' => [
        fn () => User::factory()->superadmin()->create(),
        'Platform',
        'filament.admin.pages.platform-dashboard',
    ],
    'admin' => [
        fn () => User::factory()->admin()->create([
            'organization_id' => Organization::factory(),
        ]),
        'Workspace',
        'filament.admin.pages.organization-dashboard',
    ],
    'manager' => [
        fn () => User::factory()->manager()->create(),
        'Workspace',
        'filament.admin.pages.organization-dashboard',
    ],
]);

it('marks the tenant home destination as the active navigation item', function () {
    $tenant = User::factory()->tenant()->create();

    $this->actingAs($tenant)
        ->get(route('tenant.home'))
        ->assertSuccessful()
        ->assertSee('data-navigation-state="tenant.home:true"', false);
});
