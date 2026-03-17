<?php

use App\Models\Building;
use App\Models\Organization;
use App\Models\Property;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lets managers access the unified app dashboard and key workspace resources', function () {
    $organization = Organization::factory()->create();
    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    Subscription::factory()->for($organization)->active()->create([
        'property_limit_snapshot' => 10,
        'tenant_limit_snapshot' => 10,
    ]);

    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();

    $this->actingAs($manager)
        ->get('/app')
        ->assertSuccessful();

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.buildings.index'))
        ->assertSuccessful();

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.properties.index'))
        ->assertSuccessful();

    $this->actingAs($manager)
        ->get(route('filament.admin.pages.settings'))
        ->assertSuccessful()
        ->assertDontSeeText('Subscription')
        ->assertDontSeeText('Organization Settings');
});

it('does not expose any legacy manager-prefixed route surface', function (string $path) {
    $organization = Organization::factory()->create();
    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($manager)
        ->get($path)
        ->assertNotFound();
})->with([
    '/manager',
    '/manager/dashboard',
    '/manager/buildings',
    '/manager/properties',
    '/manager/reports',
    '/manager/profile',
]);
