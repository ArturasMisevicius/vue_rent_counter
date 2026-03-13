<?php

use App\Enums\MeterType;
use App\Enums\UserRole;
use App\Models\Meter;
use App\Models\Property;

/**
 * Test Helper Methods Verification
 * 
 * Tests that the helper methods in TestCase work correctly.
 * 
 * Requirements: 10.1, 10.2, 10.3, 10.4, 10.5
 */

test('actingAsAdmin creates and authenticates admin user', function () {
    $admin = $this->actingAsAdmin();

    expect($admin)->toBeInstanceOf(\App\Models\User::class)
        ->and($admin->role)->toBe(UserRole::ADMIN)
        ->and($admin->tenant_id)->toBe(1);

    $this->assertAuthenticatedAs($admin);
});

test('actingAsManager creates and authenticates manager user with correct tenant', function () {
    $manager = $this->actingAsManager(2);

    expect($manager)->toBeInstanceOf(\App\Models\User::class)
        ->and($manager->role)->toBe(UserRole::MANAGER)
        ->and($manager->tenant_id)->toBe(2);

    $this->assertAuthenticatedAs($manager);
});

test('actingAsManager defaults to tenant 1 when no tenant specified', function () {
    $manager = $this->actingAsManager();

    expect($manager->tenant_id)->toBe(1)
        ->and($manager->role)->toBe(UserRole::MANAGER);

    $this->assertAuthenticatedAs($manager);
});

test('actingAsTenant creates and authenticates tenant user with correct tenant', function () {
    $tenant = $this->actingAsTenant(2);

    expect($tenant)->toBeInstanceOf(\App\Models\User::class)
        ->and($tenant->role)->toBe(UserRole::TENANT)
        ->and($tenant->tenant_id)->toBe(2);

    $this->assertAuthenticatedAs($tenant);
});

test('actingAsTenant defaults to tenant 1 when no tenant specified', function () {
    $tenant = $this->actingAsTenant();

    expect($tenant->tenant_id)->toBe(1)
        ->and($tenant->role)->toBe(UserRole::TENANT);

    $this->assertAuthenticatedAs($tenant);
});

test('createTestProperty creates property with correct tenant', function () {
    $property = $this->createTestProperty(2);

    expect($property)->toBeInstanceOf(Property::class)
        ->and($property->tenant_id)->toBe(2)
        ->and($property->address)->toContain('Test Address');

    $this->assertDatabaseHas('properties', [
        'id' => $property->id,
        'tenant_id' => 2,
    ]);
});

test('createTestProperty defaults to tenant 1 when no tenant specified', function () {
    $property = $this->createTestProperty();

    expect($property->tenant_id)->toBe(1);
});

test('createTestProperty accepts custom attributes', function () {
    $property = $this->createTestProperty(1, [
        'area_sqm' => 100.5,
        'address' => 'Custom Address 123',
    ]);

    expect((float) $property->area_sqm)->toBe(100.5)
        ->and($property->address)->toBe('Custom Address 123');
});

test('createTestMeterReading creates reading with correct meter and value', function () {
    // Create a meter first
    $property = $this->createTestProperty(1);
    $meter = Meter::factory()->create([
        'tenant_id' => 1,
        'property_id' => $property->id,
        'type' => MeterType::ELECTRICITY,
    ]);

    $reading = $this->createTestMeterReading($meter->id, 1500.5);

    expect($reading)->toBeInstanceOf(\App\Models\MeterReading::class)
        ->and($reading->meter_id)->toBe($meter->id)
        ->and((float) $reading->value)->toBe(1500.5)
        ->and($reading->tenant_id)->toBe(1)
        ->and($reading->entered_by)->not->toBeNull();

    $this->assertDatabaseHas('meter_readings', [
        'id' => $reading->id,
        'meter_id' => $meter->id,
        'value' => 1500.5,
    ]);
});

test('createTestMeterReading creates or finds manager user for tenant', function () {
    // Create a meter for tenant 2
    $property = $this->createTestProperty(2);
    $meter = Meter::factory()->create([
        'tenant_id' => 2,
        'property_id' => $property->id,
        'type' => MeterType::WATER_COLD,
    ]);

    $reading = $this->createTestMeterReading($meter->id, 500.0);

    // Verify the entered_by user is a manager for tenant 2
    $enteredByUser = \App\Models\User::find($reading->entered_by);
    expect($enteredByUser->role)->toBe(UserRole::MANAGER)
        ->and($enteredByUser->tenant_id)->toBe(2);
});

test('createTestMeterReading accepts custom attributes', function () {
    $property = $this->createTestProperty(1);
    $meter = Meter::factory()->create([
        'tenant_id' => 1,
        'property_id' => $property->id,
        'type' => MeterType::ELECTRICITY,
        'supports_zones' => true,
    ]);

    $customDate = now()->subDays(5);
    $reading = $this->createTestMeterReading($meter->id, 2000.0, [
        'reading_date' => $customDate,
        'zone' => 'day',
    ]);

    expect($reading->reading_date->format('Y-m-d'))->toBe($customDate->format('Y-m-d'))
        ->and($reading->zone)->toBe('day');
});

test('helper methods can be chained in test scenarios', function () {
    // Authenticate as manager
    $manager = $this->actingAsManager(1);

    // Create a property
    $property = $this->createTestProperty(1);

    // Create a meter for the property
    $meter = Meter::factory()->create([
        'tenant_id' => 1,
        'property_id' => $property->id,
        'type' => MeterType::ELECTRICITY,
    ]);

    // Create a meter reading
    $reading = $this->createTestMeterReading($meter->id, 1000.0);

    // Verify everything is connected correctly
    expect($manager->tenant_id)->toBe(1)
        ->and($property->tenant_id)->toBe(1)
        ->and($meter->tenant_id)->toBe(1)
        ->and($reading->tenant_id)->toBe(1);

    $this->assertAuthenticatedAs($manager);
});
