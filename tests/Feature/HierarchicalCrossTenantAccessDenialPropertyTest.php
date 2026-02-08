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

// Feature: hierarchical-user-management, Property 7: Cross-tenant access denial
// Validates: Requirements 12.5, 13.3
test('admin attempting to access property from different tenant gets 404', function () {
    // Generate two different tenant IDs
    $tenantId1 = fake()->numberBetween(1, 1000);
    $tenantId2 = fake()->numberBetween(1001, 2000);
    
    // Create admin for tenant 1
    $admin1 = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenantId1,
    ]);
    
    // Create admin for tenant 2
    $admin2 = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenantId2,
    ]);
    
    // Create property for tenant 2
    $property2 = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId2,
        'address' => fake()->address(),
        'type' => fake()->randomElement(['apartment', 'house']),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    // Act as admin1 (tenant 1)
    $this->actingAs($admin1);
    
    // Property: Admin from tenant 1 attempting to access tenant 2's property should get null (404)
    $result = Property::find($property2->id);
    expect($result)->toBeNull();
    
    // Verify the property exists when accessed without scopes
    $propertyExists = Property::withoutGlobalScopes()->find($property2->id);
    expect($propertyExists)->not->toBeNull();
    expect($propertyExists->tenant_id)->toBe($tenantId2);
})->repeat(100);

// Feature: hierarchical-user-management, Property 7: Cross-tenant access denial
// Validates: Requirements 12.5, 13.3
test('admin attempting to access building from different tenant gets 404', function () {
    // Generate two different tenant IDs
    $tenantId1 = fake()->numberBetween(1, 1000);
    $tenantId2 = fake()->numberBetween(1001, 2000);
    
    // Create admin for tenant 1
    $admin1 = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenantId1,
    ]);
    
    // Create building for tenant 2
    $building2 = Building::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId2,
        'name' => fake()->company(),
        'address' => fake()->address(),
    ]);
    
    // Act as admin1 (tenant 1)
    $this->actingAs($admin1);
    
    // Property: Admin from tenant 1 attempting to access tenant 2's building should get null (404)
    $result = Building::find($building2->id);
    expect($result)->toBeNull();
    
    // Verify the building exists when accessed without scopes
    $buildingExists = Building::withoutGlobalScopes()->find($building2->id);
    expect($buildingExists)->not->toBeNull();
    expect($buildingExists->tenant_id)->toBe($tenantId2);
})->repeat(100);

// Feature: hierarchical-user-management, Property 7: Cross-tenant access denial
// Validates: Requirements 12.5, 13.3
test('admin attempting to access meter from different tenant gets 404', function () {
    // Generate two different tenant IDs
    $tenantId1 = fake()->numberBetween(1, 1000);
    $tenantId2 = fake()->numberBetween(1001, 2000);
    
    // Create admin for tenant 1
    $admin1 = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenantId1,
    ]);
    
    // Create meter for tenant 2
    $meter2 = Meter::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId2,
        'serial_number' => fake()->unique()->numerify('LT-####-####'),
        'type' => fake()->randomElement(['electricity', 'water_cold', 'water_hot', 'heating']),
        'installation_date' => fake()->dateTimeBetween('-5 years', 'now'),
        'supports_zones' => fake()->boolean(30),
    ]);
    
    // Act as admin1 (tenant 1)
    $this->actingAs($admin1);
    
    // Property: Admin from tenant 1 attempting to access tenant 2's meter should get null (404)
    $result = Meter::find($meter2->id);
    expect($result)->toBeNull();
    
    // Verify the meter exists when accessed without scopes
    $meterExists = Meter::withoutGlobalScopes()->find($meter2->id);
    expect($meterExists)->not->toBeNull();
    expect($meterExists->tenant_id)->toBe($tenantId2);
})->repeat(100);

// Feature: hierarchical-user-management, Property 7: Cross-tenant access denial
// Validates: Requirements 12.5, 13.3
test('admin attempting to access invoice from different tenant gets 404', function () {
    // Generate two different tenant IDs
    $tenantId1 = fake()->numberBetween(1, 1000);
    $tenantId2 = fake()->numberBetween(1001, 2000);
    
    // Create admin for tenant 1
    $admin1 = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenantId1,
    ]);
    
    // Create invoice for tenant 2
    $invoice2 = Invoice::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId2,
        'billing_period_start' => fake()->dateTimeBetween('-2 months', '-1 month'),
        'billing_period_end' => fake()->dateTimeBetween('-1 month', 'now'),
        'total_amount' => fake()->randomFloat(2, 50, 500),
        'status' => fake()->randomElement(['draft', 'finalized', 'paid']),
    ]);
    
    // Act as admin1 (tenant 1)
    $this->actingAs($admin1);
    
    // Property: Admin from tenant 1 attempting to access tenant 2's invoice should get null (404)
    $result = Invoice::find($invoice2->id);
    expect($result)->toBeNull();
    
    // Verify the invoice exists when accessed without scopes
    $invoiceExists = Invoice::withoutGlobalScopes()->find($invoice2->id);
    expect($invoiceExists)->not->toBeNull();
    expect($invoiceExists->tenant_id)->toBe($tenantId2);
})->repeat(100);

// Feature: hierarchical-user-management, Property 7: Cross-tenant access denial
// Validates: Requirements 12.5, 13.3
test('admin attempting to access meter reading from different tenant gets 404', function () {
    // Generate two different tenant IDs
    $tenantId1 = fake()->numberBetween(1, 1000);
    $tenantId2 = fake()->numberBetween(1001, 2000);
    
    // Create admin for tenant 1
    $admin1 = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenantId1,
    ]);
    
    // Create user for entered_by field
    $user2 = User::factory()->create(['tenant_id' => $tenantId2]);
    
    // Create meter reading for tenant 2
    $reading2 = MeterReading::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId2,
        'meter_id' => fake()->numberBetween(1, 100),
        'reading_date' => fake()->dateTimeBetween('-1 month', 'now'),
        'value' => fake()->randomFloat(2, 0, 10000),
        'entered_by' => $user2->id,
    ]);
    
    // Act as admin1 (tenant 1)
    $this->actingAs($admin1);
    
    // Property: Admin from tenant 1 attempting to access tenant 2's meter reading should get null (404)
    $result = MeterReading::find($reading2->id);
    expect($result)->toBeNull();
    
    // Verify the meter reading exists when accessed without scopes
    $readingExists = MeterReading::withoutGlobalScopes()->find($reading2->id);
    expect($readingExists)->not->toBeNull();
    expect($readingExists->tenant_id)->toBe($tenantId2);
})->repeat(100);

// Feature: hierarchical-user-management, Property 7: Cross-tenant access denial
// Validates: Requirements 12.5, 13.3
test('admin attempting to query all resources only sees their own tenant data', function () {
    // Generate two different tenant IDs
    $tenantId1 = fake()->numberBetween(1, 1000);
    $tenantId2 = fake()->numberBetween(1001, 2000);
    
    // Create admin for tenant 1
    $admin1 = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenantId1,
    ]);
    
    // Create random number of resources for each tenant
    $propertiesCount1 = fake()->numberBetween(1, 5);
    $propertiesCount2 = fake()->numberBetween(1, 5);
    
    // Create properties for tenant 1
    for ($i = 0; $i < $propertiesCount1; $i++) {
        Property::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId1,
            'address' => fake()->address(),
            'type' => fake()->randomElement(['apartment', 'house']),
            'area_sqm' => fake()->randomFloat(2, 20, 200),
        ]);
    }
    
    // Create properties for tenant 2
    for ($i = 0; $i < $propertiesCount2; $i++) {
        Property::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId2,
            'address' => fake()->address(),
            'type' => fake()->randomElement(['apartment', 'house']),
            'area_sqm' => fake()->randomFloat(2, 20, 200),
        ]);
    }
    
    // Act as admin1 (tenant 1)
    $this->actingAs($admin1);
    
    // Property: Admin should only see their own tenant's data
    $properties = Property::all();
    expect($properties)->toHaveCount($propertiesCount1);
    
    // Verify all returned properties belong to tenant 1
    foreach ($properties as $property) {
        expect($property->tenant_id)->toBe($tenantId1);
    }
})->repeat(100);

// Feature: hierarchical-user-management, Property 7: Cross-tenant access denial
// Validates: Requirements 12.5, 13.3
test('admin attempting to access another admin user account gets 404', function () {
    // Generate two different tenant IDs
    $tenantId1 = fake()->numberBetween(1, 1000);
    $tenantId2 = fake()->numberBetween(1001, 2000);
    
    // Create admin for tenant 1
    $admin1 = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenantId1,
    ]);
    
    // Create admin for tenant 2
    $admin2 = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenantId2,
    ]);
    
    // Act as admin1 (tenant 1)
    $this->actingAs($admin1);
    
    // Property: Admin from tenant 1 attempting to access admin from tenant 2 should get null (404)
    $result = User::find($admin2->id);
    expect($result)->toBeNull();
    
    // Verify the admin exists when accessed without scopes
    $adminExists = User::withoutGlobalScopes()->find($admin2->id);
    expect($adminExists)->not->toBeNull();
    expect($adminExists->tenant_id)->toBe($tenantId2);
})->repeat(100);

// Feature: hierarchical-user-management, Property 7: Cross-tenant access denial
// Validates: Requirements 12.5, 13.3
test('manager attempting to access resources from different tenant gets 404', function () {
    // Generate two different tenant IDs
    $tenantId1 = fake()->numberBetween(1, 1000);
    $tenantId2 = fake()->numberBetween(1001, 2000);
    
    // Create manager for tenant 1
    $manager1 = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId1,
    ]);
    
    // Create property for tenant 2
    $property2 = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId2,
        'address' => fake()->address(),
        'type' => fake()->randomElement(['apartment', 'house']),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    // Create building for tenant 2
    $building2 = Building::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId2,
        'name' => fake()->company(),
        'address' => fake()->address(),
    ]);
    
    // Act as manager1 (tenant 1)
    $this->actingAs($manager1);
    
    // Property: Manager from tenant 1 attempting to access tenant 2's resources should get null (404)
    $propertyResult = Property::find($property2->id);
    expect($propertyResult)->toBeNull();
    
    $buildingResult = Building::find($building2->id);
    expect($buildingResult)->toBeNull();
    
    // Verify the resources exist when accessed without scopes
    $propertyExists = Property::withoutGlobalScopes()->find($property2->id);
    expect($propertyExists)->not->toBeNull();
    expect($propertyExists->tenant_id)->toBe($tenantId2);
    
    $buildingExists = Building::withoutGlobalScopes()->find($building2->id);
    expect($buildingExists)->not->toBeNull();
    expect($buildingExists->tenant_id)->toBe($tenantId2);
})->repeat(100);
