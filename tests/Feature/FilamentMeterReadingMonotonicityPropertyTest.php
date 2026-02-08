<?php

use App\Enums\MeterType;
use App\Enums\PropertyType;
use App\Enums\UserRole;
use App\Filament\Resources\MeterReadingResource;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

// Feature: filament-admin-panel, Property 3: Monotonicity enforcement
// Validates: Requirements 2.5
test('Filament MeterReadingResource rejects new readings that are less than the most recent reading', function () {
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
    
    // Create a previous reading with a random value
    $previousValue = fake()->randomFloat(2, 100, 500);
    $zone = $supportsZones ? fake()->randomElement(['day', 'night']) : null;
    
    MeterReading::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'meter_id' => $meter->id,
        'reading_date' => now()->subDays(30),
        'value' => $previousValue,
        'zone' => $zone,
        'entered_by' => $manager->id,
    ]);
    
    // Act as the manager
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    // Generate a new reading value that is LESS than the previous reading (violates monotonicity)
    $newValue = $previousValue - fake()->randomFloat(2, 1, 50);
    
    // Property: The system should reject the submission when new reading < previous reading
    $component = Livewire::test(MeterReadingResource\Pages\CreateMeterReading::class);
    
    $component->fillForm([
        'meter_id' => $meter->id,
        'reading_date' => now()->subDays(fake()->numberBetween(1, 29))->format('Y-m-d'),
        'value' => $newValue,
        'zone' => $zone,
    ]);
    
    $component->call('create');

    $errors = $component->instance()->getErrorBag()->toArray();
    expect($errors)->toHaveKey('data.value');
    expect($errors['data.value'][0] ?? null)->toBe(__('meter_readings.validation.custom.monotonicity_lower', [
        'previous' => $previousValue,
    ]));
    
    // Verify that no new meter reading was created
    $readingCount = MeterReading::withoutGlobalScopes()
        ->where('meter_id', $meter->id)
        ->count();
    
    expect($readingCount)->toBe(1); // Only the original reading should exist
})->repeat(100);

// Feature: filament-admin-panel, Property 3: Monotonicity enforcement
// Validates: Requirements 2.5
test('Filament MeterReadingResource accepts new readings that are greater than or equal to the most recent reading', function () {
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
    
    // Create a previous reading with a random value
    $previousValue = fake()->randomFloat(2, 100, 500);
    $zone = $supportsZones ? fake()->randomElement(['day', 'night']) : null;
    
    MeterReading::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'meter_id' => $meter->id,
        'reading_date' => now()->subDays(30),
        'value' => $previousValue,
        'zone' => $zone,
        'entered_by' => $manager->id,
    ]);
    
    // Act as the manager
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    // Generate a new reading value that is GREATER than the previous reading (satisfies monotonicity)
    $newValue = $previousValue + fake()->randomFloat(2, 0.01, 100);
    
    // Property: The system should accept the submission when new reading >= previous reading
    $component = Livewire::test(MeterReadingResource\Pages\CreateMeterReading::class);
    
    $component->fillForm([
        'meter_id' => $meter->id,
        'reading_date' => now()->subDays(fake()->numberBetween(1, 29))->format('Y-m-d'),
        'value' => $newValue,
        'zone' => $zone,
    ]);
    
    // Try to create - this should succeed
    $component->call('create');

    $errors = $component->instance()->getErrorBag()->toArray();
    expect($errors)->toBe([]);
    
    // Verify that a new meter reading was created
    $createdReading = MeterReading::withoutGlobalScopes()
        ->where('meter_id', $meter->id)
        ->orderByDesc('reading_date')
        ->orderByDesc('id')
        ->first();
    
    expect($createdReading)->not->toBeNull();
    expect((string) $createdReading->value)->toBe(number_format($newValue, 2, '.', ''));
    expect($createdReading->tenant_id)->toBe($tenantId);
    
    // Verify that the new reading is greater than or equal to the previous reading
    expect((float) $createdReading->value)->toBeGreaterThanOrEqual($previousValue);
})->repeat(100);

// Feature: filament-admin-panel, Property 3: Monotonicity enforcement
// Validates: Requirements 2.5
test('Filament MeterReadingResource enforces monotonicity when editing existing readings', function () {
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
    
    $zone = $supportsZones ? fake()->randomElement(['day', 'night']) : null;
    
    // Create three readings: previous, current (to edit), and next
    $previousValue = fake()->randomFloat(2, 100, 200);
    $currentValue = $previousValue + fake()->randomFloat(2, 50, 100);
    $nextValue = $currentValue + fake()->randomFloat(2, 50, 100);
    
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
    
    // Try to edit the current reading to a value LESS than the previous reading
    $invalidValue = $previousValue - fake()->randomFloat(2, 1, 10);
    
    // Property: The system should reject edits that violate monotonicity (less than previous)
    $component = Livewire::test(MeterReadingResource\Pages\EditMeterReading::class, [
        'record' => $currentReading->id,
    ]);
    
    $component->fillForm([
        'meter_id' => $meter->id,
        'reading_date' => $currentReading->reading_date->format('Y-m-d'),
        'value' => $invalidValue,
        'zone' => $zone,
        'change_reason' => 'Adjustment required for audit',
    ]);
    
    $component->call('save');

    $errors = $component->instance()->getErrorBag()->toArray();
    expect($errors)->toHaveKey('data.value');
    expect($errors['data.value'][0] ?? null)->toBe(__('meter_readings.validation.custom.monotonicity_lower', [
        'previous' => $previousValue,
    ]));
    
    // Verify that the reading was not updated
    $currentReading->refresh();
    expect((string) $currentReading->value)->toBe(number_format($currentValue, 2, '.', ''));
})->repeat(100);

// Feature: filament-admin-panel, Property 3: Monotonicity enforcement
// Validates: Requirements 2.5
test('Filament MeterReadingResource rejects edits that exceed the next reading value', function () {
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
    
    $zone = $supportsZones ? fake()->randomElement(['day', 'night']) : null;
    
    // Create three readings: previous, current (to edit), and next
    $previousValue = fake()->randomFloat(2, 100, 200);
    $currentValue = $previousValue + fake()->randomFloat(2, 50, 100);
    $nextValue = $currentValue + fake()->randomFloat(2, 50, 100);
    
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
    
    // Try to edit the current reading to a value GREATER than the next reading
    $invalidValue = $nextValue + fake()->randomFloat(2, 1, 10);
    
    // Property: The system should reject edits that exceed the next reading
    $component = Livewire::test(MeterReadingResource\Pages\EditMeterReading::class, [
        'record' => $currentReading->id,
    ]);
    
    $component->fillForm([
        'meter_id' => $meter->id,
        'reading_date' => $currentReading->reading_date->format('Y-m-d'),
        'value' => $invalidValue,
        'zone' => $zone,
        'change_reason' => 'Adjustment required for audit',
    ]);
    
    $component->call('save');

    $errors = $component->instance()->getErrorBag()->toArray();
    expect($errors)->toHaveKey('data.value');
    expect($errors['data.value'][0] ?? null)->toBe(__('meter_readings.validation.custom.monotonicity_higher', [
        'next' => $nextValue,
    ]));
    
    // Verify that the reading was not updated
    $currentReading->refresh();
    expect((string) $currentReading->value)->toBe(number_format($currentValue, 2, '.', ''));
})->repeat(100);

// Feature: filament-admin-panel, Property 3: Monotonicity enforcement
// Validates: Requirements 2.5
test('Filament MeterReadingResource allows valid edits within monotonicity bounds', function () {
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
    
    $zone = $supportsZones ? fake()->randomElement(['day', 'night']) : null;
    
    // Create three readings: previous, current (to edit), and next
    $previousValue = fake()->randomFloat(2, 100, 200);
    $currentValue = $previousValue + fake()->randomFloat(2, 50, 100);
    $nextValue = $currentValue + fake()->randomFloat(2, 50, 100);
    
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
    
    // Edit the current reading to a valid value (between previous and next)
    $validValue = $previousValue + fake()->randomFloat(2, 10, ($nextValue - $previousValue - 10));
    
    // Property: The system should accept edits that maintain monotonicity
    $component = Livewire::test(MeterReadingResource\Pages\EditMeterReading::class, [
        'record' => $currentReading->id,
    ]);
    
    $component->fillForm([
        'meter_id' => $meter->id,
        'reading_date' => $currentReading->reading_date->format('Y-m-d'),
        'value' => $validValue,
        'zone' => $zone,
        'change_reason' => 'Adjustment required for audit',
    ]);
    
    // Try to save - this should succeed
    $component->call('save');

    $errors = $component->instance()->getErrorBag()->toArray();
    expect($errors)->toBe([]);
    
    // Verify that the reading was updated
    $currentReading->refresh();
    expect((string) $currentReading->value)->toBe(number_format($validValue, 2, '.', ''));
    
    // Verify monotonicity is maintained
    expect((float) $currentReading->value)->toBeGreaterThanOrEqual($previousValue);
    expect((float) $currentReading->value)->toBeLessThanOrEqual($nextValue);
})->repeat(100);
