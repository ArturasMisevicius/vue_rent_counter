<?php

use App\Enums\PropertyType;
use App\Enums\UserRole;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

// Feature: user-group-frontends, Property 7: Manager property isolation
// Validates: Requirements 5.1
test('manager viewing properties list only sees properties from their tenant', function () {
    // Generate random number of properties for each tenant
    $propertiesCount1 = fake()->numberBetween(3, 10);
    $propertiesCount2 = fake()->numberBetween(3, 10);
    
    // Create two tenants with explicit IDs to avoid scope issues
    $tenantId1 = fake()->numberBetween(1000, 5000);
    $tenantId2 = fake()->numberBetween(5001, 9000);
    
    // Create properties for tenant1 without global scopes
    $tenant1Properties = [];
    for ($i = 0; $i < $propertiesCount1; $i++) {
        $tenant1Properties[] = Property::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId1,
            'address' => fake()->unique()->address(),
            'type' => fake()->randomElement([PropertyType::APARTMENT, PropertyType::HOUSE]),
            'area_sqm' => fake()->randomFloat(2, 20, 200),
        ]);
    }
    
    // Create properties for tenant2 without global scopes
    $tenant2Properties = [];
    for ($i = 0; $i < $propertiesCount2; $i++) {
        $tenant2Properties[] = Property::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId2,
            'address' => fake()->unique()->address(),
            'type' => fake()->randomElement([PropertyType::APARTMENT, PropertyType::HOUSE]),
            'area_sqm' => fake()->randomFloat(2, 20, 200),
        ]);
    }
    
    // Create a manager for tenant1
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId1,
    ]);
    
    // Act as the manager from tenant1
    $response = $this->actingAs($manager)->get(route('manager.properties.index'));
    
    // Property: Manager should only see properties from their tenant
    $response->assertOk();
    
    // Verify all tenant1 properties are visible
    foreach ($tenant1Properties as $property) {
        $response->assertSee($property->address);
    }
    
    // Verify no tenant2 properties are visible
    foreach ($tenant2Properties as $property) {
        $response->assertDontSee($property->address);
    }
    
    // Additionally verify the query result directly
    $this->actingAs($manager);
    $queriedProperties = Property::all();
    
    // All queried properties should belong to tenant1
    expect($queriedProperties)->toHaveCount($propertiesCount1);
    expect($queriedProperties)->each(fn ($property) => 
        expect($property->tenant_id)->toBe($tenantId1)
    );
    
    // Verify tenant2 properties are not accessible via find()
    foreach ($tenant2Properties as $property) {
        expect(Property::find($property->id))->toBeNull();
    }
})->repeat(100);
