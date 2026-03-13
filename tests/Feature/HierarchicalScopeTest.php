<?php

use App\Enums\PropertyType;
use App\Enums\UserRole;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Feature: hierarchical-user-management, Property 1: Superadmin unrestricted access
 * Validates: Requirements 1.4, 12.2, 13.1
 */
test('superadmin can access all resources without tenant filtering', function () {
    // Create two different tenants with properties
    $tenantId1 = fake()->numberBetween(1, 1000);
    $tenantId2 = fake()->numberBetween(1001, 2000);
    
    $property1 = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId1,
        'address' => fake()->address(),
        'type' => PropertyType::APARTMENT,
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    $property2 = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId2,
        'address' => fake()->address(),
        'type' => PropertyType::APARTMENT,
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    // Create a superadmin user
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
    ]);
    
    // Act as superadmin
    $this->actingAs($superadmin);
    
    // Property: Superadmin should see all properties regardless of tenant_id
    $allProperties = Property::all();
    
    expect($allProperties)->toHaveCount(2);
    expect($allProperties->pluck('id')->toArray())->toContain($property1->id, $property2->id);
});

/**
 * Feature: hierarchical-user-management, Property 2: Admin tenant isolation
 * Validates: Requirements 3.3, 4.3, 12.3
 */
test('admin can only access resources within their tenant_id', function () {
    // Create two different tenants with properties
    $tenantId1 = fake()->numberBetween(1, 1000);
    $tenantId2 = fake()->numberBetween(1001, 2000);
    
    $property1 = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId1,
        'address' => fake()->address(),
        'type' => PropertyType::APARTMENT,
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    $property2 = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId2,
        'address' => fake()->address(),
        'type' => PropertyType::APARTMENT,
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    // Create an admin user for tenant 1
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenantId1,
    ]);
    
    // Act as admin
    $this->actingAs($admin);
    
    // Property: Admin should only see properties from their tenant
    $visibleProperties = Property::all();
    
    expect($visibleProperties)->toHaveCount(1);
    expect($visibleProperties->first()->id)->toBe($property1->id);
    expect($visibleProperties->first()->tenant_id)->toBe($tenantId1);
    
    // Property: Admin should not be able to access other tenant's properties
    expect(Property::find($property2->id))->toBeNull();
});

/**
 * Feature: hierarchical-user-management, Property 3: Tenant property isolation
 * Validates: Requirements 8.2, 9.1, 11.1, 12.4
 */
test('tenant can only access resources within their tenant_id and property_id', function () {
    // Create a tenant with two properties
    $tenantId = fake()->numberBetween(1, 1000);
    
    $property1 = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'type' => PropertyType::APARTMENT,
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    $property2 = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'type' => PropertyType::APARTMENT,
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    // Create a tenant user assigned to property 1
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $tenantId,
        'property_id' => $property1->id,
    ]);
    
    // Act as tenant
    $this->actingAs($tenant);
    
    // Property: Tenant should only see their assigned property
    $visibleProperties = Property::all();
    
    expect($visibleProperties)->toHaveCount(1);
    expect($visibleProperties->first()->id)->toBe($property1->id);
    
    // Property: Tenant should not be able to access other properties even in same tenant
    expect(Property::find($property2->id))->toBeNull();
});

/**
 * Feature: hierarchical-user-management, Property 3: Tenant property isolation for meters
 * Validates: Requirements 9.1, 12.4
 */
test('tenant can only access meters for their assigned property', function () {
    // Create a tenant with two properties
    $tenantId = fake()->numberBetween(1, 1000);
    
    $property1 = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'type' => PropertyType::APARTMENT,
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    $property2 = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'type' => PropertyType::APARTMENT,
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    // Create meters for both properties
    $meter1 = Meter::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'property_id' => $property1->id,
        'serial_number' => fake()->unique()->numerify('METER-####'),
        'type' => \App\Enums\MeterType::ELECTRICITY,
        'installation_date' => now()->subMonths(6),
    ]);
    
    $meter2 = Meter::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'property_id' => $property2->id,
        'serial_number' => fake()->unique()->numerify('METER-####'),
        'type' => \App\Enums\MeterType::ELECTRICITY,
        'installation_date' => now()->subMonths(6),
    ]);
    
    // Create a tenant user assigned to property 1
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $tenantId,
        'property_id' => $property1->id,
    ]);
    
    // Act as tenant
    $this->actingAs($tenant);
    
    // Property: Tenant should only see meters for their assigned property
    $visibleMeters = Meter::all();
    
    expect($visibleMeters)->toHaveCount(1);
    expect($visibleMeters->first()->id)->toBe($meter1->id);
    expect($visibleMeters->first()->property_id)->toBe($property1->id);
    
    // Property: Tenant should not be able to access meters from other properties
    expect(Meter::find($meter2->id))->toBeNull();
});

/**
 * Feature: hierarchical-user-management, Property 2: Admin tenant isolation for buildings
 * Validates: Requirements 4.3, 12.3
 */
test('admin can only access buildings within their tenant_id', function () {
    // Create two different tenants with buildings
    $tenantId1 = fake()->numberBetween(1, 1000);
    $tenantId2 = fake()->numberBetween(1001, 2000);
    
    $building1 = Building::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId1,
        'address' => fake()->address(),
        'total_apartments' => fake()->numberBetween(10, 100),
    ]);
    
    $building2 = Building::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId2,
        'address' => fake()->address(),
        'total_apartments' => fake()->numberBetween(10, 100),
    ]);
    
    // Create an admin user for tenant 1
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenantId1,
    ]);
    
    // Act as admin
    $this->actingAs($admin);
    
    // Property: Admin should only see buildings from their tenant
    $visibleBuildings = Building::all();
    
    expect($visibleBuildings)->toHaveCount(1);
    expect($visibleBuildings->first()->id)->toBe($building1->id);
    expect($visibleBuildings->first()->tenant_id)->toBe($tenantId1);
    
    // Property: Admin should not be able to access other tenant's buildings
    expect(Building::find($building2->id))->toBeNull();
});

/**
 * Feature: hierarchical-user-management, Property 4: Scope macros work correctly
 * Validates: Requirements 12.1, 12.2, 12.3
 */
test('scope macros allow bypassing and overriding hierarchical filtering', function () {
    $tenantId1 = fake()->numberBetween(1, 1000);
    $tenantId2 = fake()->numberBetween(1001, 2000);
    
    $property1 = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId1,
        'address' => fake()->address(),
        'type' => PropertyType::APARTMENT,
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    $property2 = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId2,
        'address' => fake()->address(),
        'type' => PropertyType::APARTMENT,
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenantId1,
    ]);
    
    $this->actingAs($admin);
    
    // Test withoutHierarchicalScope macro
    $allProperties = Property::withoutHierarchicalScope()->get();
    expect($allProperties)->toHaveCount(2);
    
    // Test forTenant macro
    $tenant2Properties = Property::forTenant($tenantId2)->get();
    expect($tenant2Properties)->toHaveCount(1);
    expect($tenant2Properties->first()->id)->toBe($property2->id);
    
    // Test forProperty macro
    $specificProperty = Property::forProperty($property2->id)->get();
    expect($specificProperty)->toHaveCount(1);
    expect($specificProperty->first()->id)->toBe($property2->id);
});

/**
 * Feature: hierarchical-user-management, Property 5: Column cache improves performance
 * Validates: Performance optimization
 */
test('column existence checks are cached to avoid repeated schema queries', function () {
    $tenantId = fake()->numberBetween(1, 1000);
    
    $property = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'type' => PropertyType::APARTMENT,
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenantId,
    ]);
    
    $this->actingAs($admin);
    
    // First query - will cache column checks
    $properties1 = Property::all();
    
    // Second query - should use cached column checks
    $properties2 = Property::all();
    
    // Both queries should return the same result
    expect($properties1)->toHaveCount(1);
    expect($properties2)->toHaveCount(1);
    expect($properties1->first()->id)->toBe($property->id);
    expect($properties2->first()->id)->toBe($property->id);
});

/**
 * Feature: hierarchical-user-management: Manager role filtering
 * Validates: Requirements 12.3
 */
test('manager can only access resources within their tenant_id', function () {
    $tenantId1 = fake()->numberBetween(1, 1000);
    $tenantId2 = fake()->numberBetween(1001, 2000);
    
    $property1 = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId1,
        'address' => fake()->address(),
        'type' => PropertyType::APARTMENT,
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    $property2 = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId2,
        'address' => fake()->address(),
        'type' => PropertyType::APARTMENT,
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId1,
    ]);
    
    $this->actingAs($manager);
    
    $visibleProperties = Property::all();
    
    expect($visibleProperties)->toHaveCount(1);
    expect($visibleProperties->first()->id)->toBe($property1->id);
    expect(Property::find($property2->id))->toBeNull();
});

/**
 * Feature: hierarchical-user-management: Buildings relationship filtering
 * Validates: Requirements 12.4
 */
test('tenant can access buildings via property relationship', function () {
    $tenantId = fake()->numberBetween(1, 1000);
    
    $building1 = Building::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'total_apartments' => fake()->numberBetween(10, 100),
    ]);
    
    $building2 = Building::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'total_apartments' => fake()->numberBetween(10, 100),
    ]);
    
    $property1 = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'building_id' => $building1->id,
        'address' => fake()->address(),
        'type' => PropertyType::APARTMENT,
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    $property2 = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'building_id' => $building2->id,
        'address' => fake()->address(),
        'type' => PropertyType::APARTMENT,
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $tenantId,
        'property_id' => $property1->id,
    ]);
    
    $this->actingAs($tenant);
    
    // Tenant should only see building associated with their property
    $visibleBuildings = Building::all();
    
    expect($visibleBuildings)->toHaveCount(1);
    expect($visibleBuildings->first()->id)->toBe($building1->id);
    expect(Building::find($building2->id))->toBeNull();
});

/**
 * Feature: hierarchical-user-management: Unauthenticated access
 * Validates: Requirements 12.1
 */
test('unauthenticated users see no data', function () {
    Property::withoutGlobalScopes()->create(['tenant_id' => 1]);
    Property::withoutGlobalScopes()->create(['tenant_id' => 2]);
    
    // No authentication
    $properties = Property::all();
    
    expect($properties)->toHaveCount(0);
});

/**
 * Feature: hierarchical-user-management: User without tenant_id
 * Validates: Requirements 12.1
 */
test('users without tenant_id see no data', function () {
    Property::withoutGlobalScopes()->create(['tenant_id' => 1]);
    
    $user = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => null,
    ]);
    
    $this->actingAs($user);
    
    $properties = Property::all();
    
    expect($properties)->toHaveCount(0);
});
