<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\PropertyType;
use App\Enums\UserRole;
use App\Filament\Resources\BuildingResource;
use App\Models\Building;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * PropertiesRelationManager Validation Tests
 * 
 * Tests the validation rules integrated from StorePropertyRequest and UpdatePropertyRequest
 * into the PropertiesRelationManager form. Ensures consistency between API and Filament validation.
 */

// ============================================================================
// ADDRESS FIELD VALIDATION TESTS
// ============================================================================

test('address field is required when creating property via relation manager', function () {
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
    
    $component
        ->callTableAction('create', data: [
            'address' => '', // Empty address
            'type' => PropertyType::APARTMENT->value,
            'area_sqm' => 50.0,
        ])
        ->assertHasTableActionErrors(['address' => 'required']);
});

test('address field cannot exceed 255 characters', function () {
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
    
    $component
        ->callTableAction('create', data: [
            'address' => str_repeat('a', 256), // 256 characters
            'type' => PropertyType::APARTMENT->value,
            'area_sqm' => 50.0,
        ])
        ->assertHasTableActionErrors(['address' => 'max']);
});

test('address field rejects XSS attempts with script tags', function () {
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
    
    $xssAttempts = [
        '<script>alert("XSS")</script>',
        'javascript:alert("XSS")',
        '<img src=x onerror=alert("XSS")>',
        '<div onclick=alert("XSS")>Test</div>',
    ];
    
    foreach ($xssAttempts as $xssAttempt) {
        $component
            ->callTableAction('create', data: [
                'address' => $xssAttempt,
                'type' => PropertyType::APARTMENT->value,
                'area_sqm' => 50.0,
            ])
            ->assertHasTableActionErrors(['address']);
    }
});

test('address field rejects invalid characters', function () {
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
    
    $invalidAddresses = [
        'Test @ Address',
        'Test $ Address',
        'Test % Address',
        'Test & Address',
        'Test * Address',
    ];
    
    foreach ($invalidAddresses as $invalidAddress) {
        $component
            ->callTableAction('create', data: [
                'address' => $invalidAddress,
                'type' => PropertyType::APARTMENT->value,
                'area_sqm' => 50.0,
            ])
            ->assertHasTableActionErrors(['address']);
    }
});

test('address field accepts valid addresses with common characters', function () {
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
    
    $validAddresses = [
        '123 Main Street',
        'Apt. 4B, Building #5',
        '10-20 Oak Avenue',
        'Unit 3/5, Complex A',
        'Building (North), Floor 2',
    ];
    
    foreach ($validAddresses as $validAddress) {
        $component
            ->callTableAction('create', data: [
                'address' => $validAddress,
                'type' => PropertyType::APARTMENT->value,
                'area_sqm' => 50.0,
            ])
            ->assertHasNoTableActionErrors();
            
        // Verify property was created
        expect(Property::where('address', strip_tags(trim($validAddress)))->exists())->toBeTrue();
    }
});

// ============================================================================
// TYPE FIELD VALIDATION TESTS
// ============================================================================

test('type field is required when creating property', function () {
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
    
    $component
        ->callTableAction('create', data: [
            'address' => fake()->address(),
            'type' => null, // Missing type
            'area_sqm' => 50.0,
        ])
        ->assertHasTableActionErrors(['type' => 'required']);
});

test('type field only accepts valid PropertyType enum values', function () {
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
    
    $invalidTypes = [
        'invalid_type',
        'condo',
        'villa',
        'studio',
        'penthouse',
    ];
    
    foreach ($invalidTypes as $invalidType) {
        $component
            ->callTableAction('create', data: [
                'address' => fake()->address(),
                'type' => $invalidType,
                'area_sqm' => 50.0,
            ])
            ->assertHasTableActionErrors(['type']);
    }
});

test('type field accepts valid PropertyType enum values', function () {
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
    
    $validTypes = [
        PropertyType::APARTMENT->value,
        PropertyType::HOUSE->value,
    ];
    
    foreach ($validTypes as $validType) {
        $address = fake()->unique()->address();
        $component
            ->callTableAction('create', data: [
                'address' => $address,
                'type' => $validType,
                'area_sqm' => 50.0,
            ])
            ->assertHasNoTableActionErrors();
            
        // Verify property was created with correct type
        $property = Property::where('address', strip_tags(trim($address)))->first();
        expect($property)->not->toBeNull();
        expect($property->type->value)->toBe($validType);
    }
});

// ============================================================================
// AREA FIELD VALIDATION TESTS
// ============================================================================

test('area_sqm field is required when creating property', function () {
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
    
    $component
        ->callTableAction('create', data: [
            'address' => fake()->address(),
            'type' => PropertyType::APARTMENT->value,
            'area_sqm' => null, // Missing area
        ])
        ->assertHasTableActionErrors(['area_sqm' => 'required']);
});

test('area_sqm field must be numeric', function () {
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
    
    $nonNumericValues = [
        'not-a-number',
        'fifty',
        'abc123',
        '50m²',
    ];
    
    foreach ($nonNumericValues as $nonNumericValue) {
        $component
            ->callTableAction('create', data: [
                'address' => fake()->address(),
                'type' => PropertyType::APARTMENT->value,
                'area_sqm' => $nonNumericValue,
            ])
            ->assertHasTableActionErrors(['area_sqm']);
    }
});

test('area_sqm field cannot be negative', function () {
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
    
    $component
        ->callTableAction('create', data: [
            'address' => fake()->address(),
            'type' => PropertyType::APARTMENT->value,
            'area_sqm' => -50.0,
        ])
        ->assertHasTableActionErrors(['area_sqm' => 'min']);
});

test('area_sqm field cannot exceed 10000', function () {
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
    
    $component
        ->callTableAction('create', data: [
            'address' => fake()->address(),
            'type' => PropertyType::APARTMENT->value,
            'area_sqm' => 10001.0,
        ])
        ->assertHasTableActionErrors(['area_sqm' => 'max']);
});

test('area_sqm field rejects more than 2 decimal places', function () {
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
    
    $invalidPrecisionValues = [
        50.123,  // 3 decimal places
        50.1234, // 4 decimal places
        50.12345, // 5 decimal places
    ];
    
    foreach ($invalidPrecisionValues as $invalidValue) {
        $component
            ->callTableAction('create', data: [
                'address' => fake()->address(),
                'type' => PropertyType::APARTMENT->value,
                'area_sqm' => $invalidValue,
            ])
            ->assertHasTableActionErrors(['area_sqm']);
    }
});

test('area_sqm field rejects scientific notation', function () {
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
    
    $scientificNotationValues = [
        '5e2',   // 500
        '5E2',   // 500
        '5.5e1', // 55
        '1.23e-1', // 0.123
    ];
    
    foreach ($scientificNotationValues as $scientificValue) {
        $component
            ->callTableAction('create', data: [
                'address' => fake()->address(),
                'type' => PropertyType::APARTMENT->value,
                'area_sqm' => $scientificValue,
            ])
            ->assertHasTableActionErrors(['area_sqm']);
    }
});

test('area_sqm field accepts valid decimal values with up to 2 decimal places', function () {
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
    
    $validAreaValues = [
        50,      // Integer
        50.0,    // 1 decimal place
        50.5,    // 1 decimal place
        50.12,   // 2 decimal places
        50.99,   // 2 decimal places
        0.01,    // Minimum with decimals
        9999.99, // Maximum with decimals
    ];
    
    foreach ($validAreaValues as $validArea) {
        $address = fake()->unique()->address();
        $component
            ->callTableAction('create', data: [
                'address' => $address,
                'type' => PropertyType::APARTMENT->value,
                'area_sqm' => $validArea,
            ])
            ->assertHasNoTableActionErrors();
            
        // Verify property was created with correct area
        $property = Property::where('address', strip_tags(trim($address)))->first();
        expect($property)->not->toBeNull();
        expect((float) $property->area_sqm)->toBe((float) $validArea);
    }
});

// ============================================================================
// TENANT FIELD REMOVAL TEST
// ============================================================================

test('tenant field is not present in form schema', function () {
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
    
    // Get the form schema
    $relationManager = $component->instance();
    $form = $relationManager->form($relationManager->makeForm());
    $schema = $form->getComponents();
    
    // Flatten all components to check for tenant field
    $allComponents = [];
    foreach ($schema as $component) {
        if (method_exists($component, 'getChildComponents')) {
            $allComponents = array_merge($allComponents, $component->getChildComponents());
        } else {
            $allComponents[] = $component;
        }
    }
    
    // Check that no component has name 'tenants' or 'tenant_id'
    foreach ($allComponents as $component) {
        if (method_exists($component, 'getName')) {
            $name = $component->getName();
            expect($name)->not->toBe('tenants');
            expect($name)->not->toBe('tenant_id');
        }
    }
});
