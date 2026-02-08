<?php

use App\Enums\PropertyType;
use App\Enums\UserRole;
use App\Filament\Resources\PropertyResource;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// Feature: filament-admin-panel, Property 4: Tenant scope isolation for properties
// Validates: Requirements 3.1
test('PropertyResource automatically filters properties by authenticated user tenant_id', function () {
    // Generate random tenant IDs
    $tenantId1 = fake()->numberBetween(1, 1000);
    $tenantId2 = fake()->numberBetween(1001, 2000);
    
    // Create random number of properties for each tenant
    $propertiesCount1 = fake()->numberBetween(2, 8);
    $propertiesCount2 = fake()->numberBetween(2, 8);
    
    // Create properties for tenant 1 without global scopes
    $properties1 = [];
    for ($i = 0; $i < $propertiesCount1; $i++) {
        $properties1[] = Property::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId1,
            'address' => fake()->unique()->address(),
            'type' => fake()->randomElement([PropertyType::APARTMENT, PropertyType::HOUSE]),
            'area_sqm' => fake()->randomFloat(2, 20, 200),
        ]);
    }
    
    // Create properties for tenant 2 without global scopes
    $properties2 = [];
    for ($i = 0; $i < $propertiesCount2; $i++) {
        $properties2[] = Property::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId2,
            'address' => fake()->unique()->address(),
            'type' => fake()->randomElement([PropertyType::APARTMENT, PropertyType::HOUSE]),
            'area_sqm' => fake()->randomFloat(2, 20, 200),
        ]);
    }
    
    // Create a manager for tenant 1
    $manager1 = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId1,
    ]);
    
    // Act as manager from tenant 1
    $this->actingAs($manager1);
    
    // Set session tenant_id (this is what TenantScope uses)
    session(['tenant_id' => $tenantId1]);
    
    // Property: When accessing PropertyResource list page, only tenant 1's properties should be visible
    $component = Livewire::test(PropertyResource\Pages\ListProperties::class);
    
    // Verify the component loaded successfully
    $component->assertSuccessful();
    
    // Get the table records from the component
    $tableRecords = $component->instance()->getTableRecords();
    
    // Property: All returned properties should belong to tenant 1
    expect($tableRecords)->toHaveCount($propertiesCount1);
    
    $tableRecords->each(function ($property) use ($tenantId1) {
        expect($property->tenant_id)->toBe($tenantId1);
    });
    
    // Property: Tenant 2's properties should not be accessible
    foreach ($properties2 as $property2) {
        expect(Property::find($property2->id))->toBeNull();
    }
    
    // Verify tenant 1's properties are all present in the table
    $propertyIds1 = collect($properties1)->pluck('id')->toArray();
    $tableRecordIds = $tableRecords->pluck('id')->toArray();
    
    expect($tableRecordIds)->toEqualCanonicalizing($propertyIds1);
    
    // Now switch to manager from tenant 2
    $manager2 = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId2,
    ]);
    
    $this->actingAs($manager2);
    session(['tenant_id' => $tenantId2]);
    
    // Property: When accessing PropertyResource list page, only tenant 2's properties should be visible
    $component2 = Livewire::test(PropertyResource\Pages\ListProperties::class);
    
    $component2->assertSuccessful();
    
    $tableRecords2 = $component2->instance()->getTableRecords();
    
    // Property: All returned properties should belong to tenant 2
    expect($tableRecords2)->toHaveCount($propertiesCount2);
    
    $tableRecords2->each(function ($property) use ($tenantId2) {
        expect($property->tenant_id)->toBe($tenantId2);
    });
    
    // Property: Tenant 1's properties should not be accessible
    foreach ($properties1 as $property1) {
        expect(Property::find($property1->id))->toBeNull();
    }
    
    // Verify tenant 2's properties are all present in the table
    $propertyIds2 = collect($properties2)->pluck('id')->toArray();
    $tableRecordIds2 = $tableRecords2->pluck('id')->toArray();
    
    expect($tableRecordIds2)->toEqualCanonicalizing($propertyIds2);
})->repeat(100);

// Feature: filament-admin-panel, Property 4: Tenant scope isolation for properties
// Validates: Requirements 3.1
test('PropertyResource edit page only allows editing properties within tenant scope', function () {
    // Generate random tenant IDs
    $tenantId1 = fake()->numberBetween(1, 1000);
    $tenantId2 = fake()->numberBetween(1001, 2000);
    
    // Create a property for tenant 1
    $property1 = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId1,
        'address' => fake()->address(),
        'type' => fake()->randomElement([PropertyType::APARTMENT, PropertyType::HOUSE]),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    // Create a property for tenant 2
    $property2 = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId2,
        'address' => fake()->address(),
        'type' => fake()->randomElement([PropertyType::APARTMENT, PropertyType::HOUSE]),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    // Create a manager for tenant 1
    $manager1 = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId1,
    ]);
    
    // Act as manager from tenant 1
    $this->actingAs($manager1);
    session(['tenant_id' => $tenantId1]);
    
    // Property: Manager should be able to access edit page for their tenant's property
    $component = Livewire::test(PropertyResource\Pages\EditProperty::class, [
        'record' => $property1->id,
    ]);
    
    $component->assertSuccessful();
    
    // Verify the correct property is loaded
    expect($component->instance()->record->id)->toBe($property1->id);
    expect($component->instance()->record->tenant_id)->toBe($tenantId1);
    
    // Property: Manager should NOT be able to access edit page for another tenant's property
    // This should fail because the property won't be found due to tenant scope
    try {
        $component2 = Livewire::test(PropertyResource\Pages\EditProperty::class, [
            'record' => $property2->id,
        ]);
        
        // If we get here, the test should fail because access should be denied
        expect(false)->toBeTrue('Manager should not be able to access another tenant\'s property');
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        // This is expected - the property should not be found due to tenant scope
        expect(true)->toBeTrue();
    }
})->repeat(100);

// Feature: filament-admin-panel, Property 4: Tenant scope isolation for properties
// Validates: Requirements 3.1
test('PropertyResource create page automatically assigns tenant_id from authenticated user', function () {
    // Generate random tenant ID
    $tenantId = fake()->numberBetween(1, 1000);
    
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
    
    // Property: When creating a property through Filament, tenant_id should be automatically assigned
    $component = Livewire::test(PropertyResource\Pages\CreateProperty::class);
    
    $component->assertSuccessful();
    
    // Fill the form and submit
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
    expect($createdProperty->area_sqm)->toBe($area);
})->repeat(100);
