<?php

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders tenant pages inside the authenticated shell', function () {
    $tenant = User::factory()->tenant()->create();

    $this->actingAs($tenant)
        ->get(route('filament.admin.pages.tenant-dashboard'))
        ->assertSuccessful()
        ->assertSeeText('Search anything')
        ->assertSeeText('Home')
        ->assertSeeText('Profile')
        ->assertSee('data-shell-nav="sidebar"', false)
        ->assertDontSeeText('Buildings')
        ->assertDontSeeText('Organizations');
});

it('does not register echo listeners when broadcasting is configured to log', function () {
    $tenant = User::factory()->tenant()->create();

    $this->actingAs($tenant)
        ->get(route('filament.admin.pages.dashboard'))
        ->assertSuccessful()
        ->assertDontSee('echo-private:org.', false);
});

it('renders role-aware shared chrome around organization admin pages', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($admin)
        ->get(route('filament.admin.pages.organization-dashboard'))
        ->assertSuccessful()
        ->assertSeeText('Search anything')
        ->assertSee('data-shell-nav="sidebar"', false)
        ->assertSee('data-shell-group="properties"', false)
        ->assertSee('data-shell-group="billing"', false)
        ->assertSee('data-shell-group="reports"', false)
        ->assertSee('data-shell-group="account"', false)
        ->assertSeeText('Buildings')
        ->assertSeeText('Properties')
        ->assertSeeText('Settings')
        ->assertSee(route('filament.admin.resources.buildings.index'), false)
        ->assertSee(route('filament.admin.resources.properties.index'), false)
        ->assertSee('data-shell-current="filament.admin.pages.dashboard"', false)
        ->assertDontSee('data-shell-group="platform"', false);
});

it('renders platform navigation for superadmins without organization navigation', function () {
    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($superadmin)
        ->get(route('filament.admin.pages.platform-dashboard'))
        ->assertSuccessful()
        ->assertSeeText('Search anything')
        ->assertSee('data-shell-nav="sidebar"', false)
        ->assertSee('data-shell-group="platform"', false)
        ->assertSee('data-shell-group="account"', false)
        ->assertSee(route('filament.admin.resources.organizations.index'), false)
        ->assertSee('data-shell-current="filament.admin.pages.dashboard"', false);
});

it('redirects admin-like users from the shared profile route into the filament-backed profile page', function () {
    $organization = Organization::factory()->create();
    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($manager)
        ->get(route('profile.edit'))
        ->assertRedirect(route('filament.admin.pages.profile'));
});
