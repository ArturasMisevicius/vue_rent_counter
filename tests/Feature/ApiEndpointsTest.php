<?php

use App\Enums\MeterType;
use App\Enums\PropertyType;
use App\Enums\UserRole;
use App\Models\Building;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\User;

test('api endpoint can submit meter reading', function () {
    $user = User::factory()->create([
        'tenant_id' => 1,
        'role' => UserRole::MANAGER,
    ]);
    
    $property = Property::factory()->create(['tenant_id' => 1]);
    $meter = Meter::factory()->create([
        'tenant_id' => 1,
        'property_id' => $property->id,
        'supports_zones' => false,
    ]);
    
    // Create a previous reading
    MeterReading::factory()->create([
        'tenant_id' => 1,
        'meter_id' => $meter->id,
        'value' => 1000.00,
        'reading_date' => now()->subDays(30),
        'entered_by' => $user->id,
    ]);
    
    $response = $this->actingAs($user)
        ->postJson('/api/meter-readings', [
            'meter_id' => $meter->id,
            'reading_date' => now()->format('Y-m-d'),
            'value' => 1150.50,
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
    $response->assertJson([
        'meter_id' => $meter->id,
        'value' => 1150.50,
    ]);
});

test('api endpoint rejects meter reading lower than previous', function () {
    $user = User::factory()->create([
        'tenant_id' => 1,
        'role' => UserRole::MANAGER,
    ]);
    
    $property = Property::factory()->create(['tenant_id' => 1]);
    $meter = Meter::factory()->create([
        'tenant_id' => 1,
        'property_id' => $property->id,
        'supports_zones' => false,
    ]);
    
    // Create a previous reading
    MeterReading::factory()->create([
        'tenant_id' => 1,
        'meter_id' => $meter->id,
        'value' => 1000.00,
        'reading_date' => now()->subDays(30),
        'entered_by' => $user->id,
    ]);
    
    $response = $this->actingAs($user)
        ->postJson('/api/meter-readings', [
            'meter_id' => $meter->id,
            'reading_date' => now()->format('Y-m-d'),
            'value' => 950.00, // Lower than previous
        ]);
    
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['value']);
});

test('api endpoint lists properties for authenticated user', function () {
    $user = User::factory()->create([
        'tenant_id' => 1,
        'role' => UserRole::MANAGER,
    ]);
    
    // Create properties for tenant 1
    $property1 = Property::factory()->create([
        'tenant_id' => 1,
        'address' => 'Test Address 1',
        'type' => PropertyType::APARTMENT,
    ]);
    
    $property2 = Property::factory()->create([
        'tenant_id' => 1,
        'address' => 'Test Address 2',
        'type' => PropertyType::HOUSE,
    ]);
    
    // Create property for tenant 2 (should not be visible)
    Property::factory()->create([
        'tenant_id' => 2,
        'address' => 'Other Tenant Property',
    ]);
    
    $response = $this->actingAs($user)
        ->getJson('/api/properties');
    
    $response->assertStatus(200);
    $response->assertJsonCount(2);
    $response->assertJsonFragment(['address' => 'Test Address 1']);
    $response->assertJsonFragment(['address' => 'Test Address 2']);
    $response->assertJsonMissing(['address' => 'Other Tenant Property']);
});

test('api endpoint returns property details with meters', function () {
    $user = User::factory()->create([
        'tenant_id' => 1,
        'role' => UserRole::MANAGER,
    ]);
    
    $building = Building::factory()->create(['tenant_id' => 1]);
    $property = Property::factory()->create([
        'tenant_id' => 1,
        'building_id' => $building->id,
        'address' => 'Test Property',
        'type' => PropertyType::APARTMENT,
        'area_sqm' => 75.5,
    ]);
    
    $meter = Meter::factory()->create([
        'tenant_id' => 1,
        'property_id' => $property->id,
        'type' => MeterType::ELECTRICITY,
        'serial_number' => 'EL-123456',
    ]);
    
    MeterReading::factory()->create([
        'tenant_id' => 1,
        'meter_id' => $meter->id,
        'value' => 1500.00,
        'reading_date' => now()->subDays(5),
        'entered_by' => $user->id,
    ]);
    
    $response = $this->actingAs($user)
        ->getJson("/api/properties/{$property->id}");
    
    $response->assertStatus(200);
    $response->assertJsonStructure([
        'id',
        'address',
        'type',
        'area_sqm',
        'building_id',
        'building' => ['id', 'address', 'total_apartments'],
        'meters' => [
            '*' => [
                'id',
                'serial_number',
                'type',
                'supports_zones',
                'installation_date',
                'last_reading',
            ],
        ],
    ]);
    $response->assertJson([
        'address' => 'Test Property',
        'area_sqm' => 75.5,
    ]);
});

test('api endpoint prevents cross-tenant property access', function () {
    $user = User::factory()->create([
        'tenant_id' => 1,
        'role' => UserRole::MANAGER,
    ]);
    
    // Create property for tenant 2
    $property = Property::factory()->create([
        'tenant_id' => 2,
        'address' => 'Other Tenant Property',
    ]);
    
    $response = $this->actingAs($user)
        ->getJson("/api/properties/{$property->id}");
    
    $response->assertStatus(404);
});

test('api endpoint requires authentication', function () {
    $property = Property::factory()->create(['tenant_id' => 1]);
    
    $response = $this->getJson('/api/properties');
    $response->assertStatus(401); // Unauthorized
    
    $response = $this->getJson("/api/properties/{$property->id}");
    $response->assertStatus(401); // Unauthorized
    
    $response = $this->postJson('/api/meter-readings', [
        'meter_id' => 1,
        'reading_date' => now()->format('Y-m-d'),
        'value' => 1000.00,
    ]);
    $response->assertStatus(401); // Unauthorized
});

test('api endpoint requires manager or admin role', function () {
    $tenant = User::factory()->create([
        'tenant_id' => 1,
        'role' => UserRole::TENANT,
    ]);
    
    $property = Property::factory()->create(['tenant_id' => 1]);
    
    $response = $this->actingAs($tenant)
        ->getJson('/api/properties');
    
    $response->assertStatus(403);
});
