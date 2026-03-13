<?php

use App\Enums\MeterType;
use App\Enums\PropertyType;
use App\Enums\UserRole;
use App\Filament\Resources\MeterReadingResource;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// Feature: filament-admin-panel, Property 1: Tenant scope isolation for meter readings
// Validates: Requirements 2.1, 2.7
test('MeterReadingResource automatically filters meter readings by authenticated user tenant_id', function () {
    // Generate random tenant IDs
    $tenantId1 = fake()->numberBetween(1, 1000);
    $tenantId2 = fake()->numberBetween(1001, 2000);
    
    // Create random number of meter readings for each tenant
    $readingsCount1 = fake()->numberBetween(2, 8);
    $readingsCount2 = fake()->numberBetween(2, 8);
    
    // Create properties and meters for tenant 1
    $property1 = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId1,
        'address' => fake()->unique()->address(),
        'type' => fake()->randomElement([PropertyType::APARTMENT, PropertyType::HOUSE]),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    $meter1 = Meter::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId1,
        'property_id' => $property1->id,
        'serial_number' => fake()->unique()->numerify('METER-####'),
        'type' => fake()->randomElement([MeterType::ELECTRICITY, MeterType::WATER_COLD, MeterType::WATER_HOT, MeterType::HEATING]),
        'installation_date' => fake()->date(),
        'supports_zones' => false,
    ]);
    
    // Create meter readings for tenant 1
    $readings1 = [];
    $lastValue = fake()->randomFloat(2, 100, 500);
    for ($i = 0; $i < $readingsCount1; $i++) {
        $lastValue += fake()->randomFloat(2, 10, 50);
        $readings1[] = MeterReading::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId1,
            'meter_id' => $meter1->id,
            'reading_date' => now()->subDays($readingsCount1 - $i),
            'value' => $lastValue,
            'entered_by' => null,
        ]);
    }
    
    // Create properties and meters for tenant 2
    $property2 = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId2,
        'address' => fake()->unique()->address(),
        'type' => fake()->randomElement([PropertyType::APARTMENT, PropertyType::HOUSE]),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    $meter2 = Meter::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId2,
        'property_id' => $property2->id,
        'serial_number' => fake()->unique()->numerify('METER-####'),
        'type' => fake()->randomElement([MeterType::ELECTRICITY, MeterType::WATER_COLD, MeterType::WATER_HOT, MeterType::HEATING]),
        'installation_date' => fake()->date(),
        'supports_zones' => false,
    ]);
    
    // Create meter readings for tenant 2
    $readings2 = [];
    $lastValue = fake()->randomFloat(2, 100, 500);
    for ($i = 0; $i < $readingsCount2; $i++) {
        $lastValue += fake()->randomFloat(2, 10, 50);
        $readings2[] = MeterReading::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId2,
            'meter_id' => $meter2->id,
            'reading_date' => now()->subDays($readingsCount2 - $i),
            'value' => $lastValue,
            'entered_by' => null,
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
    
    // Property: When accessing MeterReadingResource list page, only tenant 1's meter readings should be visible
    $component = Livewire::test(MeterReadingResource\Pages\ListMeterReadings::class);
    
    // Verify the component loaded successfully
    $component->assertSuccessful();
    
    // Get the table records from the component
    $tableRecords = $component->instance()->getTableRecords();
    
    // Property: All returned meter readings should belong to tenant 1
    expect($tableRecords)->toHaveCount($readingsCount1);
    
    $tableRecords->each(function ($reading) use ($tenantId1) {
        expect($reading->tenant_id)->toBe($tenantId1);
    });
    
    // Property: Tenant 2's meter readings should not be accessible
    foreach ($readings2 as $reading2) {
        expect(MeterReading::find($reading2->id))->toBeNull();
    }
    
    // Verify tenant 1's meter readings are all present in the table
    $readingIds1 = collect($readings1)->pluck('id')->toArray();
    $tableRecordIds = $tableRecords->pluck('id')->toArray();
    
    expect($tableRecordIds)->toEqualCanonicalizing($readingIds1);
    
    // Now switch to manager from tenant 2
    $manager2 = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId2,
    ]);
    
    $this->actingAs($manager2);
    session(['tenant_id' => $tenantId2]);
    
    // Property: When accessing MeterReadingResource list page, only tenant 2's meter readings should be visible
    $component2 = Livewire::test(MeterReadingResource\Pages\ListMeterReadings::class);
    
    $component2->assertSuccessful();
    
    $tableRecords2 = $component2->instance()->getTableRecords();
    
    // Property: All returned meter readings should belong to tenant 2
    expect($tableRecords2)->toHaveCount($readingsCount2);
    
    $tableRecords2->each(function ($reading) use ($tenantId2) {
        expect($reading->tenant_id)->toBe($tenantId2);
    });
    
    // Property: Tenant 1's meter readings should not be accessible
    foreach ($readings1 as $reading1) {
        expect(MeterReading::find($reading1->id))->toBeNull();
    }
    
    // Verify tenant 2's meter readings are all present in the table
    $readingIds2 = collect($readings2)->pluck('id')->toArray();
    $tableRecordIds2 = $tableRecords2->pluck('id')->toArray();
    
    expect($tableRecordIds2)->toEqualCanonicalizing($readingIds2);
})->repeat(100);

// Feature: filament-admin-panel, Property 1: Tenant scope isolation for meter readings
// Validates: Requirements 2.1, 2.7
test('MeterReadingResource edit page only allows editing meter readings within tenant scope', function () {
    // Generate random tenant IDs
    $tenantId1 = fake()->numberBetween(1, 1000);
    $tenantId2 = fake()->numberBetween(1001, 2000);
    
    // Create property and meter for tenant 1
    $property1 = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId1,
        'address' => fake()->address(),
        'type' => fake()->randomElement([PropertyType::APARTMENT, PropertyType::HOUSE]),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    $meter1 = Meter::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId1,
        'property_id' => $property1->id,
        'serial_number' => fake()->numerify('METER-####'),
        'type' => fake()->randomElement([MeterType::ELECTRICITY, MeterType::WATER_COLD, MeterType::WATER_HOT, MeterType::HEATING]),
        'installation_date' => fake()->date(),
        'supports_zones' => false,
    ]);
    
    // Create a meter reading for tenant 1
    $reading1 = MeterReading::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId1,
        'meter_id' => $meter1->id,
        'reading_date' => now()->subDays(1),
        'value' => fake()->randomFloat(2, 100, 500),
        'entered_by' => null,
    ]);
    
    // Create property and meter for tenant 2
    $property2 = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId2,
        'address' => fake()->address(),
        'type' => fake()->randomElement([PropertyType::APARTMENT, PropertyType::HOUSE]),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    $meter2 = Meter::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId2,
        'property_id' => $property2->id,
        'serial_number' => fake()->numerify('METER-####'),
        'type' => fake()->randomElement([MeterType::ELECTRICITY, MeterType::WATER_COLD, MeterType::WATER_HOT, MeterType::HEATING]),
        'installation_date' => fake()->date(),
        'supports_zones' => false,
    ]);
    
    // Create a meter reading for tenant 2
    $reading2 = MeterReading::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId2,
        'meter_id' => $meter2->id,
        'reading_date' => now()->subDays(1),
        'value' => fake()->randomFloat(2, 100, 500),
        'entered_by' => null,
    ]);
    
    // Create a manager for tenant 1
    $manager1 = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId1,
    ]);
    
    // Act as manager from tenant 1
    $this->actingAs($manager1);
    session(['tenant_id' => $tenantId1]);
    
    // Property: Manager should be able to access edit page for their tenant's meter reading
    $component = Livewire::test(MeterReadingResource\Pages\EditMeterReading::class, [
        'record' => $reading1->id,
    ]);
    
    $component->assertSuccessful();
    
    // Verify the correct meter reading is loaded
    expect($component->instance()->record->id)->toBe($reading1->id);
    expect($component->instance()->record->tenant_id)->toBe($tenantId1);
    
    // Property: Manager should NOT be able to access edit page for another tenant's meter reading
    // This should fail because the meter reading won't be found due to tenant scope
    try {
        $component2 = Livewire::test(MeterReadingResource\Pages\EditMeterReading::class, [
            'record' => $reading2->id,
        ]);
        
        // If we get here, the test should fail because access should be denied
        expect(false)->toBeTrue('Manager should not be able to access another tenant\'s meter reading');
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        // This is expected - the meter reading should not be found due to tenant scope
        expect(true)->toBeTrue();
    }
})->repeat(100);

// Feature: filament-admin-panel, Property 1: Tenant scope isolation for meter readings
// Validates: Requirements 2.1, 2.7
test('MeterReadingResource create page automatically assigns tenant_id from authenticated user', function () {
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
    
    $meter = Meter::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'property_id' => $property->id,
        'serial_number' => fake()->numerify('METER-####'),
        'type' => fake()->randomElement([MeterType::ELECTRICITY, MeterType::WATER_COLD, MeterType::WATER_HOT, MeterType::HEATING]),
        'installation_date' => fake()->date(),
        'supports_zones' => false,
    ]);
    
    // Create a previous reading to satisfy monotonicity validation
    $previousValue = fake()->randomFloat(2, 100, 500);
    MeterReading::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'meter_id' => $meter->id,
        'reading_date' => now()->subDays(60),
        'value' => $previousValue,
        'entered_by' => $manager->id,
    ]);
    
    // Act as the manager
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    // Generate random meter reading data (higher than previous)
    $readingDate = now()->subDays(fake()->numberBetween(1, 30));
    $value = $previousValue + fake()->randomFloat(2, 10, 100);
    
    // Property: When creating a meter reading through Filament, tenant_id should be automatically assigned
    $component = Livewire::test(MeterReadingResource\Pages\CreateMeterReading::class);
    
    $component->assertSuccessful();
    
    // Fill the form and submit
    $component
        ->fillForm([
            'property_id' => $property->id,
            'meter_id' => $meter->id,
            'reading_date' => $readingDate->format('Y-m-d'),
            'value' => $value,
        ])
        ->call('create');
    
    // Verify the meter reading was created with the correct tenant_id
    $createdReading = MeterReading::withoutGlobalScopes()
        ->where('meter_id', $meter->id)
        ->where('value', $value)
        ->first();
    
    expect($createdReading)->not->toBeNull();
    expect($createdReading->tenant_id)->toBe($tenantId);
    expect($createdReading->meter_id)->toBe($meter->id);
    expect($createdReading->value)->toBe($value);
    expect($createdReading->reading_date->format('Y-m-d'))->toBe($readingDate->format('Y-m-d'));
})->repeat(100);
