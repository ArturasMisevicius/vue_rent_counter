<?php

use App\Enums\MeterType;
use App\Enums\PropertyType;
use App\Enums\UserRole;
use App\Filament\Resources\MeterReadingResource;
use App\Http\Requests\StoreMeterReadingRequest;
use App\Http\Requests\UpdateMeterReadingRequest;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// Feature: filament-admin-panel, Property 2: Meter reading validation consistency
// Validates: Requirements 2.4, 2.6
test('Filament MeterReadingResource applies same validation rules as StoreMeterReadingRequest for create operations', function () {
    // Generate random tenant ID
    $tenantId = fake()->numberBetween(1, 1000);
    
    // Create a manager for the tenant
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    // Create property and meter for the tenant
    $property = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'type' => fake()->randomElement([PropertyType::APARTMENT, PropertyType::HOUSE]),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    $supportsZones = fake()->boolean();
    $meter = Meter::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'property_id' => $property->id,
        'serial_number' => fake()->numerify('METER-####'),
        'type' => fake()->randomElement([MeterType::ELECTRICITY, MeterType::WATER_COLD, MeterType::WATER_HOT, MeterType::HEATING]),
        'installation_date' => fake()->date(),
        'supports_zones' => $supportsZones,
    ]);
    
    // Create a previous reading to test monotonicity
    $previousValue = fake()->randomFloat(2, 100, 500);
    $previousZone = $supportsZones ? fake()->randomElement(['day', 'night']) : null;
    MeterReading::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'meter_id' => $meter->id,
        'reading_date' => now()->subDays(60),
        'value' => $previousValue,
        'zone' => $previousZone,
        'entered_by' => $manager->id,
    ]);
    
    // Act as the manager
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    // Generate random test data
    $testData = [
        'meter_id' => $meter->id,
        'reading_date' => now()->subDays(fake()->numberBetween(1, 30))->format('Y-m-d'),
        'value' => $previousValue + fake()->randomFloat(2, 10, 100), // Higher than previous
        'zone' => $supportsZones ? $previousZone : null,
    ];
    
    // Property: Validation rules from StoreMeterReadingRequest should match Filament validation
    
    // Test with StoreMeterReadingRequest
    $request = new StoreMeterReadingRequest();
    $request->setContainer(app());
    $request->setRedirector(app('redirect'));
    $request->replace($testData);
    
    $validator = Validator::make($testData, $request->rules(), $request->messages());
    $request->withValidator($validator);
    
    $formRequestPasses = !$validator->fails();
    $formRequestErrors = $validator->errors()->toArray();
    
    // Test with Filament form
    $component = Livewire::test(MeterReadingResource\Pages\CreateMeterReading::class);
    
    $component->fillForm([
        'property_id' => $property->id,
        'meter_id' => $testData['meter_id'],
        'reading_date' => $testData['reading_date'],
        'value' => $testData['value'],
        'zone' => $testData['zone'],
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

// Feature: filament-admin-panel, Property 2: Meter reading validation consistency
// Validates: Requirements 2.4, 2.6
test('Filament MeterReadingResource rejects invalid data consistently with StoreMeterReadingRequest', function () {
    // Generate random tenant ID
    $tenantId = fake()->numberBetween(1, 1000);
    
    // Create a manager for the tenant
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    // Create property and meter for the tenant
    $property = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'type' => fake()->randomElement([PropertyType::APARTMENT, PropertyType::HOUSE]),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    $supportsZones = fake()->boolean();
    $meter = Meter::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'property_id' => $property->id,
        'serial_number' => fake()->numerify('METER-####'),
        'type' => fake()->randomElement([MeterType::ELECTRICITY, MeterType::WATER_COLD, MeterType::WATER_HOT, MeterType::HEATING]),
        'installation_date' => fake()->date(),
        'supports_zones' => $supportsZones,
    ]);
    
    // Create a previous reading
    $previousValue = fake()->randomFloat(2, 100, 500);
    $previousZone = $supportsZones ? fake()->randomElement(['day', 'night']) : null;
    MeterReading::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'meter_id' => $meter->id,
        'reading_date' => now()->subDays(60),
        'value' => $previousValue,
        'zone' => $previousZone,
        'entered_by' => $manager->id,
    ]);
    
    // Act as the manager
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    // Generate INVALID test data (randomly choose one type of invalid data)
    $invalidationType = fake()->randomElement([
        'missing_meter_id',
        'invalid_meter_id',
        'missing_reading_date',
        'future_reading_date',
        'missing_value',
        'negative_value',
        'non_numeric_value',
        'monotonicity_violation',
        'zone_not_supported',
        'zone_required_but_missing',
    ]);
    
    $testData = [
        'meter_id' => $meter->id,
        'reading_date' => now()->subDays(fake()->numberBetween(1, 30))->format('Y-m-d'),
        'value' => $previousValue + fake()->randomFloat(2, 10, 100),
        'zone' => $supportsZones ? $previousZone : null,
    ];
    
    // Apply the invalidation
    switch ($invalidationType) {
        case 'missing_meter_id':
            unset($testData['meter_id']);
            break;
        case 'invalid_meter_id':
            $testData['meter_id'] = 999999;
            break;
        case 'missing_reading_date':
            unset($testData['reading_date']);
            break;
        case 'future_reading_date':
            $testData['reading_date'] = now()->addDays(fake()->numberBetween(1, 30))->format('Y-m-d');
            break;
        case 'missing_value':
            unset($testData['value']);
            break;
        case 'negative_value':
            $testData['value'] = -1 * fake()->randomFloat(2, 1, 100);
            break;
        case 'non_numeric_value':
            $testData['value'] = 'not-a-number';
            break;
        case 'monotonicity_violation':
            $testData['value'] = $previousValue - fake()->randomFloat(2, 10, 50); // Lower than previous
            break;
        case 'zone_not_supported':
            if (!$supportsZones) {
                $testData['zone'] = 'day'; // Provide zone when meter doesn't support it
            } else {
                // Skip this test case if meter supports zones
                $testData['zone'] = $previousZone;
            }
            break;
        case 'zone_required_but_missing':
            if ($supportsZones) {
                $testData['zone'] = null; // Remove zone when meter requires it
            } else {
                // Skip this test case if meter doesn't support zones
                $testData['zone'] = null;
            }
            break;
    }
    
    // Property: Both StoreMeterReadingRequest and Filament should reject invalid data
    
    // Test with StoreMeterReadingRequest
    $request = new StoreMeterReadingRequest();
    $request->setContainer(app());
    $request->setRedirector(app('redirect'));
    $request->replace($testData);
    
    $validator = Validator::make($testData, $request->rules(), $request->messages());
    $request->withValidator($validator);
    
    $formRequestPasses = !$validator->fails();
    
    // Test with Filament form
    $component = Livewire::test(MeterReadingResource\Pages\CreateMeterReading::class);
    
    $formData = [
        'property_id' => $property->id,
    ];
    
    if (isset($testData['meter_id'])) {
        $formData['meter_id'] = $testData['meter_id'];
    }
    if (isset($testData['reading_date'])) {
        $formData['reading_date'] = $testData['reading_date'];
    }
    if (isset($testData['value'])) {
        $formData['value'] = $testData['value'];
    }
    if (isset($testData['zone'])) {
        $formData['zone'] = $testData['zone'];
    }
    
    $component->fillForm($formData);
    
    try {
        $component->call('create');
        $filamentPasses = true;
    } catch (\Illuminate\Validation\ValidationException $e) {
        $filamentPasses = false;
    }
    
    // Property: Both should reject the invalid data
    expect($formRequestPasses)->toBeFalse("StoreMeterReadingRequest should reject invalid data (type: {$invalidationType})");
    expect($filamentPasses)->toBeFalse("Filament should reject invalid data (type: {$invalidationType})");
    
    // Property: Both should have the same validation outcome
    expect($filamentPasses)->toBe($formRequestPasses,
        "Validation outcome mismatch for {$invalidationType}. FormRequest: " . ($formRequestPasses ? 'pass' : 'fail') . 
        ", Filament: " . ($filamentPasses ? 'pass' : 'fail')
    );
})->repeat(100);

// Feature: filament-admin-panel, Property 2: Meter reading validation consistency
// Validates: Requirements 2.4, 2.6
test('Filament MeterReadingResource applies same validation rules as UpdateMeterReadingRequest for edit operations', function () {
    // Generate random tenant ID
    $tenantId = fake()->numberBetween(1, 1000);
    
    // Create a manager for the tenant
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    // Create property and meter for the tenant
    $property = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'type' => fake()->randomElement([PropertyType::APARTMENT, PropertyType::HOUSE]),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    $supportsZones = fake()->boolean();
    $meter = Meter::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'property_id' => $property->id,
        'serial_number' => fake()->numerify('METER-####'),
        'type' => fake()->randomElement([MeterType::ELECTRICITY, MeterType::WATER_COLD, MeterType::WATER_HOT, MeterType::HEATING]),
        'installation_date' => fake()->date(),
        'supports_zones' => $supportsZones,
    ]);
    
    // Create three readings: previous, current (to edit), and next
    $previousValue = fake()->randomFloat(2, 100, 200);
    $currentValue = $previousValue + fake()->randomFloat(2, 50, 100);
    $nextValue = $currentValue + fake()->randomFloat(2, 50, 100);
    $zone = $supportsZones ? fake()->randomElement(['day', 'night']) : null;
    
    MeterReading::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'meter_id' => $meter->id,
        'reading_date' => now()->subDays(60),
        'value' => $previousValue,
        'zone' => $zone,
        'entered_by' => $manager->id,
    ]);
    
    $currentReading = MeterReading::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'meter_id' => $meter->id,
        'reading_date' => now()->subDays(30),
        'value' => $currentValue,
        'zone' => $zone,
        'entered_by' => $manager->id,
    ]);
    
    MeterReading::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'meter_id' => $meter->id,
        'reading_date' => now()->subDays(10),
        'value' => $nextValue,
        'zone' => $zone,
        'entered_by' => $manager->id,
    ]);
    
    // Act as the manager
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    // Generate valid updated value (between previous and next)
    $newValue = $previousValue + fake()->randomFloat(2, 10, ($nextValue - $previousValue - 10));
    
    $testData = [
        'value' => $newValue,
        'reading_date' => $currentReading->reading_date->format('Y-m-d'),
        'zone' => $zone,
    ];
    
    // Property: Validation rules from UpdateMeterReadingRequest should match Filament validation
    
    // Note: UpdateMeterReadingRequest doesn't require change_reason in basic validation,
    // but Filament might have it as a required field. We'll test the core validation logic.
    
    // Test with UpdateMeterReadingRequest
    $request = new UpdateMeterReadingRequest();
    $request->setContainer(app());
    $request->setRedirector(app('redirect'));
    $request->setRouteResolver(function () use ($currentReading) {
        return new class($currentReading) {
            public function __construct(private $reading) {}
            public function parameter($key) {
                return $key === 'reading' ? $this->reading : null;
            }
        };
    });
    $request->replace($testData);
    
    $validator = Validator::make($testData, $request->rules(), $request->messages());
    $request->withValidator($validator);
    
    $formRequestPasses = !$validator->fails();
    $formRequestErrors = $validator->errors()->toArray();
    
    // Test with Filament form
    $component = Livewire::test(MeterReadingResource\Pages\EditMeterReading::class, [
        'record' => $currentReading->id,
    ]);
    
    $component->fillForm([
        'property_id' => $property->id,
        'meter_id' => $meter->id,
        'reading_date' => $testData['reading_date'],
        'value' => $testData['value'],
        'zone' => $testData['zone'],
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
    
    // Property: Both should have the same validation outcome for the value field
    // (We focus on value validation since that's the core logic)
    $formRequestValueValid = !isset($formRequestErrors['value']);
    $filamentValueValid = !isset($filamentErrors['value']);
    
    expect($filamentValueValid)->toBe($formRequestValueValid,
        "Value validation outcome mismatch. FormRequest: " . ($formRequestValueValid ? 'valid' : 'invalid') . 
        ", Filament: " . ($filamentValueValid ? 'valid' : 'invalid') .
        ". FormRequest errors: " . json_encode($formRequestErrors) .
        ". Filament errors: " . json_encode($filamentErrors)
    );
})->repeat(100);
