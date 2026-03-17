<?php

use App\Models\Building;
use App\Models\Organization;
use App\Models\Property;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lets managers access the same organization workspace routes as admins', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);
    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    Subscription::factory()->for($organization)->active()->create([
        'property_limit_snapshot' => 10,
        'tenant_limit_snapshot' => 10,
    ]);

    $this->actingAs($manager)
        ->get(route('filament.admin.pages.organization-dashboard'))
        ->assertSuccessful()
        ->assertSee('data-shell-group="organization"', false)
        ->assertSee('data-shell-group="account"', false)
        ->assertSeeText('Profile')
        ->assertSeeText('Settings')
        ->assertDontSee('data-shell-group="platform"', false);

    $this->actingAs($manager)
        ->get(route('filament.admin.pages.profile'))
        ->assertSuccessful();

    $this->actingAs($manager)
        ->get(route('filament.admin.pages.settings'))
        ->assertSuccessful();

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.buildings.index'))
        ->assertSuccessful();

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.buildings.create'))
        ->assertSuccessful();

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.buildings.view', $building))
        ->assertSuccessful();

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.buildings.edit', $building))
        ->assertSuccessful();

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.properties.index'))
        ->assertSuccessful();

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.properties.create'))
        ->assertSuccessful();

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.properties.view', $property))
        ->assertSuccessful();

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.properties.edit', $property))
        ->assertSuccessful();

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.tenants.index'))
        ->assertSuccessful();

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.tenants.create'))
        ->assertSuccessful();

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.tenants.view', $tenant))
        ->assertSuccessful();

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.tenants.edit', $tenant))
        ->assertSuccessful();
});
