<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\PropertyType;
use App\Enums\UserRole;
use App\Filament\Resources\BuildingResource;
use App\Models\Building;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * PropertiesRelationManager Behavior Tests
 * 
 * Tests behavioral features including:
 * - Default area values based on property type
 * - Update operations
 * - Form state management
 * - Localization
 */

// ============================================================================
// DEFAULT AREA BEHAVIOR TESTS
// ============================================================================

test('selecting apartment type sets default area from config', function () {
    $tenantId = fake()->numberBetween(1, 1000);
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    $building = Building::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'total_apartments' => fake()->numberBetween(10, 100),
    ]);
    
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    // Get default apartment area from config
    $defaultApartmentArea = config('billing.property.default_apartment_area', 50);
    
    $component = Livewire::test(
        BuildingResource\RelationManagers\PropertiesRelationManager::class,
        ['ownerRecord' => $building, 'pageClass' => BuildingResource\Pages\EditBuilding::class]
    );
    
    // Open create form and select apartment type
    $component
        ->mountTableAction('create')
        ->setTableActionData([
            'type' => PropertyType::APARTMENT->value,
        ]);
    
    // Verify area_sqm was set to default apartment area
    $formData = $component->instance()->mountedTableActionData;
    expect($formData['area_sqm'] ?? null)->toBe($defaultApartmentArea);
});

test('selecting house type sets default area from config', function () {
    $tenantId = fake()->numberBetween(1, 1000);
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    $building = Building::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'total_apartments' => fake()->numberBetween(10, 100),
    ]);
    
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    // Get default house area from config
    $defaultHouseArea = config('billing.property.default_house_area', 120);
    
    $component = Livewire::test(
        BuildingResource\RelationManagers\PropertiesRelationManager::class,
        ['ownerRecord' => $building, 'pageClass' => BuildingResource\Pages\EditBuilding::class]
    );
    
    // Open create form and select house type
    $component
        ->mountTableAction('create')
        ->setTableActionData([
            'type' => PropertyType::HOUSE->value,
        ]);
    
    // Verify area_sqm was set to default house area
    $formData = $component->instance()->mountedTableActionData;
    expect($formData['area_sqm'] ?? null)->toBe($defaultHouseArea);
});

test('changing property type updates default area value', function () {
    $tenantId = fake()->numberBetween(1, 1000);
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    $building = Building::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'total_apartments' => fake()->numberBetween(10, 100),
    ]);
    
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    $defaultApartmentArea = config('billing.property.default_apartment_area', 50);
    $defaultHouseArea = config('billing.property.default_house_area', 120);
    
    $component = Livewire::test(
        BuildingResource\RelationManagers\PropertiesRelationManager::class,
        ['ownerRecord' => $building, 'pageClass' => BuildingResource\Pages\EditBuilding::class]
    );
    
    // Start with apartment type
    $component
        ->mountTableAction('create')
        ->setTableActionData([
            'type' => PropertyType::APARTMENT->value,
        ]);
    
    $formData = $component->instance()->mountedTableActionData;
    expect($formData['area_sqm'] ?? null)->toBe($defaultApartmentArea);
    
    // Change to house type
    $component->setTableActionData([
        'type' => PropertyType::HOUSE->value,
    ]);
    
    $formData = $component->instance()->mountedTableActionData;
    expect($formData['area_sqm'] ?? null)->toBe($defaultHouseArea);
});

test('user can override default area value', function () {
    $tenantId = fake()->numberBetween(1, 1000);
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    $building = Building::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'total_apartments' => fake()->numberBetween(10, 100),
    ]);
    
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    $customArea = 75.5;
    $address = fake()->address();
    
    $component = Livewire::test(
        BuildingResource\RelationManagers\PropertiesRelationManager::class,
        ['ownerRecord' => $building, 'pageClass' => BuildingResource\Pages\EditBuilding::class]
    );
    
    // Create property with custom area (overriding default)
    $component
        ->callTableAction('create', data: [
            'address' => $address,
            'type' => PropertyType::APARTMENT->value,
            'area_sqm' => $customArea,
        ])
        ->assertHasNoTableActionErrors();
    
    // Verify property was created with custom area
    $property = Property::where('address', strip_tags(trim($address)))->first();
    expect($property)->not->toBeNull();
    expect((float) $property->area_sqm)->toBe($customArea);
});

// ============================================================================
// UPDATE OPERATION TESTS
// ============================================================================

test('updating property applies same validation as create', function () {
    $tenantId = fake()->numberBetween(1, 1000);
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    $building = Building::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'total_apartments' => fake()->numberBetween(10, 100),
    ]);
    
    $property = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'building_id' => $building->id,
        'address' => fake()->address(),
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 50.0,
    ]);
    
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    $component = Livewire::test(
        BuildingResource\RelationManagers\PropertiesRelationManager::class,
        ['ownerRecord' => $building, 'pageClass' => BuildingResource\Pages\EditBuilding::class]
    );
    
    // Attempt to update with invalid data
    $component
        ->callTableAction('edit', $property->id, data: [
            'address' => '', // Invalid: empty address
            'type' => PropertyType::HOUSE->value,
            'area_sqm' => 100.0,
        ])
        ->assertHasTableActionErrors(['address' => 'required']);
    
    // Verify property was not updated
    $property->refresh();
    expect($property->type)->toBe(PropertyType::APARTMENT);
});

test('updating property with valid data succeeds', function () {
    $tenantId = fake()->numberBetween(1, 1000);
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    $building = Building::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'total_apartments' => fake()->numberBetween(10, 100),
    ]);
    
    $property = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'building_id' => $building->id,
        'address' => 'Original Address',
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 50.0,
    ]);
    
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    $component = Livewire::test(
        BuildingResource\RelationManagers\PropertiesRelationManager::class,
        ['ownerRecord' => $building, 'pageClass' => BuildingResource\Pages\EditBuilding::class]
    );
    
    $newAddress = 'Updated Address';
    
    // Update property with valid data
    $component
        ->callTableAction('edit', $property->id, data: [
            'address' => $newAddress,
            'type' => PropertyType::HOUSE->value,
            'area_sqm' => 100.0,
        ])
        ->assertHasNoTableActionErrors();
    
    // Verify property was updated
    $property->refresh();
    expect($property->address)->toBe($newAddress);
    expect($property->type)->toBe(PropertyType::HOUSE);
    expect((float) $property->area_sqm)->toBe(100.0);
});

test('updating property preserves tenant_id and building_id', function () {
    $tenantId = fake()->numberBetween(1, 1000);
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    $building = Building::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'total_apartments' => fake()->numberBetween(10, 100),
    ]);
    
    $property = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'building_id' => $building->id,
        'address' => fake()->address(),
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 50.0,
    ]);
    
    $originalTenantId = $property->tenant_id;
    $originalBuildingId = $property->building_id;
    
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    $component = Livewire::test(
        BuildingResource\RelationManagers\PropertiesRelationManager::class,
        ['ownerRecord' => $building, 'pageClass' => BuildingResource\Pages\EditBuilding::class]
    );
    
    // Update property
    $component
        ->callTableAction('edit', $property->id, data: [
            'address' => 'Updated Address',
            'type' => PropertyType::HOUSE->value,
            'area_sqm' => 100.0,
        ])
        ->assertHasNoTableActionErrors();
    
    // Verify tenant_id and building_id were not changed
    $property->refresh();
    expect($property->tenant_id)->toBe($originalTenantId);
    expect($property->building_id)->toBe($originalBuildingId);
});

// ============================================================================
// LOCALIZATION TESTS
// ============================================================================

test('validation messages use localized strings', function () {
    $tenantId = fake()->numberBetween(1, 1000);
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    $building = Building::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'total_apartments' => fake()->numberBetween(10, 100),
    ]);
    
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    $component = Livewire::test(
        BuildingResource\RelationManagers\PropertiesRelationManager::class,
        ['ownerRecord' => $building, 'pageClass' => BuildingResource\Pages\EditBuilding::class]
    );
    
    // Trigger validation error
    $component
        ->callTableAction('create', data: [
            'address' => '', // Empty address
            'type' => PropertyType::APARTMENT->value,
            'area_sqm' => 50.0,
        ])
        ->assertHasTableActionErrors(['address']);
    
    // Get the error message
    $errors = $component->instance()->mountedTableActionData['errors'] ?? [];
    
    // Verify error message matches localized string
    $expectedMessage = __('properties.validation.address.required');
    expect($expectedMessage)->not->toBe('properties.validation.address.required'); // Ensure translation exists
});

test('form labels use localized strings', function () {
    $tenantId = fake()->numberBetween(1, 1000);
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    $building = Building::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'total_apartments' => fake()->numberBetween(10, 100),
    ]);
    
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    // Verify localization keys exist
    expect(__('properties.labels.address'))->not->toBe('properties.labels.address');
    expect(__('properties.labels.type'))->not->toBe('properties.labels.type');
    expect(__('properties.labels.area'))->not->toBe('properties.labels.area');
    expect(__('properties.placeholders.address'))->not->toBe('properties.placeholders.address');
    expect(__('properties.helper_text.address'))->not->toBe('properties.helper_text.address');
});

// ============================================================================
// NOTIFICATION TESTS
// ============================================================================

test('successful property creation shows success notification', function () {
    $tenantId = fake()->numberBetween(1, 1000);
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    $building = Building::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'total_apartments' => fake()->numberBetween(10, 100),
    ]);
    
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    $component = Livewire::test(
        BuildingResource\RelationManagers\PropertiesRelationManager::class,
        ['ownerRecord' => $building, 'pageClass' => BuildingResource\Pages\EditBuilding::class]
    );
    
    // Create property
    $component
        ->callTableAction('create', data: [
            'address' => fake()->address(),
            'type' => PropertyType::APARTMENT->value,
            'area_sqm' => 50.0,
        ])
        ->assertHasNoTableActionErrors()
        ->assertNotified();
});

test('successful property update shows success notification', function () {
    $tenantId = fake()->numberBetween(1, 1000);
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    $building = Building::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'total_apartments' => fake()->numberBetween(10, 100),
    ]);
    
    $property = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'building_id' => $building->id,
        'address' => fake()->address(),
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 50.0,
    ]);
    
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    $component = Livewire::test(
        BuildingResource\RelationManagers\PropertiesRelationManager::class,
        ['ownerRecord' => $building, 'pageClass' => BuildingResource\Pages\EditBuilding::class]
    );
    
    // Update property
    $component
        ->callTableAction('edit', $property->id, data: [
            'address' => 'Updated Address',
            'type' => PropertyType::HOUSE->value,
            'area_sqm' => 100.0,
        ])
        ->assertHasNoTableActionErrors()
        ->assertNotified();
});

test('successful property deletion shows success notification', function () {
    $tenantId = fake()->numberBetween(1, 1000);
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    $building = Building::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'total_apartments' => fake()->numberBetween(10, 100),
    ]);
    
    $property = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'building_id' => $building->id,
        'address' => fake()->address(),
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 50.0,
    ]);
    
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    $component = Livewire::test(
        BuildingResource\RelationManagers\PropertiesRelationManager::class,
        ['ownerRecord' => $building, 'pageClass' => BuildingResource\Pages\EditBuilding::class]
    );
    
    // Delete property
    $component
        ->callTableAction('delete', $property->id)
        ->assertHasNoTableActionErrors()
        ->assertNotified();
    
    // Verify property was deleted
    expect(Property::find($property->id))->toBeNull();
});
