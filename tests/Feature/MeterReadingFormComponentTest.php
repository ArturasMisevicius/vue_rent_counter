<?php

declare(strict_types=1);

use App\Livewire\Manager\MeterReadingForm;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\Provider;
use App\Models\Tariff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('manager create page renders livewire meter reading form', function () {
    $manager = User::factory()->manager()->create();
    $property = Property::factory()->create(['tenant_id' => $manager->tenant_id]);

    Meter::factory()->create([
        'property_id' => $property->id,
        'tenant_id' => $manager->tenant_id,
    ]);

    $response = $this->actingAs($manager)
        ->get(route('manager.meter-readings.create'));

    $response->assertSuccessful();
    $response->assertSeeLivewire(MeterReadingForm::class);
});

test('livewire meter reading form rejects non monotonic value', function () {
    $manager = User::factory()->manager()->create();
    $property = Property::factory()->create(['tenant_id' => $manager->tenant_id]);
    $meter = Meter::factory()->create([
        'property_id' => $property->id,
        'tenant_id' => $manager->tenant_id,
        'supports_zones' => false,
    ]);

    MeterReading::factory()->create([
        'meter_id' => $meter->id,
        'tenant_id' => $manager->tenant_id,
        'value' => 1000.00,
        'reading_date' => now()->subMonth(),
        'entered_by' => $manager->id,
    ]);

    $this->actingAs($manager);

    Livewire::test(MeterReadingForm::class)
        ->set('formData.meter_id', (string) $meter->id)
        ->set('formData.reading_date', now()->toDateString())
        ->set('formData.value', '900')
        ->call('submit')
        ->assertHasErrors(['formData.value']);
});

test('livewire meter reading form stores valid single zone reading', function () {
    $manager = User::factory()->manager()->create();
    $property = Property::factory()->create(['tenant_id' => $manager->tenant_id]);
    $meter = Meter::factory()->create([
        'property_id' => $property->id,
        'tenant_id' => $manager->tenant_id,
        'supports_zones' => false,
    ]);

    MeterReading::factory()->create([
        'meter_id' => $meter->id,
        'tenant_id' => $manager->tenant_id,
        'value' => 1000.00,
        'reading_date' => now()->subMonth(),
        'entered_by' => $manager->id,
    ]);

    $this->actingAs($manager);

    Livewire::test(MeterReadingForm::class)
        ->set('formData.meter_id', (string) $meter->id)
        ->set('formData.reading_date', now()->toDateString())
        ->set('formData.value', '1100')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(route('manager.meter-readings.index'));

    $this->assertDatabaseHas('meter_readings', [
        'meter_id' => $meter->id,
        'tenant_id' => $manager->tenant_id,
        'entered_by' => $manager->id,
        'zone' => null,
    ]);
});

test('livewire meter reading form stores day and night readings for zone meters', function () {
    $manager = User::factory()->manager()->create();
    $property = Property::factory()->create(['tenant_id' => $manager->tenant_id]);
    $meter = Meter::factory()->create([
        'property_id' => $property->id,
        'tenant_id' => $manager->tenant_id,
        'supports_zones' => true,
    ]);

    $this->actingAs($manager);

    Livewire::test(MeterReadingForm::class)
        ->set('formData.meter_id', (string) $meter->id)
        ->set('formData.reading_date', now()->toDateString())
        ->set('formData.day_value', '1200')
        ->set('formData.night_value', '800')
        ->call('submit')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('meter_readings', [
        'meter_id' => $meter->id,
        'tenant_id' => $manager->tenant_id,
        'zone' => 'day',
        'entered_by' => $manager->id,
    ]);

    $this->assertDatabaseHas('meter_readings', [
        'meter_id' => $meter->id,
        'tenant_id' => $manager->tenant_id,
        'zone' => 'night',
        'entered_by' => $manager->id,
    ]);
});

test('livewire meter reading form loads only active provider tariffs', function () {
    $manager = User::factory()->manager()->create();
    $property = Property::factory()->create(['tenant_id' => $manager->tenant_id]);

    Meter::factory()->create([
        'property_id' => $property->id,
        'tenant_id' => $manager->tenant_id,
    ]);

    $provider = Provider::factory()->create();

    Tariff::factory()->create([
        'provider_id' => $provider->id,
        'name' => 'Active Tariff',
        'active_from' => now()->subWeek(),
        'active_until' => null,
    ]);

    Tariff::factory()->create([
        'provider_id' => $provider->id,
        'name' => 'Expired Tariff',
        'active_from' => now()->subMonths(2),
        'active_until' => now()->subMonth(),
    ]);

    $this->actingAs($manager);

    $component = Livewire::test(MeterReadingForm::class)
        ->set('formData.provider_id', (string) $provider->id);

    $tariffs = $component->get('availableTariffs');

    expect($tariffs)->toHaveCount(1);
    expect($tariffs[0]['name'])->toBe('Active Tariff');
});
