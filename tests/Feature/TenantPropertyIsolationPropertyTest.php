<?php

use App\Enums\UserRole;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Feature: hierarchical-user-management, Property 3: Tenant property isolation
// Validates: Requirements 8.2, 9.1, 11.1, 12.4
test('tenant can only access their assigned property', function () {
    // Generate tenant ID
    $tenantId = fake()->numberBetween(1, 1000);
    
    // Create two properties for the same tenant
    $property1 = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'type' => fake()->randomElement(['apartment', 'house']),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    $property2 = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'type' => fake()->randomElement(['apartment', 'house']),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    // Create tenant user assigned to property1
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $tenantId,
        'property_id' => $property1->id,
    ]);
    
    // Act as tenant
    $this->actingAs($tenant);
    
    // Property: Tenant should be able to access their assigned property
    $result1 = Property::find($property1->id);
    expect($result1)->not->toBeNull();
    expect($result1->id)->toBe($property1->id);
    
    // Property: Tenant should NOT be able to access other properties in same tenant
    $result2 = Property::find($property2->id);
    expect($result2)->toBeNull();
    
    // Verify both properties exist when accessed without scopes
    $property1Exists = Property::withoutGlobalScopes()->find($property1->id);
    expect($property1Exists)->not->toBeNull();
    
    $property2Exists = Property::withoutGlobalScopes()->find($property2->id);
    expect($property2Exists)->not->toBeNull();
})->repeat(100);

// Feature: hierarchical-user-management, Property 3: Tenant property isolation
// Validates: Requirements 8.2, 9.1, 11.1, 12.4
test('tenant can only access meters for their assigned property', function () {
    // Generate tenant ID
    $tenantId = fake()->numberBetween(1, 1000);
    
    // Create two properties for the same tenant
    $property1 = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'type' => fake()->randomElement(['apartment', 'house']),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    $property2 = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'type' => fake()->randomElement(['apartment', 'house']),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    // Create meters for both properties
    $meter1 = Meter::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'property_id' => $property1->id,
        'serial_number' => fake()->unique()->numerify('LT-####-####'),
        'type' => fake()->randomElement(['electricity', 'water_cold', 'water_hot', 'heating']),
        'installation_date' => fake()->dateTimeBetween('-5 years', 'now'),
        'supports_zones' => fake()->boolean(30),
    ]);
    
    $meter2 = Meter::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'property_id' => $property2->id,
        'serial_number' => fake()->unique()->numerify('LT-####-####'),
        'type' => fake()->randomElement(['electricity', 'water_cold', 'water_hot', 'heating']),
        'installation_date' => fake()->dateTimeBetween('-5 years', 'now'),
        'supports_zones' => fake()->boolean(30),
    ]);
    
    // Create tenant user assigned to property1
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $tenantId,
        'property_id' => $property1->id,
    ]);
    
    // Act as tenant
    $this->actingAs($tenant);
    
    // Property: Tenant should be able to access meters for their assigned property
    $result1 = Meter::find($meter1->id);
    expect($result1)->not->toBeNull();
    expect($result1->id)->toBe($meter1->id);
    expect($result1->property_id)->toBe($property1->id);
    
    // Property: Tenant should NOT be able to access meters for other properties
    $result2 = Meter::find($meter2->id);
    expect($result2)->toBeNull();
    
    // Verify both meters exist when accessed without scopes
    $meter1Exists = Meter::withoutGlobalScopes()->find($meter1->id);
    expect($meter1Exists)->not->toBeNull();
    
    $meter2Exists = Meter::withoutGlobalScopes()->find($meter2->id);
    expect($meter2Exists)->not->toBeNull();
})->repeat(100);

// Feature: hierarchical-user-management, Property 3: Tenant property isolation
// Validates: Requirements 8.2, 9.1, 11.1, 12.4
test('tenant querying all properties only sees their assigned property', function () {
    // Generate tenant ID
    $tenantId = fake()->numberBetween(1, 1000);
    
    // Create random number of properties for the same tenant
    $propertiesCount = fake()->numberBetween(3, 8);
    $properties = [];
    
    for ($i = 0; $i < $propertiesCount; $i++) {
        $properties[] = Property::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId,
            'address' => fake()->address(),
            'type' => fake()->randomElement(['apartment', 'house']),
            'area_sqm' => fake()->randomFloat(2, 20, 200),
        ]);
    }
    
    // Pick a random property to assign to tenant
    $assignedProperty = fake()->randomElement($properties);
    
    // Create tenant user assigned to the selected property
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $tenantId,
        'property_id' => $assignedProperty->id,
    ]);
    
    // Act as tenant
    $this->actingAs($tenant);
    
    // Property: Tenant should only see their assigned property
    $visibleProperties = Property::all();
    expect($visibleProperties)->toHaveCount(1);
    expect($visibleProperties->first()->id)->toBe($assignedProperty->id);
    
    // Verify all properties exist when accessed without scopes
    $allProperties = Property::withoutGlobalScopes()->where('tenant_id', $tenantId)->get();
    expect($allProperties)->toHaveCount($propertiesCount);
})->repeat(100);

// Feature: hierarchical-user-management, Property 3: Tenant property isolation
// Validates: Requirements 8.2, 9.1, 11.1, 12.4
test('tenant querying all meters only sees meters for their assigned property', function () {
    // Generate tenant ID
    $tenantId = fake()->numberBetween(1, 1000);
    
    // Create two properties for the same tenant
    $property1 = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'type' => fake()->randomElement(['apartment', 'house']),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    $property2 = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'type' => fake()->randomElement(['apartment', 'house']),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    // Create random number of meters for property1
    $metersCount1 = fake()->numberBetween(1, 4);
    for ($i = 0; $i < $metersCount1; $i++) {
        Meter::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId,
            'property_id' => $property1->id,
            'serial_number' => fake()->unique()->numerify('LT-####-####'),
            'type' => fake()->randomElement(['electricity', 'water_cold', 'water_hot', 'heating']),
            'installation_date' => fake()->dateTimeBetween('-5 years', 'now'),
            'supports_zones' => fake()->boolean(30),
        ]);
    }
    
    // Create random number of meters for property2
    $metersCount2 = fake()->numberBetween(1, 4);
    for ($i = 0; $i < $metersCount2; $i++) {
        Meter::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId,
            'property_id' => $property2->id,
            'serial_number' => fake()->unique()->numerify('LT-####-####'),
            'type' => fake()->randomElement(['electricity', 'water_cold', 'water_hot', 'heating']),
            'installation_date' => fake()->dateTimeBetween('-5 years', 'now'),
            'supports_zones' => fake()->boolean(30),
        ]);
    }
    
    // Create tenant user assigned to property1
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $tenantId,
        'property_id' => $property1->id,
    ]);
    
    // Act as tenant
    $this->actingAs($tenant);
    
    // Property: Tenant should only see meters for their assigned property
    $visibleMeters = Meter::all();
    expect($visibleMeters)->toHaveCount($metersCount1);
    
    // Verify all meters belong to property1
    foreach ($visibleMeters as $meter) {
        expect($meter->property_id)->toBe($property1->id);
        expect($meter->tenant_id)->toBe($tenantId);
    }
    
    // Verify all meters exist when accessed without scopes
    $allMeters = Meter::withoutGlobalScopes()->where('tenant_id', $tenantId)->get();
    expect($allMeters)->toHaveCount($metersCount1 + $metersCount2);
})->repeat(100);

// Feature: hierarchical-user-management, Property 3: Tenant property isolation
// Validates: Requirements 8.2, 9.1, 11.1, 12.4
test('tenant with null property_id cannot access any properties', function () {
    // Generate tenant ID
    $tenantId = fake()->numberBetween(1, 1000);
    
    // Create properties for the tenant
    $propertiesCount = fake()->numberBetween(2, 5);
    for ($i = 0; $i < $propertiesCount; $i++) {
        Property::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId,
            'address' => fake()->address(),
            'type' => fake()->randomElement(['apartment', 'house']),
            'area_sqm' => fake()->randomFloat(2, 20, 200),
        ]);
    }
    
    // Create tenant user with null property_id (not assigned to any property)
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $tenantId,
        'property_id' => null,
    ]);
    
    // Act as tenant
    $this->actingAs($tenant);
    
    // Property: Tenant with null property_id should not see any properties
    $visibleProperties = Property::all();
    expect($visibleProperties)->toHaveCount(0);
    
    // Verify properties exist when accessed without scopes
    $allProperties = Property::withoutGlobalScopes()->where('tenant_id', $tenantId)->get();
    expect($allProperties)->toHaveCount($propertiesCount);
})->repeat(100);

// Feature: hierarchical-user-management, Property 3: Tenant property isolation
// Validates: Requirements 8.2, 9.1, 11.1, 12.4
test('tenant with null property_id cannot access any meters', function () {
    // Generate tenant ID
    $tenantId = fake()->numberBetween(1, 1000);
    
    // Create property for the tenant
    $property = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'type' => fake()->randomElement(['apartment', 'house']),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    // Create meters for the property
    $metersCount = fake()->numberBetween(2, 5);
    for ($i = 0; $i < $metersCount; $i++) {
        Meter::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId,
            'property_id' => $property->id,
            'serial_number' => fake()->unique()->numerify('LT-####-####'),
            'type' => fake()->randomElement(['electricity', 'water_cold', 'water_hot', 'heating']),
            'installation_date' => fake()->dateTimeBetween('-5 years', 'now'),
            'supports_zones' => fake()->boolean(30),
        ]);
    }
    
    // Create tenant user with null property_id (not assigned to any property)
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $tenantId,
        'property_id' => null,
    ]);
    
    // Act as tenant
    $this->actingAs($tenant);
    
    // Property: Tenant with null property_id should not see any meters
    $visibleMeters = Meter::all();
    expect($visibleMeters)->toHaveCount(0);
    
    // Verify meters exist when accessed without scopes
    $allMeters = Meter::withoutGlobalScopes()->where('tenant_id', $tenantId)->get();
    expect($allMeters)->toHaveCount($metersCount);
})->repeat(100);

// Feature: hierarchical-user-management, Property 3: Tenant property isolation
// Validates: Requirements 8.2, 9.1, 11.1, 12.4
test('tenant cannot access buildings from different tenant even with same property_id', function () {
    // Generate two different tenant IDs
    $tenantId1 = fake()->numberBetween(1, 1000);
    $tenantId2 = fake()->numberBetween(1001, 2000);
    
    // Create property for tenant 1
    $property1 = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId1,
        'address' => fake()->address(),
        'type' => fake()->randomElement(['apartment', 'house']),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    // Create building for tenant 2 (different tenant)
    $building2 = Building::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId2,
        'name' => fake()->company(),
        'address' => fake()->address(),
    ]);
    
    // Create tenant user assigned to property1
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $tenantId1,
        'property_id' => $property1->id,
    ]);
    
    // Act as tenant
    $this->actingAs($tenant);
    
    // Property: Tenant should not be able to access buildings from different tenant
    // (Buildings don't have property_id, so tenant_id filtering should still apply)
    $result = Building::find($building2->id);
    expect($result)->toBeNull();
    
    // Verify the building exists when accessed without scopes
    $buildingExists = Building::withoutGlobalScopes()->find($building2->id);
    expect($buildingExists)->not->toBeNull();
    expect($buildingExists->tenant_id)->toBe($tenantId2);
})->repeat(100);
