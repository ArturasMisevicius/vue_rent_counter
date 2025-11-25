<?php

declare(strict_types=1);

use App\Models\Building;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create test organization and users
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->manager = User::factory()->create(['role' => 'manager', 'tenant_id' => $this->admin->tenant_id]);
    
    // Create test data
    $building = Building::factory()->create(['tenant_id' => $this->admin->tenant_id]);
    $property = Property::factory()->create([
        'tenant_id' => $this->admin->tenant_id,
        'building_id' => $building->id,
    ]);
    $this->meter = Meter::factory()->create([
        'tenant_id' => $this->admin->tenant_id,
        'property_id' => $property->id,
        'supports_zones' => false,
    ]);
});

test('api can create meter reading with valid data', function () {
    $this->actingAs($this->manager);
    
    $response = $this->postJson('/api/meter-readings', [
        'meter_id' => $this->meter->id,
        'reading_date' => now()->format('Y-m-d'),
        'value' => 1234.56,
    ]);
    
    $response->assertStatus(201);
    $response->assertJsonStructure([
        'id',
        'meter_id',
        'reading_date',
        'value',
        'zone',
        'entered_by',
        'created_at',
    ]);
    
    expect($response->json('meter_id'))->toBe($this->meter->id);
    expect($response->json('value'))->toBe('1234.56');
    expect($response->json('entered_by'))->toBe($this->manager->id);
});

test('api rejects reading lower than previous reading', function () {
    // Create previous reading
    MeterReading::factory()->create([
        'tenant_id' => $this->admin->tenant_id,
        'meter_id' => $this->meter->id,
        'value' => 1000.00,
        'reading_date' => now()->subDays(7),
        'entered_by' => $this->admin->id,
    ]);
    
    $this->actingAs($this->manager);
    
    $response = $this->postJson('/api/meter-readings', [
        'meter_id' => $this->meter->id,
        'reading_date' => now()->format('Y-m-d'),
        'value' => 900.00, // Lower than previous
    ]);
    
    $response->assertStatus(422);
    $response->assertJsonValidationErrors('value');
});

test('api rejects future reading date', function () {
    $this->actingAs($this->manager);
    
    $response = $this->postJson('/api/meter-readings', [
        'meter_id' => $this->meter->id,
        'reading_date' => now()->addDays(1)->format('Y-m-d'),
        'value' => 1234.56,
    ]);
    
    $response->assertStatus(422);
    $response->assertJsonValidationErrors('reading_date');
});

test('api handles multi-zone meter readings', function () {
    $multiZoneMeter = Meter::factory()->create([
        'tenant_id' => $this->admin->tenant_id,
        'property_id' => $this->meter->property_id,
        'supports_zones' => true,
    ]);
    
    $this->actingAs($this->manager);
    
    // Create day zone reading
    $response = $this->postJson('/api/meter-readings', [
        'meter_id' => $multiZoneMeter->id,
        'reading_date' => now()->format('Y-m-d'),
        'value' => 500.00,
        'zone' => 'day',
    ]);
    
    $response->assertStatus(201);
    expect($response->json('zone'))->toBe('day');
    
    // Create night zone reading
    $response = $this->postJson('/api/meter-readings', [
        'meter_id' => $multiZoneMeter->id,
        'reading_date' => now()->format('Y-m-d'),
        'value' => 300.00,
        'zone' => 'night',
    ]);
    
    $response->assertStatus(201);
    expect($response->json('zone'))->toBe('night');
});

test('api requires zone for multi-zone meters', function () {
    $multiZoneMeter = Meter::factory()->create([
        'tenant_id' => $this->admin->tenant_id,
        'property_id' => $this->meter->property_id,
        'supports_zones' => true,
    ]);
    
    $this->actingAs($this->manager);
    
    $response = $this->postJson('/api/meter-readings', [
        'meter_id' => $multiZoneMeter->id,
        'reading_date' => now()->format('Y-m-d'),
        'value' => 500.00,
        // Missing zone
    ]);
    
    $response->assertStatus(422);
    $response->assertJsonValidationErrors('zone');
});

test('api can show meter reading', function () {
    $reading = MeterReading::factory()->create([
        'tenant_id' => $this->admin->tenant_id,
        'meter_id' => $this->meter->id,
        'value' => 1234.56,
        'reading_date' => now()->subDays(7),
        'entered_by' => $this->admin->id,
    ]);
    
    $this->actingAs($this->manager);
    
    $response = $this->getJson("/api/meter-readings/{$reading->id}");
    
    $response->assertStatus(200);
    $response->assertJsonStructure([
        'id',
        'meter_id',
        'reading_date',
        'value',
        'zone',
        'entered_by',
        'created_at',
        'updated_at',
        'consumption',
        'meter' => [
            'id',
            'serial_number',
            'type',
            'supports_zones',
        ],
    ]);
    
    expect($response->json('id'))->toBe($reading->id);
    expect($response->json('value'))->toBe('1234.56');
});

test('api can update meter reading', function () {
    $reading = MeterReading::factory()->create([
        'tenant_id' => $this->admin->tenant_id,
        'meter_id' => $this->meter->id,
        'value' => 1000.00,
        'reading_date' => now()->subDays(7),
        'entered_by' => $this->admin->id,
    ]);
    
    $this->actingAs($this->manager);
    
    $response = $this->putJson("/api/meter-readings/{$reading->id}", [
        'value' => 1050.00,
        'change_reason' => 'Corrected misread digit from 1000 to 1050',
    ]);
    
    $response->assertStatus(200);
    $response->assertJsonStructure([
        'id',
        'meter_id',
        'reading_date',
        'value',
        'zone',
        'entered_by',
        'updated_at',
        'audit' => [
            'old_value',
            'new_value',
            'change_reason',
            'changed_by',
        ],
    ]);
    
    expect($response->json('value'))->toBe('1050.00');
    expect($response->json('audit.old_value'))->toBe('1000.00');
    expect($response->json('audit.new_value'))->toBe('1050.00');
    expect($response->json('audit.change_reason'))->toBe('Corrected misread digit from 1000 to 1050');
});

test('api update requires change reason', function () {
    $reading = MeterReading::factory()->create([
        'tenant_id' => $this->admin->tenant_id,
        'meter_id' => $this->meter->id,
        'value' => 1000.00,
        'reading_date' => now()->subDays(7),
        'entered_by' => $this->admin->id,
    ]);
    
    $this->actingAs($this->manager);
    
    $response = $this->putJson("/api/meter-readings/{$reading->id}", [
        'value' => 1050.00,
        // Missing change_reason
    ]);
    
    $response->assertStatus(422);
    $response->assertJsonValidationErrors('change_reason');
});

test('api update validates monotonicity', function () {
    // Create previous reading
    MeterReading::factory()->create([
        'tenant_id' => $this->admin->tenant_id,
        'meter_id' => $this->meter->id,
        'value' => 900.00,
        'reading_date' => now()->subDays(14),
        'entered_by' => $this->admin->id,
    ]);
    
    $reading = MeterReading::factory()->create([
        'tenant_id' => $this->admin->tenant_id,
        'meter_id' => $this->meter->id,
        'value' => 1000.00,
        'reading_date' => now()->subDays(7),
        'entered_by' => $this->admin->id,
    ]);
    
    $this->actingAs($this->manager);
    
    $response = $this->putJson("/api/meter-readings/{$reading->id}", [
        'value' => 850.00, // Lower than previous (900)
        'change_reason' => 'Attempting to set lower value',
    ]);
    
    $response->assertStatus(422);
    $response->assertJsonValidationErrors('value');
});

test('tenant cannot access api endpoints', function () {
    $tenant = User::factory()->create([
        'role' => 'tenant',
        'tenant_id' => $this->admin->tenant_id,
    ]);
    
    $this->actingAs($tenant);
    
    $response = $this->postJson('/api/meter-readings', [
        'meter_id' => $this->meter->id,
        'reading_date' => now()->format('Y-m-d'),
        'value' => 1234.56,
    ]);
    
    $response->assertForbidden();
});

test('unauthenticated user cannot access api endpoints', function () {
    $response = $this->postJson('/api/meter-readings', [
        'meter_id' => $this->meter->id,
        'reading_date' => now()->format('Y-m-d'),
        'value' => 1234.56,
    ]);
    
    $response->assertUnauthorized();
});
