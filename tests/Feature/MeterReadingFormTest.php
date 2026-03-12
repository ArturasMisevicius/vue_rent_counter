<?php

declare(strict_types=1);

use App\Livewire\Manager\MeterReadingForm;
use App\Models\Meter;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('manager meter reading create page does not reference api endpoints', function () {
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
    $response->assertDontSee('/api/', false);
});

test('api endpoints are no longer exposed', function () {
    $this->getJson('/api/meters/1/last-reading')->assertNotFound();
    $this->getJson('/api/providers/1/tariffs')->assertNotFound();
    $this->postJson('/api/meter-readings', [])->assertNotFound();
});

test('tenant cannot access manager meter reading create page', function () {
    $tenant = User::factory()->tenant()->create();

    $response = $this->actingAs($tenant)
        ->get(route('manager.meter-readings.create'));

    $response->assertForbidden();
});
