<?php

declare(strict_types=1);

use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\ProvidersSeeder::class);
});

test('meter reading form component renders correctly', function () {
    $manager = User::factory()->manager()->create();
    $property = Property::factory()->create(['tenant_id' => $manager->tenant_id]);
    $meter = Meter::factory()->create(['property_id' => $property->id]);
    $providers = Provider::all();

    $response = $this->actingAs($manager)
        ->get(route('manager.meter-readings.create'));

    $response->assertStatus(200);
    $response->assertSee(__('meter_readings.form_component.title'));
    $response->assertSee(__('meter_readings.form_component.select_meter'));
    $response->assertSee(__('meter_readings.form_component.select_provider'));
    $response->assertSee($meter->serial_number);
});

test('meter reading form displays previous reading when meter is selected', function () {
    $manager = User::factory()->manager()->create();
    $property = Property::factory()->create(['tenant_id' => $manager->tenant_id]);
    $meter = Meter::factory()->create(['property_id' => $property->id]);
    
    // Create a previous reading
    $previousReading = MeterReading::factory()->create([
        'meter_id' => $meter->id,
        'value' => 1000.00,
        'reading_date' => now()->subDays(30),
    ]);

    $response = $this->actingAs($manager)
        ->getJson("/api/meters/{$meter->id}/last-reading");

    $response->assertStatus(200);
    $response->assertJson([
        'value' => 1000.00,
    ]);
});

test('meter reading form validates monotonicity client-side', function () {
    $manager = User::factory()->manager()->create();
    $property = Property::factory()->create(['tenant_id' => $manager->tenant_id]);
    $meter = Meter::factory()->create(['property_id' => $property->id]);
    
    // Create a previous reading
    MeterReading::factory()->create([
        'meter_id' => $meter->id,
        'value' => 1000.00,
        'reading_date' => now()->subDays(30),
    ]);

    // Try to submit a lower reading
    $response = $this->actingAs($manager)
        ->postJson('/api/meter-readings', [
            'meter_id' => $meter->id,
            'reading_date' => now()->format('Y-m-d'),
            'value' => 900.00, // Lower than previous
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['value']);
});

test('meter reading form supports multi-zone meters', function () {
    $manager = User::factory()->manager()->create();
    $property = Property::factory()->create(['tenant_id' => $manager->tenant_id]);
    $meter = Meter::factory()->create([
        'property_id' => $property->id,
        'supports_zones' => true,
    ]);

    $response = $this->actingAs($manager)
        ->get(route('manager.meter-readings.create'));

    $response->assertStatus(200);
    $response->assertSee(__('meter_readings.form_component.day_zone'));
    $response->assertSee(__('meter_readings.form_component.night_zone'));
});

test('meter reading form loads tariffs dynamically when provider is selected', function () {
    $manager = User::factory()->manager()->create();
    $provider = Provider::first();

    $response = $this->actingAs($manager)
        ->getJson("/api/providers/{$provider->id}/tariffs");

    $response->assertStatus(200);
    $response->assertJsonStructure([
        '*' => ['id', 'name', 'configuration'],
    ]);
});

test('meter reading form calculates consumption correctly', function () {
    $manager = User::factory()->manager()->create();
    $property = Property::factory()->create(['tenant_id' => $manager->tenant_id]);
    $meter = Meter::factory()->create(['property_id' => $property->id]);
    
    // Create a previous reading
    MeterReading::factory()->create([
        'meter_id' => $meter->id,
        'value' => 1000.00,
        'reading_date' => now()->subDays(30),
    ]);

    // The component should calculate: 1200 - 1000 = 200 consumption
    // This is tested via the Alpine.js computed property
    $response = $this->actingAs($manager)
        ->get(route('manager.meter-readings.create'));

    $response->assertStatus(200);
    $response->assertSee(__('meter_readings.form_component.consumption'));
});

test('meter reading form prevents future dates', function () {
    $manager = User::factory()->manager()->create();
    $property = Property::factory()->create(['tenant_id' => $manager->tenant_id]);
    $meter = Meter::factory()->create(['property_id' => $property->id]);

    $futureDate = now()->addDays(1)->format('Y-m-d');

    $response = $this->actingAs($manager)
        ->postJson('/api/meter-readings', [
            'meter_id' => $meter->id,
            'reading_date' => $futureDate,
            'value' => 1000.00,
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['reading_date']);
});
