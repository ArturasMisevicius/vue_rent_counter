<?php

use App\Enums\UserRole;
use App\Models\Building;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('superadmin sees all properties across all tenants', function () {
    // Create superadmin
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
    ]);

    // Create properties for different tenants
    $property1 = Property::factory()->create(['tenant_id' => 1]);
    $property2 = Property::factory()->create(['tenant_id' => 2]);
    $property3 = Property::factory()->create(['tenant_id' => 3]);

    // Act as superadmin
    $this->actingAs($superadmin);

    // Superadmin should see all properties
    $properties = Property::all();
    expect($properties)->toHaveCount(3);
    expect($properties->pluck('id')->toArray())->toContain($property1->id, $property2->id, $property3->id);
});

test('admin sees only properties from their tenant', function () {
    // Create admin for tenant 1
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);

    // Create properties for different tenants
    $property1 = Property::factory()->create(['tenant_id' => 1]);
    $property2 = Property::factory()->create(['tenant_id' => 1]);
    $property3 = Property::factory()->create(['tenant_id' => 2]);

    // Act as admin
    $this->actingAs($admin);

    // Admin should only see tenant 1 properties
    $properties = Property::all();
    expect($properties)->toHaveCount(2);
    expect($properties->pluck('id')->toArray())->toContain($property1->id, $property2->id);
    expect($properties->pluck('id')->toArray())->not->toContain($property3->id);
});

test('manager sees only properties from their tenant', function () {
    // Create manager for tenant 2
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => 2,
    ]);

    // Create properties for different tenants
    $property1 = Property::factory()->create(['tenant_id' => 1]);
    $property2 = Property::factory()->create(['tenant_id' => 2]);
    $property3 = Property::factory()->create(['tenant_id' => 2]);

    // Act as manager
    $this->actingAs($manager);

    // Manager should only see tenant 2 properties
    $properties = Property::all();
    expect($properties)->toHaveCount(2);
    expect($properties->pluck('id')->toArray())->toContain($property2->id, $property3->id);
    expect($properties->pluck('id')->toArray())->not->toContain($property1->id);
});

test('tenant sees only their assigned property', function () {
    // Create properties
    $property1 = Property::factory()->create(['tenant_id' => 1]);
    $property2 = Property::factory()->create(['tenant_id' => 1]);
    $property3 = Property::factory()->create(['tenant_id' => 2]);

    // Create tenant assigned to property1
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => 1,
        'property_id' => $property1->id,
    ]);

    // Act as tenant
    $this->actingAs($tenant);

    // Tenant should only see their assigned property
    $properties = Property::all();
    expect($properties)->toHaveCount(1);
    expect($properties->first()->id)->toBe($property1->id);
});

test('tenant cannot see properties from different tenant even with same property_id', function () {
    // Create properties with same ID pattern but different tenants
    $property1 = Property::factory()->create(['tenant_id' => 1]);
    $property2 = Property::factory()->create(['tenant_id' => 2]);

    // Create tenant for tenant 1, assigned to property1
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => 1,
        'property_id' => $property1->id,
    ]);

    // Act as tenant
    $this->actingAs($tenant);

    // Tenant should only see property1
    $properties = Property::all();
    expect($properties)->toHaveCount(1);
    expect($properties->first()->id)->toBe($property1->id);
    
    // Attempting to find property2 should return null
    $foundProperty = Property::find($property2->id);
    expect($foundProperty)->toBeNull();
});

test('hierarchical scope works with buildings', function () {
    // Create admin for tenant 1
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);

    // Create buildings for different tenants
    $building1 = Building::factory()->create(['tenant_id' => 1]);
    $building2 = Building::factory()->create(['tenant_id' => 2]);

    // Act as admin
    $this->actingAs($admin);

    // Admin should only see tenant 1 buildings
    $buildings = Building::all();
    expect($buildings)->toHaveCount(1);
    expect($buildings->first()->id)->toBe($building1->id);
});

test('scope falls back to session when no authenticated user', function () {
    // Create properties for different tenants
    $property1 = Property::factory()->create(['tenant_id' => 1]);
    $property2 = Property::factory()->create(['tenant_id' => 2]);

    // Set session tenant_id
    session(['tenant_id' => 1]);

    // Should only see tenant 1 properties
    $properties = Property::all();
    expect($properties)->toHaveCount(1);
    expect($properties->first()->id)->toBe($property1->id);
});
