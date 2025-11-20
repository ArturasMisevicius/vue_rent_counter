<?php

use App\Enums\MeterType;
use App\Enums\UserRole;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\MeterReadingAudit;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Meter Reading Validation Tests
 * 
 * Tests validation rules for meter reading entry including:
 * - Storage with timestamp and user reference
 * - Monotonicity enforcement (readings cannot decrease)
 * - Temporal validation (no future dates)
 * - Multi-zone meter support
 * - Audit trail creation
 * 
 * Requirements: 6.1, 6.2, 6.3, 6.4, 6.5
 */

test('valid reading is stored with timestamp and user reference', function () {
    // Create manager user
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
    ]);
    
    // Create property and meter
    $property = Property::factory()->create([
        'tenant_id' => $manager->tenant_id,
    ]);
    
    $meter = Meter::factory()->create([
        'tenant_id' => $manager->tenant_id,
        'property_id' => $property->id,
        'supports_zones' => false,
    ]);

    // Submit valid meter reading
    $response = $this->actingAs($manager)->post('/manager/meter-readings', [
        'meter_id' => $meter->id,
        'reading_date' => now()->format('Y-m-d'),
        'value' => 1000.5,
        'zone' => null,
        'tenant_id' => $manager->tenant_id,
    ]);

    // Assert redirect (successful submission)
    $response->assertRedirect();
    
    // Assert meter reading was created with timestamp and user reference
    $reading = MeterReading::where('meter_id', $meter->id)->first();
    
    expect($reading)->not->toBeNull();
    expect($reading->value)->toBe('1000.50');
    expect($reading->entered_by)->toBe($manager->id);
    expect($reading->created_at)->not->toBeNull();
    expect($reading->reading_date)->not->toBeNull();
});

test('reading lower than previous is rejected', function () {
    // Create manager user
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
    ]);
    
    // Create property and meter
    $property = Property::factory()->create([
        'tenant_id' => $manager->tenant_id,
    ]);
    
    $meter = Meter::factory()->create([
        'tenant_id' => $manager->tenant_id,
        'property_id' => $property->id,
        'supports_zones' => false,
    ]);
    
    // Create previous reading
    MeterReading::factory()->create([
        'tenant_id' => $manager->tenant_id,
        'meter_id' => $meter->id,
        'reading_date' => now()->subMonth(),
        'value' => 1000.0,
        'zone' => null,
        'entered_by' => $manager->id,
    ]);

    // Attempt to submit reading lower than previous
    $response = $this->actingAs($manager)->post('/manager/meter-readings', [
        'meter_id' => $meter->id,
        'reading_date' => now()->format('Y-m-d'),
        'value' => 950.0, // Lower than previous 1000.0
        'zone' => null,
        'tenant_id' => $manager->tenant_id,
    ]);

    // Assert validation error
    $response->assertSessionHasErrors('value');
    
    // Assert error message mentions previous reading
    $errors = session('errors');
    expect($errors->get('value')[0])->toContain('cannot be lower than previous reading');
    expect($errors->get('value')[0])->toContain('1000');
    
    // Assert reading was not created
    $this->assertDatabaseMissing('meter_readings', [
        'meter_id' => $meter->id,
        'value' => 950.0,
    ]);
});

test('reading with future date is rejected', function () {
    // Create manager user
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
    ]);
    
    // Create property and meter
    $property = Property::factory()->create([
        'tenant_id' => $manager->tenant_id,
    ]);
    
    $meter = Meter::factory()->create([
        'tenant_id' => $manager->tenant_id,
        'property_id' => $property->id,
        'supports_zones' => false,
    ]);

    // Attempt to submit reading with future date
    $futureDate = now()->addDays(5)->format('Y-m-d');
    
    $response = $this->actingAs($manager)->post('/manager/meter-readings', [
        'meter_id' => $meter->id,
        'reading_date' => $futureDate,
        'value' => 1000.0,
        'zone' => null,
        'tenant_id' => $manager->tenant_id,
    ]);

    // Assert validation error
    $response->assertSessionHasErrors('reading_date');
    
    // Assert error message mentions future date
    $errors = session('errors');
    expect($errors->get('reading_date')[0])->toContain('cannot be in the future');
    
    // Assert reading was not created
    $this->assertDatabaseMissing('meter_readings', [
        'meter_id' => $meter->id,
        'reading_date' => $futureDate,
    ]);
});

test('multi-zone meter accepts separate zone readings', function () {
    // Create manager user
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
    ]);
    
    // Create property and multi-zone meter
    $property = Property::factory()->create([
        'tenant_id' => $manager->tenant_id,
    ]);
    
    $meter = Meter::factory()->create([
        'tenant_id' => $manager->tenant_id,
        'property_id' => $property->id,
        'type' => MeterType::ELECTRICITY,
        'supports_zones' => true,
    ]);

    // Submit day zone reading
    $response1 = $this->actingAs($manager)->post('/manager/meter-readings', [
        'meter_id' => $meter->id,
        'reading_date' => now()->format('Y-m-d'),
        'value' => 1500.0,
        'zone' => 'day',
        'tenant_id' => $manager->tenant_id,
    ]);

    // Assert successful submission
    $response1->assertRedirect();
    
    // Submit night zone reading for same date
    $response2 = $this->actingAs($manager)->post('/manager/meter-readings', [
        'meter_id' => $meter->id,
        'reading_date' => now()->format('Y-m-d'),
        'value' => 800.0,
        'zone' => 'night',
        'tenant_id' => $manager->tenant_id,
    ]);

    // Assert successful submission
    $response2->assertRedirect();
    
    // Assert both readings were created
    $this->assertDatabaseHas('meter_readings', [
        'meter_id' => $meter->id,
        'zone' => 'day',
        'value' => 1500.0,
    ]);
    
    $this->assertDatabaseHas('meter_readings', [
        'meter_id' => $meter->id,
        'zone' => 'night',
        'value' => 800.0,
    ]);
    
    // Assert we have exactly 2 readings for this meter
    $readings = MeterReading::where('meter_id', $meter->id)->get();
    expect($readings)->toHaveCount(2);
});

test('audit trail is created for readings', function () {
    // Create manager user
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
    ]);
    
    // Create property and meter
    $property = Property::factory()->create([
        'tenant_id' => $manager->tenant_id,
    ]);
    
    $meter = Meter::factory()->create([
        'tenant_id' => $manager->tenant_id,
        'property_id' => $property->id,
        'supports_zones' => false,
    ]);
    
    // Create initial reading
    $reading = MeterReading::factory()->create([
        'tenant_id' => $manager->tenant_id,
        'meter_id' => $meter->id,
        'value' => 1000.0,
        'entered_by' => $manager->id,
    ]);

    // Update the reading (this should create an audit trail)
    $response = $this->actingAs($manager)->put("/manager/meter-readings/{$reading->id}", [
        'meter_id' => $meter->id,
        'reading_date' => $reading->reading_date->format('Y-m-d'),
        'value' => 1050.0,
        'zone' => null,
        'tenant_id' => $manager->tenant_id,
        'change_reason' => 'Correction due to misread',
    ]);

    // Assert redirect (successful update)
    $response->assertRedirect();
    
    // Assert audit trail was created
    $this->assertDatabaseHas('meter_reading_audits', [
        'meter_reading_id' => $reading->id,
        'changed_by_user_id' => $manager->id,
        'old_value' => 1000.0,
        'new_value' => 1050.0,
    ]);
    
    // Verify audit trail relationship
    $reading->refresh();
    $auditRecords = $reading->auditTrail;
    
    expect($auditRecords)->toHaveCount(1);
    expect($auditRecords->first()->old_value)->toBe('1000.00');
    expect($auditRecords->first()->new_value)->toBe('1050.00');
    expect($auditRecords->first()->changed_by_user_id)->toBe($manager->id);
});

test('reading equal to previous is accepted', function () {
    // Create manager user
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
    ]);
    
    // Create property and meter
    $property = Property::factory()->create([
        'tenant_id' => $manager->tenant_id,
    ]);
    
    $meter = Meter::factory()->create([
        'tenant_id' => $manager->tenant_id,
        'property_id' => $property->id,
        'supports_zones' => false,
    ]);
    
    // Create previous reading
    MeterReading::factory()->create([
        'tenant_id' => $manager->tenant_id,
        'meter_id' => $meter->id,
        'reading_date' => now()->subMonth(),
        'value' => 1000.0,
        'zone' => null,
        'entered_by' => $manager->id,
    ]);

    // Submit reading equal to previous (zero consumption is valid)
    $response = $this->actingAs($manager)->post('/manager/meter-readings', [
        'meter_id' => $meter->id,
        'reading_date' => now()->format('Y-m-d'),
        'value' => 1000.0, // Equal to previous
        'zone' => null,
        'tenant_id' => $manager->tenant_id,
    ]);

    // Assert successful submission
    $response->assertRedirect();
    
    // Assert reading was created
    $this->assertDatabaseHas('meter_readings', [
        'meter_id' => $meter->id,
        'value' => 1000.0,
        'entered_by' => $manager->id,
    ]);
});

test('reading with today date is accepted', function () {
    // Create manager user
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
    ]);
    
    // Create property and meter
    $property = Property::factory()->create([
        'tenant_id' => $manager->tenant_id,
    ]);
    
    $meter = Meter::factory()->create([
        'tenant_id' => $manager->tenant_id,
        'property_id' => $property->id,
        'supports_zones' => false,
    ]);

    // Submit reading with today's date
    $response = $this->actingAs($manager)->post('/manager/meter-readings', [
        'meter_id' => $meter->id,
        'reading_date' => now()->format('Y-m-d'),
        'value' => 1000.0,
        'zone' => null,
        'tenant_id' => $manager->tenant_id,
    ]);

    // Assert successful submission
    $response->assertRedirect();
    
    // Assert reading was created
    $this->assertDatabaseHas('meter_readings', [
        'meter_id' => $meter->id,
        'value' => 1000.0,
    ]);
});

test('zone is required for multi-zone meters', function () {
    // Create manager user
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
    ]);
    
    // Create property and multi-zone meter
    $property = Property::factory()->create([
        'tenant_id' => $manager->tenant_id,
    ]);
    
    $meter = Meter::factory()->create([
        'tenant_id' => $manager->tenant_id,
        'property_id' => $property->id,
        'type' => MeterType::ELECTRICITY,
        'supports_zones' => true,
    ]);

    // Attempt to submit reading without zone
    $response = $this->actingAs($manager)->post('/manager/meter-readings', [
        'meter_id' => $meter->id,
        'reading_date' => now()->format('Y-m-d'),
        'value' => 1000.0,
        'zone' => null, // Missing zone for multi-zone meter
        'tenant_id' => $manager->tenant_id,
    ]);

    // Assert validation error
    $response->assertSessionHasErrors('zone');
    
    // Assert error message mentions zone requirement
    $errors = session('errors');
    expect($errors->get('zone')[0])->toContain('Zone is required');
    
    // Assert reading was not created
    $this->assertDatabaseMissing('meter_readings', [
        'meter_id' => $meter->id,
        'value' => 1000.0,
    ]);
});

test('zone is rejected for non-zone meters', function () {
    // Create manager user
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
    ]);
    
    // Create property and non-zone meter
    $property = Property::factory()->create([
        'tenant_id' => $manager->tenant_id,
    ]);
    
    $meter = Meter::factory()->create([
        'tenant_id' => $manager->tenant_id,
        'property_id' => $property->id,
        'type' => MeterType::WATER_COLD,
        'supports_zones' => false,
    ]);

    // Attempt to submit reading with zone for non-zone meter
    $response = $this->actingAs($manager)->post('/manager/meter-readings', [
        'meter_id' => $meter->id,
        'reading_date' => now()->format('Y-m-d'),
        'value' => 1000.0,
        'zone' => 'day', // Zone provided for non-zone meter
        'tenant_id' => $manager->tenant_id,
    ]);

    // Assert validation error
    $response->assertSessionHasErrors('zone');
    
    // Assert error message mentions zone not supported
    $errors = session('errors');
    expect($errors->get('zone')[0])->toContain('does not support zone-based readings');
    
    // Assert reading was not created
    $this->assertDatabaseMissing('meter_readings', [
        'meter_id' => $meter->id,
        'value' => 1000.0,
    ]);
});

test('monotonicity is enforced per zone for multi-zone meters', function () {
    // Create manager user
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
    ]);
    
    // Create property and multi-zone meter
    $property = Property::factory()->create([
        'tenant_id' => $manager->tenant_id,
    ]);
    
    $meter = Meter::factory()->create([
        'tenant_id' => $manager->tenant_id,
        'property_id' => $property->id,
        'type' => MeterType::ELECTRICITY,
        'supports_zones' => true,
    ]);
    
    // Create previous readings for both zones
    MeterReading::factory()->create([
        'tenant_id' => $manager->tenant_id,
        'meter_id' => $meter->id,
        'reading_date' => now()->subMonth(),
        'value' => 1000.0,
        'zone' => 'day',
        'entered_by' => $manager->id,
    ]);
    
    MeterReading::factory()->create([
        'tenant_id' => $manager->tenant_id,
        'meter_id' => $meter->id,
        'reading_date' => now()->subMonth(),
        'value' => 500.0,
        'zone' => 'night',
        'entered_by' => $manager->id,
    ]);

    // Attempt to submit day reading lower than previous day reading
    $response1 = $this->actingAs($manager)->post('/manager/meter-readings', [
        'meter_id' => $meter->id,
        'reading_date' => now()->format('Y-m-d'),
        'value' => 950.0, // Lower than previous day reading (1000.0)
        'zone' => 'day',
        'tenant_id' => $manager->tenant_id,
    ]);

    // Assert validation error for day zone
    $response1->assertSessionHasErrors('value');
    
    // Submit valid night reading (higher than previous night reading)
    $response2 = $this->actingAs($manager)->post('/manager/meter-readings', [
        'meter_id' => $meter->id,
        'reading_date' => now()->format('Y-m-d'),
        'value' => 600.0, // Higher than previous night reading (500.0)
        'zone' => 'night',
        'tenant_id' => $manager->tenant_id,
    ]);

    // Assert successful submission for night zone
    $response2->assertRedirect();
    
    // Assert night reading was created but day reading was not
    $this->assertDatabaseHas('meter_readings', [
        'meter_id' => $meter->id,
        'zone' => 'night',
        'value' => 600.0,
    ]);
    
    $this->assertDatabaseMissing('meter_readings', [
        'meter_id' => $meter->id,
        'zone' => 'day',
        'value' => 950.0,
    ]);
});

test('negative reading value is rejected', function () {
    // Create manager user
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
    ]);
    
    // Create property and meter
    $property = Property::factory()->create([
        'tenant_id' => $manager->tenant_id,
    ]);
    
    $meter = Meter::factory()->create([
        'tenant_id' => $manager->tenant_id,
        'property_id' => $property->id,
        'supports_zones' => false,
    ]);

    // Attempt to submit negative reading
    $response = $this->actingAs($manager)->post('/manager/meter-readings', [
        'meter_id' => $meter->id,
        'reading_date' => now()->format('Y-m-d'),
        'value' => -100.0, // Negative value
        'zone' => null,
        'tenant_id' => $manager->tenant_id,
    ]);

    // Assert validation error
    $response->assertSessionHasErrors('value');
    
    // Assert error message mentions positive number
    $errors = session('errors');
    expect($errors->get('value')[0])->toContain('must be a positive number');
    
    // Assert reading was not created
    $this->assertDatabaseMissing('meter_readings', [
        'meter_id' => $meter->id,
        'value' => -100.0,
    ]);
});

test('non-numeric reading value is rejected', function () {
    // Create manager user
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
    ]);
    
    // Create property and meter
    $property = Property::factory()->create([
        'tenant_id' => $manager->tenant_id,
    ]);
    
    $meter = Meter::factory()->create([
        'tenant_id' => $manager->tenant_id,
        'property_id' => $property->id,
        'supports_zones' => false,
    ]);

    // Attempt to submit non-numeric reading
    $response = $this->actingAs($manager)->post('/manager/meter-readings', [
        'meter_id' => $meter->id,
        'reading_date' => now()->format('Y-m-d'),
        'value' => 'abc', // Non-numeric value
        'zone' => null,
        'tenant_id' => $manager->tenant_id,
    ]);

    // Assert validation error
    $response->assertSessionHasErrors('value');
    
    // Assert error message mentions number requirement
    $errors = session('errors');
    expect($errors->get('value')[0])->toContain('must be a number');
    
    // Assert reading was not created
    $this->assertDatabaseMissing('meter_readings', [
        'meter_id' => $meter->id,
    ]);
});

test('missing required fields are rejected', function () {
    // Create manager user
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
    ]);

    // Attempt to submit reading with missing fields
    $response = $this->actingAs($manager)->post('/manager/meter-readings', [
        // Missing meter_id, reading_date, value
        'tenant_id' => $manager->tenant_id,
    ]);

    // Assert validation errors for all required fields
    $response->assertSessionHasErrors(['meter_id', 'reading_date', 'value']);
});

test('invalid meter id is rejected', function () {
    // Create manager user
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
    ]);

    // Attempt to submit reading with non-existent meter
    $response = $this->actingAs($manager)->post('/manager/meter-readings', [
        'meter_id' => 99999, // Non-existent meter
        'reading_date' => now()->format('Y-m-d'),
        'value' => 1000.0,
        'zone' => null,
        'tenant_id' => $manager->tenant_id,
    ]);

    // Assert validation error
    $response->assertSessionHasErrors('meter_id');
    
    // Assert error message mentions meter not existing
    $errors = session('errors');
    expect($errors->get('meter_id')[0])->toContain('does not exist');
});

test('audit trail includes change reason when provided', function () {
    // Create manager user
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
    ]);
    
    // Create property and meter
    $property = Property::factory()->create([
        'tenant_id' => $manager->tenant_id,
    ]);
    
    $meter = Meter::factory()->create([
        'tenant_id' => $manager->tenant_id,
        'property_id' => $property->id,
        'supports_zones' => false,
    ]);
    
    // Create initial reading
    $reading = MeterReading::factory()->create([
        'tenant_id' => $manager->tenant_id,
        'meter_id' => $meter->id,
        'value' => 1000.0,
        'entered_by' => $manager->id,
    ]);

    // Update the reading with a change reason
    $changeReason = 'Meter was misread initially, correcting to actual value';
    
    $response = $this->actingAs($manager)->put("/manager/meter-readings/{$reading->id}", [
        'meter_id' => $meter->id,
        'reading_date' => $reading->reading_date->format('Y-m-d'),
        'value' => 1075.5,
        'zone' => null,
        'tenant_id' => $manager->tenant_id,
        'change_reason' => $changeReason,
    ]);

    // Assert redirect (successful update)
    $response->assertRedirect();
    
    // Assert audit trail includes the change reason
    $audit = MeterReadingAudit::where('meter_reading_id', $reading->id)->first();
    
    expect($audit)->not->toBeNull();
    expect($audit->change_reason)->toBe($changeReason);
    expect($audit->old_value)->toBe('1000.00');
    expect($audit->new_value)->toBe('1075.50');
});
