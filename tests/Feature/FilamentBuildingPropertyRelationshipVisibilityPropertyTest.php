<?php

use App\Enums\PropertyType;
use App\Enums\UserRole;
use App\Filament\Resources\BuildingResource;
use App\Models\Building;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// Feature: filament-admin-panel, Property 18: Building-property relationship visibility
// Validates: Requirements 7.5
test('BuildingResource displays all associated properties in relationship manager', function () {
    // Generate random tenant ID
    $tenantId = fake()->numberBetween(1, 1000);
    
    // Create a building for the tenant
    $building = Building::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'total_apartments' => fake()->numberBetween(10, 100),
    ]);
    
    // Generate random number of properties (between 1 and 15)
    $propertiesCount = fake()->numberBetween(1, 15);
    $createdProperties = [];
    
    for ($i = 0; $i < $propertiesCount; $i++) {
        $property = Property::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId,
            'building_id' => $building->id,
            'address' => fake()->address(),
            'type' => fake()->randomElement([PropertyType::APARTMENT, PropertyType::HOUSE]),
            'area_sqm' => fake()->randomFloat(2, 20, 200),
        ]);
        
        $createdProperties[] = $property;
    }
    
    // Create a manager for the tenant
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    // Act as the manager
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    // Property: When viewing a building edit page, all associated properties should be accessible
    $component = Livewire::test(BuildingResource\Pages\EditBuilding::class, [
        'record' => $building->id,
    ]);
    
    $component->assertSuccessful();
    
    // Verify the building is loaded
    expect($component->instance()->record->id)->toBe($building->id);
    
    // Get the properties through the relationship
    $buildingProperties = $component->instance()->record->properties;
    
    // Property: All created properties should be present
    expect($buildingProperties)->toHaveCount($propertiesCount);
    
    // Property: Each property should have all required details
    foreach ($createdProperties as $createdProperty) {
        $foundProperty = $buildingProperties->firstWhere('id', $createdProperty->id);
        
        expect($foundProperty)->not->toBeNull();
        expect($foundProperty->address)->toBe($createdProperty->address);
        expect($foundProperty->type)->toBe($createdProperty->type);
        expect($foundProperty->area_sqm)->toBe($createdProperty->area_sqm);
        expect($foundProperty->building_id)->toBe($building->id);
        expect($foundProperty->tenant_id)->toBe($tenantId);
    }
    
    // Verify the relation manager can be accessed
    $relationManager = Livewire::test(
        BuildingResource\RelationManagers\PropertiesRelationManager::class,
        [
            'ownerRecord' => $building,
            'pageClass' => BuildingResource\Pages\EditBuilding::class,
        ]
    );
    
    $relationManager->assertSuccessful();
    
    // Get table records from the relation manager
    $tableRecords = $relationManager->instance()->getTableRecords();
    
    // Property: All properties should be visible in the relation manager table
    expect($tableRecords)->toHaveCount($propertiesCount);
    
    // Property: Each property in the table should match the created properties
    $tableRecords->each(function ($tableProperty) use ($createdProperties, $building) {
        $matchingProperty = collect($createdProperties)->firstWhere('id', $tableProperty->id);
        
        expect($matchingProperty)->not->toBeNull();
        expect($tableProperty->address)->toBe($matchingProperty->address);
        expect($tableProperty->type)->toBe($matchingProperty->type);
        expect($tableProperty->building_id)->toBe($building->id);
        
        // Verify area matches (with decimal precision)
        expect(number_format((float) $tableProperty->area_sqm, 2))
            ->toBe(number_format((float) $matchingProperty->area_sqm, 2));
    });
})->repeat(100);

// Feature: filament-admin-panel, Property 18: Building-property relationship visibility
// Validates: Requirements 7.5
test('BuildingResource displays properties even when building has no properties', function () {
    // Generate random tenant ID
    $tenantId = fake()->numberBetween(1, 1000);
    
    // Create a building without any properties
    $building = Building::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'total_apartments' => fake()->numberBetween(10, 100),
    ]);
    
    // Create a manager for the tenant
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    // Act as the manager
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    // Property: When viewing a building with no properties, the relation manager should still be accessible
    $component = Livewire::test(BuildingResource\Pages\EditBuilding::class, [
        'record' => $building->id,
    ]);
    
    $component->assertSuccessful();
    
    // Get the properties through the relationship
    $buildingProperties = $component->instance()->record->properties;
    
    // Property: Building should have zero properties
    expect($buildingProperties)->toHaveCount(0);
    
    // Verify the relation manager can be accessed even with no properties
    $relationManager = Livewire::test(
        BuildingResource\RelationManagers\PropertiesRelationManager::class,
        [
            'ownerRecord' => $building,
            'pageClass' => BuildingResource\Pages\EditBuilding::class,
        ]
    );
    
    $relationManager->assertSuccessful();
    
    // Get table records from the relation manager
    $tableRecords = $relationManager->instance()->getTableRecords();
    
    // Property: Table should show zero properties
    expect($tableRecords)->toHaveCount(0);
})->repeat(100);

// Feature: filament-admin-panel, Property 18: Building-property relationship visibility
// Validates: Requirements 7.5
test('BuildingResource only displays properties belonging to the building', function () {
    // Generate random tenant ID
    $tenantId = fake()->numberBetween(1, 1000);
    
    // Create two buildings for the same tenant
    $building1 = Building::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'total_apartments' => fake()->numberBetween(10, 50),
    ]);
    
    $building2 = Building::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'total_apartments' => fake()->numberBetween(10, 50),
    ]);
    
    // Create properties for building 1
    $building1PropertiesCount = fake()->numberBetween(2, 8);
    $building1Properties = [];
    
    for ($i = 0; $i < $building1PropertiesCount; $i++) {
        $building1Properties[] = Property::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId,
            'building_id' => $building1->id,
            'address' => fake()->address(),
            'type' => fake()->randomElement([PropertyType::APARTMENT, PropertyType::HOUSE]),
            'area_sqm' => fake()->randomFloat(2, 20, 200),
        ]);
    }
    
    // Create properties for building 2
    $building2PropertiesCount = fake()->numberBetween(2, 8);
    $building2Properties = [];
    
    for ($i = 0; $i < $building2PropertiesCount; $i++) {
        $building2Properties[] = Property::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId,
            'building_id' => $building2->id,
            'address' => fake()->address(),
            'type' => fake()->randomElement([PropertyType::APARTMENT, PropertyType::HOUSE]),
            'area_sqm' => fake()->randomFloat(2, 20, 200),
        ]);
    }
    
    // Create a manager for the tenant
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    // Act as the manager
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    // Property: When viewing building 1, only building 1's properties should be visible
    $relationManager1 = Livewire::test(
        BuildingResource\RelationManagers\PropertiesRelationManager::class,
        [
            'ownerRecord' => $building1,
            'pageClass' => BuildingResource\Pages\EditBuilding::class,
        ]
    );
    
    $relationManager1->assertSuccessful();
    
    $tableRecords1 = $relationManager1->instance()->getTableRecords();
    
    // Property: Only building 1's properties should be present
    expect($tableRecords1)->toHaveCount($building1PropertiesCount);
    
    $tableRecords1->each(function ($property) use ($building1, $building2Properties) {
        expect($property->building_id)->toBe($building1->id);
        
        // Verify this property is not from building 2
        $isFromBuilding2 = collect($building2Properties)->contains('id', $property->id);
        expect($isFromBuilding2)->toBeFalse();
    });
    
    // Property: When viewing building 2, only building 2's properties should be visible
    $relationManager2 = Livewire::test(
        BuildingResource\RelationManagers\PropertiesRelationManager::class,
        [
            'ownerRecord' => $building2,
            'pageClass' => BuildingResource\Pages\EditBuilding::class,
        ]
    );
    
    $relationManager2->assertSuccessful();
    
    $tableRecords2 = $relationManager2->instance()->getTableRecords();
    
    // Property: Only building 2's properties should be present
    expect($tableRecords2)->toHaveCount($building2PropertiesCount);
    
    $tableRecords2->each(function ($property) use ($building2, $building1Properties) {
        expect($property->building_id)->toBe($building2->id);
        
        // Verify this property is not from building 1
        $isFromBuilding1 = collect($building1Properties)->contains('id', $property->id);
        expect($isFromBuilding1)->toBeFalse();
    });
})->repeat(100);

// Feature: filament-admin-panel, Property 18: Building-property relationship visibility
// Validates: Requirements 7.5
test('BuildingResource relationship manager respects tenant scope', function () {
    // Generate two different tenant IDs
    $tenantId1 = fake()->numberBetween(1, 1000);
    $tenantId2 = fake()->numberBetween(1001, 2000);
    
    // Create a building for tenant 1
    $building1 = Building::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId1,
        'address' => fake()->address(),
        'total_apartments' => fake()->numberBetween(10, 50),
    ]);
    
    // Create properties for building 1 (tenant 1)
    $building1PropertiesCount = fake()->numberBetween(2, 8);
    $building1Properties = [];
    
    for ($i = 0; $i < $building1PropertiesCount; $i++) {
        $building1Properties[] = Property::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId1,
            'building_id' => $building1->id,
            'address' => fake()->address(),
            'type' => fake()->randomElement([PropertyType::APARTMENT, PropertyType::HOUSE]),
            'area_sqm' => fake()->randomFloat(2, 20, 200),
        ]);
    }
    
    // Create a building for tenant 2
    $building2 = Building::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId2,
        'address' => fake()->address(),
        'total_apartments' => fake()->numberBetween(10, 50),
    ]);
    
    // Create properties for building 2 (tenant 2)
    $building2PropertiesCount = fake()->numberBetween(2, 8);
    
    for ($i = 0; $i < $building2PropertiesCount; $i++) {
        Property::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId2,
            'building_id' => $building2->id,
            'address' => fake()->address(),
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
    session(['tenant_id' => $tenantId1]);
    
    // Property: Manager from tenant 1 should only see building 1's properties
    $relationManager1 = Livewire::test(
        BuildingResource\RelationManagers\PropertiesRelationManager::class,
        [
            'ownerRecord' => $building1,
            'pageClass' => BuildingResource\Pages\EditBuilding::class,
        ]
    );
    
    $relationManager1->assertSuccessful();
    
    $tableRecords1 = $relationManager1->instance()->getTableRecords();
    
    // Property: All properties should belong to tenant 1
    expect($tableRecords1)->toHaveCount($building1PropertiesCount);
    
    $tableRecords1->each(function ($property) use ($tenantId1, $building1) {
        expect($property->tenant_id)->toBe($tenantId1);
        expect($property->building_id)->toBe($building1->id);
    });
    
    // Property: Manager from tenant 1 should NOT be able to access building 2's properties
    // This should fail because the building won't be found due to tenant scope
    try {
        $relationManager2 = Livewire::test(
            BuildingResource\RelationManagers\PropertiesRelationManager::class,
            [
                'ownerRecord' => $building2,
                'pageClass' => BuildingResource\Pages\EditBuilding::class,
            ]
        );
        
        // If we get here, the test should fail because access should be denied
        expect(false)->toBeTrue('Manager should not be able to access another tenant\'s building properties');
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        // This is expected - the building should not be found due to tenant scope
        expect(true)->toBeTrue();
    }
})->repeat(100);
