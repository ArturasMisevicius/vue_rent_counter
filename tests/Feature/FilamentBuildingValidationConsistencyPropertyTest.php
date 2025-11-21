<?php

use App\Enums\UserRole;
use App\Filament\Resources\BuildingResource;
use App\Http\Requests\StoreBuildingRequest;
use App\Http\Requests\UpdateBuildingRequest;
use App\Models\Building;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// Feature: filament-admin-panel, Property 17: Building validation consistency
// Validates: Requirements 7.4
test('Filament BuildingResource applies same validation rules as StoreBuildingRequest for create operations', function () {
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
    
    // Generate random test data
    $testData = [
        'address' => fake()->address(),
        'total_apartments' => fake()->numberBetween(1, 1000),
    ];
    
    // Property: Validation rules from StoreBuildingRequest should match Filament validation
    
    // Test with StoreBuildingRequest
    $request = new StoreBuildingRequest();
    $request->setContainer(app());
    $request->setRedirector(app('redirect'));
    $request->setUserResolver(fn() => $manager);
    $request->replace($testData);
    
    // Manually trigger prepareForValidation to add tenant_id
    $testDataWithTenant = array_merge($testData, ['tenant_id' => $tenantId]);
    
    $validator = Validator::make($testDataWithTenant, $request->rules(), $request->messages());
    
    $formRequestPasses = !$validator->fails();
    $formRequestErrors = $validator->errors()->toArray();
    
    // Test with Filament form
    $component = Livewire::test(BuildingResource\Pages\CreateBuilding::class);
    
    $component->fillForm([
        'address' => $testData['address'],
        'total_apartments' => $testData['total_apartments'],
    ]);
    
    // Try to create - this will trigger validation
    try {
        $component->call('create');
        $filamentPasses = true;
        $filamentErrors = [];
    } catch (\Illuminate\Validation\ValidationException $e) {
        $filamentPasses = false;
        $filamentErrors = $e->errors();
    }
    
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
        
        // Both should have errors on the same fields
        expect($filamentErrorFields)->toEqualCanonicalizing($formRequestErrorFields,
            "Error fields mismatch. FormRequest: " . json_encode($formRequestErrorFields) .
            ", Filament: " . json_encode($filamentErrorFields)
        );
    }
})->repeat(100);

// Feature: filament-admin-panel, Property 17: Building validation consistency
// Validates: Requirements 7.4
test('Filament BuildingResource rejects invalid data consistently with StoreBuildingRequest', function () {
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
    
    // Generate INVALID test data (randomly choose one type of invalid data)
    $invalidationType = fake()->randomElement([
        'missing_address',
        'empty_address',
        'address_too_long',
        'missing_total_apartments',
        'non_integer_apartments',
        'zero_apartments',
        'negative_apartments',
        'apartments_too_large',
    ]);
    
    $testData = [
        'address' => fake()->address(),
        'total_apartments' => fake()->numberBetween(1, 1000),
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
            $testData['address'] = str_repeat('a', 256); // Max is 255
            break;
        case 'missing_total_apartments':
            unset($testData['total_apartments']);
            break;
        case 'non_integer_apartments':
            $testData['total_apartments'] = 'not-a-number';
            break;
        case 'zero_apartments':
            $testData['total_apartments'] = 0;
            break;
        case 'negative_apartments':
            $testData['total_apartments'] = -1 * fake()->numberBetween(1, 100);
            break;
        case 'apartments_too_large':
            $testData['total_apartments'] = 1001; // Max is 1000
            break;
    }
    
    // Property: Both StoreBuildingRequest and Filament should reject invalid data
    
    // Test with StoreBuildingRequest
    $request = new StoreBuildingRequest();
    $request->setContainer(app());
    $request->setRedirector(app('redirect'));
    $request->setUserResolver(fn() => $manager);
    $request->replace($testData);
    
    // Manually add tenant_id
    $testDataWithTenant = array_merge($testData, ['tenant_id' => $tenantId]);
    
    $validator = Validator::make($testDataWithTenant, $request->rules(), $request->messages());
    
    $formRequestPasses = !$validator->fails();
    
    // Test with Filament form
    $component = Livewire::test(BuildingResource\Pages\CreateBuilding::class);
    
    $formData = [];
    
    if (isset($testData['address'])) {
        $formData['address'] = $testData['address'];
    }
    if (isset($testData['total_apartments'])) {
        $formData['total_apartments'] = $testData['total_apartments'];
    }
    
    $component->fillForm($formData);
    
    try {
        $component->call('create');
        $filamentPasses = true;
    } catch (\Illuminate\Validation\ValidationException $e) {
        $filamentPasses = false;
    }
    
    // Property: Both should reject the invalid data
    expect($formRequestPasses)->toBeFalse("StoreBuildingRequest should reject invalid data (type: {$invalidationType})");
    expect($filamentPasses)->toBeFalse("Filament should reject invalid data (type: {$invalidationType})");
    
    // Property: Both should have the same validation outcome
    expect($filamentPasses)->toBe($formRequestPasses,
        "Validation outcome mismatch for {$invalidationType}. FormRequest: " . ($formRequestPasses ? 'pass' : 'fail') . 
        ", Filament: " . ($filamentPasses ? 'pass' : 'fail')
    );
})->repeat(100);

// Feature: filament-admin-panel, Property 17: Building validation consistency
// Validates: Requirements 7.4
test('Filament BuildingResource applies same validation rules as UpdateBuildingRequest for edit operations', function () {
    // Generate random tenant ID
    $tenantId = fake()->numberBetween(1, 1000);
    
    // Create a manager for the tenant
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    // Create an existing building
    $existingBuilding = Building::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'total_apartments' => fake()->numberBetween(1, 1000),
    ]);
    
    // Act as the manager
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    // Generate random updated data
    $testData = [
        'address' => fake()->address(),
        'total_apartments' => fake()->numberBetween(1, 1000),
    ];
    
    // Property: Validation rules from UpdateBuildingRequest should match Filament validation
    
    // Test with UpdateBuildingRequest
    $request = new UpdateBuildingRequest();
    $request->setContainer(app());
    $request->setRedirector(app('redirect'));
    $request->replace($testData);
    
    $validator = Validator::make($testData, $request->rules(), $request->messages());
    
    $formRequestPasses = !$validator->fails();
    $formRequestErrors = $validator->errors()->toArray();
    
    // Test with Filament form
    $component = Livewire::test(BuildingResource\Pages\EditBuilding::class, [
        'record' => $existingBuilding->id,
    ]);
    
    $component->fillForm([
        'address' => $testData['address'],
        'total_apartments' => $testData['total_apartments'],
    ]);
    
    // Try to save - this will trigger validation
    try {
        $component->call('save');
        $filamentPasses = true;
        $filamentErrors = [];
    } catch (\Illuminate\Validation\ValidationException $e) {
        $filamentPasses = false;
        $filamentErrors = $e->errors();
    }
    
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
        
        // Both should have errors on the same fields
        expect($filamentErrorFields)->toEqualCanonicalizing($formRequestErrorFields,
            "Error fields mismatch. FormRequest: " . json_encode($formRequestErrorFields) .
            ", Filament: " . json_encode($filamentErrorFields)
        );
    }
})->repeat(100);

// Feature: filament-admin-panel, Property 17: Building validation consistency
// Validates: Requirements 7.4
test('Filament BuildingResource rejects invalid updates consistently with UpdateBuildingRequest', function () {
    // Generate random tenant ID
    $tenantId = fake()->numberBetween(1, 1000);
    
    // Create a manager for the tenant
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    // Create an existing building
    $existingBuilding = Building::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'total_apartments' => fake()->numberBetween(1, 1000),
    ]);
    
    // Act as the manager
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    // Generate INVALID test data (randomly choose one type of invalid data)
    $invalidationType = fake()->randomElement([
        'missing_address',
        'empty_address',
        'address_too_long',
        'missing_total_apartments',
        'non_integer_apartments',
        'zero_apartments',
        'negative_apartments',
        'apartments_too_large',
    ]);
    
    $testData = [
        'address' => fake()->address(),
        'total_apartments' => fake()->numberBetween(1, 1000),
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
            $testData['address'] = str_repeat('a', 256); // Max is 255
            break;
        case 'missing_total_apartments':
            unset($testData['total_apartments']);
            break;
        case 'non_integer_apartments':
            $testData['total_apartments'] = 'not-a-number';
            break;
        case 'zero_apartments':
            $testData['total_apartments'] = 0;
            break;
        case 'negative_apartments':
            $testData['total_apartments'] = -1 * fake()->numberBetween(1, 100);
            break;
        case 'apartments_too_large':
            $testData['total_apartments'] = 1001; // Max is 1000
            break;
    }
    
    // Property: Both UpdateBuildingRequest and Filament should reject invalid data
    
    // Test with UpdateBuildingRequest
    $request = new UpdateBuildingRequest();
    $request->setContainer(app());
    $request->setRedirector(app('redirect'));
    $request->replace($testData);
    
    $validator = Validator::make($testData, $request->rules(), $request->messages());
    
    $formRequestPasses = !$validator->fails();
    
    // Test with Filament form
    $component = Livewire::test(BuildingResource\Pages\EditBuilding::class, [
        'record' => $existingBuilding->id,
    ]);
    
    $formData = [];
    
    if (isset($testData['address'])) {
        $formData['address'] = $testData['address'];
    }
    if (isset($testData['total_apartments'])) {
        $formData['total_apartments'] = $testData['total_apartments'];
    }
    
    $component->fillForm($formData);
    
    try {
        $component->call('save');
        $filamentPasses = true;
    } catch (\Illuminate\Validation\ValidationException $e) {
        $filamentPasses = false;
    }
    
    // Property: Both should reject the invalid data
    expect($formRequestPasses)->toBeFalse("UpdateBuildingRequest should reject invalid data (type: {$invalidationType})");
    expect($filamentPasses)->toBeFalse("Filament should reject invalid data (type: {$invalidationType})");
    
    // Property: Both should have the same validation outcome
    expect($filamentPasses)->toBe($formRequestPasses,
        "Validation outcome mismatch for {$invalidationType}. FormRequest: " . ($formRequestPasses ? 'pass' : 'fail') . 
        ", Filament: " . ($filamentPasses ? 'pass' : 'fail')
    );
})->repeat(100);
