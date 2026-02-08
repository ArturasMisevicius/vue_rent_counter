<?php

use App\Models\User;
use App\Models\Meter;
use App\Models\Property;
use App\Models\Provider;
use App\Models\Tariff;
use App\Models\MeterReading;
use App\Enums\UserRole;
use App\Enums\MeterType;
use App\Enums\ServiceType;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Set up tenant context
    session(['tenant_id' => 1]);
});

test('meter reading form page loads successfully for managers', function () {
    $user = User::factory()->create([
        'tenant_id' => 1,
        'role' => UserRole::MANAGER,
    ]);
    
    $property = Property::factory()->create(['tenant_id' => 1]);
    $meter = Meter::factory()->create([
        'tenant_id' => 1,
        'property_id' => $property->id,
    ]);
    
    $provider = Provider::factory()->create();
    
    $response = $this->actingAs($user)
        ->get(route('meter-readings.create'));
    
    $response->assertStatus(200);
    $response->assertViewIs('meter-readings.create');
    $response->assertViewHas('meters');
    $response->assertViewHas('providers');
});

test('api endpoint returns last reading for meter', function () {
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
    
    $reading = MeterReading::factory()->create([
        'tenant_id' => 1,
        'meter_id' => $meter->id,
        'value' => 1000.50,
        'reading_date' => now()->subDays(5),
        'entered_by' => $user->id,
    ]);
    
    $response = $this->actingAs($user)
        ->getJson("/api/meters/{$meter->id}/last-reading");
    
    $response->assertStatus(200);
    $response->assertJson([
        'value' => 1000.50,
    ]);
});

test('api endpoint returns 404 when no readings exist', function () {
    $user = User::factory()->create([
        'tenant_id' => 1,
        'role' => UserRole::MANAGER,
    ]);
    
    $property = Property::factory()->create(['tenant_id' => 1]);
    $meter = Meter::factory()->create([
        'tenant_id' => 1,
        'property_id' => $property->id,
    ]);
    
    $response = $this->actingAs($user)
        ->getJson("/api/meters/{$meter->id}/last-reading");
    
    $response->assertStatus(404);
});

test('api endpoint returns zone readings for multi-zone meters', function () {
    $user = User::factory()->create([
        'tenant_id' => 1,
        'role' => UserRole::MANAGER,
    ]);
    
    $property = Property::factory()->create(['tenant_id' => 1]);
    $meter = Meter::factory()->create([
        'tenant_id' => 1,
        'property_id' => $property->id,
        'type' => MeterType::ELECTRICITY,
        'supports_zones' => true,
    ]);
    
    MeterReading::factory()->create([
        'tenant_id' => 1,
        'meter_id' => $meter->id,
        'value' => 500.00,
        'zone' => 'day',
        'reading_date' => now()->subDays(5),
        'entered_by' => $user->id,
    ]);
    
    MeterReading::factory()->create([
        'tenant_id' => 1,
        'meter_id' => $meter->id,
        'value' => 300.00,
        'zone' => 'night',
        'reading_date' => now()->subDays(5),
        'entered_by' => $user->id,
    ]);
    
    $response = $this->actingAs($user)
        ->getJson("/api/meters/{$meter->id}/last-reading");
    
    $response->assertStatus(200);
    $response->assertJson([
        'day_value' => 500.00,
        'night_value' => 300.00,
    ]);
});

test('api endpoint returns active tariffs for provider', function () {
    $user = User::factory()->create([
        'tenant_id' => 1,
        'role' => UserRole::MANAGER,
    ]);
    
    $provider = Provider::factory()->create([
        'name' => 'Ignitis',
        'service_type' => ServiceType::ELECTRICITY,
    ]);
    
    $activeTariff = Tariff::factory()->create([
        'provider_id' => $provider->id,
        'name' => 'Standard Rate',
        'active_from' => now()->subDays(30),
        'active_until' => null,
    ]);
    
    $expiredTariff = Tariff::factory()->create([
        'provider_id' => $provider->id,
        'name' => 'Old Rate',
        'active_from' => now()->subDays(60),
        'active_until' => now()->subDays(31),
    ]);
    
    $response = $this->actingAs($user)
        ->getJson("/api/providers/{$provider->id}/tariffs");
    
    $response->assertStatus(200);
    $response->assertJsonCount(1);
    $response->assertJsonFragment(['name' => 'Standard Rate']);
    $response->assertJsonMissing(['name' => 'Old Rate']);
});

test('meter reading form component renders with Alpine.js', function () {
    $user = User::factory()->create([
        'tenant_id' => 1,
        'role' => UserRole::MANAGER,
    ]);
    
    $property = Property::factory()->create(['tenant_id' => 1]);
    $meter = Meter::factory()->create([
        'tenant_id' => 1,
        'property_id' => $property->id,
    ]);
    
    $provider = Provider::factory()->create();
    
    $response = $this->actingAs($user)
        ->get(route('meter-readings.create'));
    
    $response->assertStatus(200);
    $response->assertSee('x-data="meterReadingForm()"', false);
    $response->assertSee('Alpine.js', false);
});

test('tenants cannot access meter reading form', function () {
    $user = User::factory()->create([
        'tenant_id' => 1,
        'role' => UserRole::TENANT,
    ]);
    
    $response = $this->actingAs($user)
        ->get(route('meter-readings.create'));
    
    $response->assertStatus(403);
});
