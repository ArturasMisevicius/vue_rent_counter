<?php

use App\Enums\PropertyType;
use App\Enums\UserRole;
use App\Filament\Resources\PropertyResource;
use App\Models\Building;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// Feature: filament-admin-panel, Property 6: Automatic tenant assignment
// Validates: Requirements 3.5
test('PropertyResource automatically assigns tenant_id from authenticated manager user when creating property', function () {
    // Generate random tenant ID
    $tenantId = fake()->numberBetween(1, 10000);
    
    // Create a manager for the tenant
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    // Act as the manager
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    // Generate random property data
    $address = fake()->address();
    $type = fake()->randomElement([PropertyType::APARTMENT->value, PropertyType::HOUSE->value]);
    $area = fake()->randomFloat(2, 20, 200);
    
    // Property: When creating a property through Filament, tenant_id should be automatically assigned from the authenticated user
    $component = Livewire::test(PropertyResource\Pages\CreateProperty::class);
    
    $component->assertSuccessful();
    
    // Fill the form WITHOUT providing tenant_id (it should be auto-assigned)
    $component
        ->fillForm([
            'address' => $address,
            'type' => $type,
            'area_sqm' => $area,
        ])
        ->call('create');
    
    // Verify the property was created with the correct tenant_id
    $createdProperty = Property::withoutGlobalScopes()
        ->where('address', $address)
        ->first();
    
    expect($createdProperty)->not->toBeNull();
    expect($createdProperty->tenant_id)->toBe($tenantId);
    expect($createdProperty->address)->toBe($address);
    expect($createdProperty->type->value)->toBe($type);
    expect((float)$createdProperty->area_sqm)->toBe($area);
})->repeat(100);

// Feature: filament-admin-panel, Property 6: Automatic tenant assignment
// Validates: Requirements 3.5
test('PropertyResource assigns tenant_id from authenticated user even when building is specified', function () {
    // Generate random tenant ID
    $tenantId = fake()->numberBetween(1, 10000);
    
    // Create a manager for the tenant
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    // Create a building for the tenant
    $building = Building::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'name' => fake()->company(),
        'address' => fake()->address(),
        'total_area_sqm' => fake()->randomFloat(2, 500, 5000),
        'total_apartments' => fake()->numberBetween(1, 50),
    ]);
    
    // Act as the manager
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    // Generate random property data with building
    $address = fake()->address();
    $type = fake()->randomElement([PropertyType::APARTMENT->value, PropertyType::HOUSE->value]);
    $area = fake()->randomFloat(2, 20, 200);
    
    // Property: tenant_id should be automatically assigned even when building_id is provided
    $component = Livewire::test(PropertyResource\Pages\CreateProperty::class);
    
    $component->assertSuccessful();
    
    // Fill the form with building_id but WITHOUT tenant_id
    $component
        ->fillForm([
            'address' => $address,
            'type' => $type,
            'area_sqm' => $area,
            'building_id' => $building->id,
        ])
        ->call('create');
    
    // Verify the property was created with the correct tenant_id
    $createdProperty = Property::withoutGlobalScopes()
        ->where('address', $address)
        ->first();
    
    expect($createdProperty)->not->toBeNull();
    expect($createdProperty->tenant_id)->toBe($tenantId);
    expect($createdProperty->building_id)->toBe($building->id);
    expect($createdProperty->address)->toBe($address);
})->repeat(100);

// Feature: filament-admin-panel, Property 6: Automatic tenant assignment
// Validates: Requirements 3.5
test('PropertyResource assigns different tenant_ids for different authenticated users', function () {
    // Generate two different random tenant IDs
    $tenantId1 = fake()->numberBetween(1, 5000);
    $tenantId2 = fake()->numberBetween(5001, 10000);
    
    // Create managers for both tenants
    $manager1 = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId1,
    ]);
    
    $manager2 = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId2,
    ]);
    
    // Generate random property data for first property
    $address1 = fake()->unique()->address();
    $type1 = fake()->randomElement([PropertyType::APARTMENT->value, PropertyType::HOUSE->value]);
    $area1 = fake()->randomFloat(2, 20, 200);
    
    // Act as manager 1 and create property
    $this->actingAs($manager1);
    session(['tenant_id' => $tenantId1]);
    
    $component1 = Livewire::test(PropertyResource\Pages\CreateProperty::class);
    
    $component1
        ->fillForm([
            'address' => $address1,
            'type' => $type1,
            'area_sqm' => $area1,
        ])
        ->call('create');
    
    // Verify first property has tenant_id from manager 1
    $property1 = Property::withoutGlobalScopes()
        ->where('address', $address1)
        ->first();
    
    expect($property1)->not->toBeNull();
    expect($property1->tenant_id)->toBe($tenantId1);
    
    // Generate random property data for second property
    $address2 = fake()->unique()->address();
    $type2 = fake()->randomElement([PropertyType::APARTMENT->value, PropertyType::HOUSE->value]);
    $area2 = fake()->randomFloat(2, 20, 200);
    
    // Act as manager 2 and create property
    $this->actingAs($manager2);
    session(['tenant_id' => $tenantId2]);
    
    $component2 = Livewire::test(PropertyResource\Pages\CreateProperty::class);
    
    $component2
        ->fillForm([
            'address' => $address2,
            'type' => $type2,
            'area_sqm' => $area2,
        ])
        ->call('create');
    
    // Verify second property has tenant_id from manager 2
    $property2 = Property::withoutGlobalScopes()
        ->where('address', $address2)
        ->first();
    
    expect($property2)->not->toBeNull();
    expect($property2->tenant_id)->toBe($tenantId2);
    
    // Property: The two properties should have different tenant_ids matching their creators
    expect($property1->tenant_id)->not->toBe($property2->tenant_id);
    expect($property1->tenant_id)->toBe($tenantId1);
    expect($property2->tenant_id)->toBe($tenantId2);
})->repeat(100);

// Feature: filament-admin-panel, Property 6: Automatic tenant assignment
// Validates: Requirements 3.5
test('PropertyResource tenant_id assignment persists correctly in database', function () {
    // Generate random tenant ID
    $tenantId = fake()->numberBetween(1, 10000);
    
    // Create a manager for the tenant
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    // Act as the manager
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    // Generate random property data
    $address = fake()->address();
    $type = fake()->randomElement([PropertyType::APARTMENT->value, PropertyType::HOUSE->value]);
    $area = fake()->randomFloat(2, 20, 200);
    
    // Create property through Filament
    $component = Livewire::test(PropertyResource\Pages\CreateProperty::class);
    
    $component
        ->fillForm([
            'address' => $address,
            'type' => $type,
            'area_sqm' => $area,
        ])
        ->call('create');
    
    // Retrieve the property directly from database without scopes
    $createdProperty = Property::withoutGlobalScopes()
        ->where('address', $address)
        ->first();
    
    // Property: tenant_id should be persisted in the database
    expect($createdProperty)->not->toBeNull();
    expect($createdProperty->tenant_id)->toBe($tenantId);
    
    // Verify by querying with a fresh database connection
    $freshProperty = Property::withoutGlobalScopes()
        ->find($createdProperty->id);
    
    expect($freshProperty->tenant_id)->toBe($tenantId);
    
    // Verify the property is accessible when querying with tenant scope
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    $scopedProperty = Property::find($createdProperty->id);
    expect($scopedProperty)->not->toBeNull();
    expect($scopedProperty->id)->toBe($createdProperty->id);
})->repeat(100);
