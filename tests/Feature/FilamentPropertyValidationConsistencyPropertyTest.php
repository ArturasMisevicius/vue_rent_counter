<?php

use App\Enums\PropertyType;
use App\Enums\UserRole;
use App\Filament\Resources\PropertyResource;
use App\Http\Requests\StorePropertyRequest;
use App\Http\Requests\UpdatePropertyRequest;
use App\Models\Building;
use App\Models\Property;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

// Feature: filament-admin-panel, Property 5: Property validation consistency
// Validates: Requirements 3.4
test('Filament PropertyResource applies same validation rules as StorePropertyRequest for create operations', function () {
    // Generate random tenant ID
    $tenantId = fake()->numberBetween(1, 1000);
    
    // Create a manager for the tenant
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    // Create a building for the tenant (optional relationship)
    $building = Building::factory()->forTenantId($tenantId)->create();
    
    // Act as the manager
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    // Generate random test data
    $testData = [
        'address' => fake()->address(),
        'type' => fake()->randomElement([PropertyType::APARTMENT->value, PropertyType::HOUSE->value]),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
        'building_id' => fake()->boolean() ? $building->id : null,
    ];
    
    // Property: Validation rules from StorePropertyRequest should match Filament validation
    
    // Test with StorePropertyRequest
    $request = new StorePropertyRequest();
    $request->setContainer(app());
    $request->setRedirector(app('redirect'));
    $request->setUserResolver(fn() => $manager);
    $request->replace($testData);
    
    $validator = Validator::make($testData, $request->rules(), $request->messages());
    
    $formRequestPasses = !$validator->fails();
    $formRequestErrors = $validator->errors()->toArray();
    
    // Test with Filament form
    $component = Livewire::test(PropertyResource\Pages\CreateProperty::class);
    
    $component->fillForm([
        'address' => $testData['address'],
        'type' => $testData['type'],
        'area_sqm' => $testData['area_sqm'],
        'building_id' => $testData['building_id'],
    ]);
    
    // Try to create - this will trigger validation
    $component->call('create');

    $filamentErrors = $component->instance()->getErrorBag()->toArray();
    $filamentPasses = empty($filamentErrors);
    
    // Property: Both should have the same validation outcome
    expect($filamentPasses)->toBe($formRequestPasses, 
        "Validation outcome mismatch. FormRequest: " . ($formRequestPasses ? 'pass' : 'fail') . 
        ", Filament: " . ($filamentPasses ? 'pass' : 'fail') .
        ". FormRequest errors: " . json_encode($formRequestErrors) .
        ". Filament errors: " . json_encode($filamentErrors)
    );
    
    // If both failed, verify they failed for similar reasons
    if (!$formRequestPasses && !$filamentPasses) {
        $formRequestErrorFields = array_keys($formRequestErrors);
        $filamentErrorFields = array_keys($filamentErrors);
        
        // Normalize field names (Filament prefixes with 'data.')
        $normalizedFilamentFields = array_map(fn (string $field): string => str_replace('data.', '', $field), $filamentErrorFields);

        // Both should have errors on the same fields
        expect($normalizedFilamentFields)->toEqualCanonicalizing($formRequestErrorFields,
            "Error fields mismatch. FormRequest: " . json_encode($formRequestErrorFields) .
            ", Filament: " . json_encode($normalizedFilamentFields)
        );
    }
})->repeat(100);

// Feature: filament-admin-panel, Property 5: Property validation consistency
// Validates: Requirements 3.4
test('Filament PropertyResource rejects invalid data consistently with StorePropertyRequest', function () {
    // Generate random tenant ID
    $tenantId = fake()->numberBetween(1, 1000);
    
    // Create a manager for the tenant
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    // Create a building for the tenant
    $building = Building::factory()->forTenantId($tenantId)->create();
    $otherTenantBuilding = Building::factory()->forTenantId($tenantId + 1)->create();
    
    // Act as the manager
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    // Generate INVALID test data (randomly choose one type of invalid data)
    $invalidationType = fake()->randomElement([
        'missing_address',
        'empty_address',
        'address_too_long',
        'missing_type',
        'invalid_type',
        'missing_area',
        'negative_area',
        'area_too_large',
        'non_numeric_area',
        'invalid_building_id',
    ]);
    
    $testData = [
        'address' => fake()->address(),
        'type' => PropertyType::APARTMENT->value,
        'area_sqm' => fake()->randomFloat(2, 20, 200),
        'building_id' => $building->id,
    ];
    
    // Apply the invalidation
    switch ($invalidationType) {
        case 'missing_address':
            unset($testData['address']);
            break;
        case 'empty_address':
            $testData['address'] = '';
            break;
        case 'address_too_long':
            $testData['address'] = str_repeat('a', 501); // Max is 500
            break;
        case 'missing_type':
            unset($testData['type']);
            break;
        case 'invalid_type':
            $testData['type'] = 'invalid_type';
            break;
        case 'missing_area':
            unset($testData['area_sqm']);
            break;
        case 'negative_area':
            $testData['area_sqm'] = -1 * fake()->randomFloat(2, 1, 100);
            break;
        case 'area_too_large':
            $testData['area_sqm'] = 1000000; // Max is 999999.99
            break;
        case 'non_numeric_area':
            $testData['area_sqm'] = 'not-a-number';
            break;
        case 'invalid_building_id':
            $testData['building_id'] = $otherTenantBuilding->id;
            break;
    }
    
    // Property: Both StorePropertyRequest and Filament should reject invalid data
    
    // Test with StorePropertyRequest
    $request = new StorePropertyRequest();
    $request->setContainer(app());
    $request->setRedirector(app('redirect'));
    $request->setUserResolver(fn() => $manager);
    $request->replace($testData);
    
    $validator = Validator::make($testData, $request->rules(), $request->messages());
    
    $formRequestPasses = !$validator->fails();
    
    // Test with Filament form
    $component = Livewire::test(PropertyResource\Pages\CreateProperty::class);
    
    $formData = [];
    
    if (isset($testData['address'])) {
        $formData['address'] = $testData['address'];
    }
    if (isset($testData['type'])) {
        $formData['type'] = $testData['type'];
    }
    if (isset($testData['area_sqm'])) {
        $formData['area_sqm'] = $testData['area_sqm'];
    }
    if (isset($testData['building_id'])) {
        $formData['building_id'] = $testData['building_id'];
    }
    
    $component->fillForm($formData);
    
    $component->call('create');

    $filamentErrors = $component->instance()->getErrorBag()->toArray();
    $filamentPasses = empty($filamentErrors);
    
    // Property: Both should reject the invalid data
    expect($formRequestPasses)->toBeFalse("StorePropertyRequest should reject invalid data (type: {$invalidationType})");
    expect($filamentPasses)->toBeFalse("Filament should reject invalid data (type: {$invalidationType})");
    
    // Property: Both should have the same validation outcome
    expect($filamentPasses)->toBe($formRequestPasses,
        "Validation outcome mismatch for {$invalidationType}. FormRequest: " . ($formRequestPasses ? 'pass' : 'fail') . 
        ", Filament: " . ($filamentPasses ? 'pass' : 'fail')
    );
})->repeat(100);

// Feature: filament-admin-panel, Property 5: Property validation consistency
// Validates: Requirements 3.4
test('Filament PropertyResource applies same validation rules as UpdatePropertyRequest for edit operations', function () {
    // Generate random tenant ID
    $tenantId = fake()->numberBetween(1, 1000);
    
    // Create a manager for the tenant
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    // Create a building for the tenant
    $building = Building::factory()->forTenantId($tenantId)->create();
    
    // Create an existing property
    $existingProperty = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'type' => PropertyType::APARTMENT,
        'area_sqm' => fake()->randomFloat(2, 20, 200),
        'building_id' => $building->id,
    ]);
    
    // Act as the manager
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    // Generate random updated data
    $testData = [
        'address' => fake()->address(),
        'type' => fake()->randomElement([PropertyType::APARTMENT->value, PropertyType::HOUSE->value]),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
        'building_id' => fake()->boolean() ? $building->id : null,
    ];
    
    // Property: Validation rules from UpdatePropertyRequest should match Filament validation
    
    // Test with UpdatePropertyRequest
    $request = new UpdatePropertyRequest();
    $request->setContainer(app());
    $request->setRedirector(app('redirect'));
    $request->setUserResolver(fn () => $manager);
    $request->replace($testData);
    
    $validator = Validator::make($testData, $request->rules(), $request->messages());
    
    $formRequestPasses = !$validator->fails();
    $formRequestErrors = $validator->errors()->toArray();
    
    // Test with Filament form
    $component = Livewire::test(PropertyResource\Pages\EditProperty::class, [
        'record' => $existingProperty->id,
    ]);
    
    $component->fillForm([
        'address' => $testData['address'],
        'type' => $testData['type'],
        'area_sqm' => $testData['area_sqm'],
        'building_id' => $testData['building_id'],
    ]);
    
    // Try to save - this will trigger validation
    $component->call('save');

    $filamentErrors = $component->instance()->getErrorBag()->toArray();
    $filamentPasses = empty($filamentErrors);
    
    // Property: Both should have the same validation outcome
    expect($filamentPasses)->toBe($formRequestPasses,
        "Validation outcome mismatch. FormRequest: " . ($formRequestPasses ? 'pass' : 'fail') . 
        ", Filament: " . ($filamentPasses ? 'pass' : 'fail') .
        ". FormRequest errors: " . json_encode($formRequestErrors) .
        ". Filament errors: " . json_encode($filamentErrors)
    );
    
    // If both failed, verify they failed for similar reasons
    if (!$formRequestPasses && !$filamentPasses) {
        $formRequestErrorFields = array_keys($formRequestErrors);
        $filamentErrorFields = array_keys($filamentErrors);
        
        // Normalize field names (Filament prefixes with 'data.')
        $normalizedFilamentFields = array_map(fn (string $field): string => str_replace('data.', '', $field), $filamentErrorFields);

        // Both should have errors on the same fields
        expect($normalizedFilamentFields)->toEqualCanonicalizing($formRequestErrorFields,
            "Error fields mismatch. FormRequest: " . json_encode($formRequestErrorFields) .
            ", Filament: " . json_encode($normalizedFilamentFields)
        );
    }
})->repeat(100);

// Feature: filament-admin-panel, Property 5: Property validation consistency
// Validates: Requirements 3.4
test('Filament PropertyResource rejects invalid updates consistently with UpdatePropertyRequest', function () {
    // Generate random tenant ID
    $tenantId = fake()->numberBetween(1, 1000);
    
    // Create a manager for the tenant
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    // Create a building for the tenant
    $building = Building::factory()->forTenantId($tenantId)->create();
    $otherTenantBuilding = Building::factory()->forTenantId($tenantId + 1)->create();
    
    // Create an existing property
    $existingProperty = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'type' => PropertyType::APARTMENT,
        'area_sqm' => fake()->randomFloat(2, 20, 200),
        'building_id' => $building->id,
    ]);
    
    // Act as the manager
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    // Generate INVALID test data (randomly choose one type of invalid data)
    $invalidationType = fake()->randomElement([
        'empty_address',
        'address_too_long',
        'invalid_type',
        'negative_area',
        'area_too_large',
        'non_numeric_area',
        'invalid_building_id',
    ]);
    
    $testData = [
        'address' => fake()->address(),
        'type' => PropertyType::HOUSE->value,
        'area_sqm' => fake()->randomFloat(2, 20, 200),
        'building_id' => $building->id,
    ];
    
    // Apply the invalidation
    switch ($invalidationType) {
        case 'empty_address':
            $testData['address'] = '';
            break;
        case 'address_too_long':
            $testData['address'] = str_repeat('a', 501); // Max is 500
            break;
        case 'invalid_type':
            $testData['type'] = 'invalid_type';
            break;
        case 'negative_area':
            $testData['area_sqm'] = -1 * fake()->randomFloat(2, 1, 100);
            break;
        case 'area_too_large':
            $testData['area_sqm'] = 1000000; // Max is 999999.99
            break;
        case 'non_numeric_area':
            $testData['area_sqm'] = 'not-a-number';
            break;
        case 'invalid_building_id':
            $testData['building_id'] = $otherTenantBuilding->id;
            break;
    }
    
    // Property: Both UpdatePropertyRequest and Filament should reject invalid data
    
    // Test with UpdatePropertyRequest
    $request = new UpdatePropertyRequest();
    $request->setContainer(app());
    $request->setRedirector(app('redirect'));
    $request->setUserResolver(fn () => $manager);
    $request->replace($testData);
    
    $validator = Validator::make($testData, $request->rules(), $request->messages());
    
    $formRequestPasses = !$validator->fails();
    
    // Test with Filament form
    $component = Livewire::test(PropertyResource\Pages\EditProperty::class, [
        'record' => $existingProperty->id,
    ]);
    
    $formData = [];
    
    if (isset($testData['address'])) {
        $formData['address'] = $testData['address'];
    }
    if (isset($testData['type'])) {
        $formData['type'] = $testData['type'];
    }
    if (isset($testData['area_sqm'])) {
        $formData['area_sqm'] = $testData['area_sqm'];
    }
    if (isset($testData['building_id'])) {
        $formData['building_id'] = $testData['building_id'];
    }
    
    $component->fillForm($formData);
    
    $component->call('save');

    $filamentErrors = $component->instance()->getErrorBag()->toArray();
    $filamentPasses = empty($filamentErrors);
    
    // Property: Both should reject the invalid data
    expect($formRequestPasses)->toBeFalse("UpdatePropertyRequest should reject invalid data (type: {$invalidationType})");
    expect($filamentPasses)->toBeFalse("Filament should reject invalid data (type: {$invalidationType})");
    
    // Property: Both should have the same validation outcome
    expect($filamentPasses)->toBe($formRequestPasses,
        "Validation outcome mismatch for {$invalidationType}. FormRequest: " . ($formRequestPasses ? 'pass' : 'fail') . 
        ", Filament: " . ($filamentPasses ? 'pass' : 'fail')
    );
})->repeat(100);
