<?php

use App\Enums\InvoiceStatus;
use App\Enums\MeterType;
use App\Enums\PropertyType;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\Tariff;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// MeterReading Scopes
test('MeterReading forPeriod scope filters by date range', function () {
    $meter = Meter::factory()->create();
    
    MeterReading::factory()->for($meter)->create(['reading_date' => '2024-01-15']);
    MeterReading::factory()->for($meter)->create(['reading_date' => '2024-02-15']);
    MeterReading::factory()->for($meter)->create(['reading_date' => '2024-03-15']);
    
    $readings = MeterReading::forPeriod('2024-02-01', '2024-02-28')->get();
    
    expect($readings)->toHaveCount(1);
    expect($readings->first()->reading_date->format('Y-m-d'))->toBe('2024-02-15');
});

test('MeterReading forZone scope filters by zone', function () {
    $meter = Meter::factory()->create(['supports_zones' => true]);
    
    MeterReading::factory()->for($meter)->create(['zone' => 'day']);
    MeterReading::factory()->for($meter)->create(['zone' => 'night']);
    MeterReading::factory()->for($meter)->create(['zone' => null]);
    
    $dayReadings = MeterReading::forZone('day')->get();
    $nullReadings = MeterReading::forZone(null)->get();
    
    expect($dayReadings)->toHaveCount(1);
    expect($nullReadings)->toHaveCount(1);
});

// Tariff Scopes
test('Tariff active scope filters by date', function () {
    Tariff::factory()->create([
        'active_from' => Carbon::parse('2024-01-01'),
        'active_until' => Carbon::parse('2024-06-30'),
    ]);
    
    Tariff::factory()->create([
        'active_from' => Carbon::parse('2024-07-01'),
        'active_until' => null,
    ]);
    
    $activeTariffs = Tariff::active(Carbon::parse('2024-03-15'))->get();
    
    expect($activeTariffs)->toHaveCount(1);
});

test('Tariff flatRate scope filters flat tariffs', function () {
    Tariff::factory()->flat()->create();
    Tariff::factory()->timeOfUse()->create();
    
    $flatTariffs = Tariff::flatRate()->get();
    
    expect($flatTariffs)->toHaveCount(1);
});

test('Tariff timeOfUse scope filters time-of-use tariffs', function () {
    Tariff::factory()->flat()->create();
    Tariff::factory()->timeOfUse()->create();
    
    $touTariffs = Tariff::timeOfUse()->get();
    
    expect($touTariffs)->toHaveCount(1);
});

// Invoice Scopes
test('Invoice draft scope filters draft invoices', function () {
    Invoice::factory()->create(['status' => InvoiceStatus::DRAFT]);
    Invoice::factory()->create(['status' => InvoiceStatus::FINALIZED]);
    Invoice::factory()->create(['status' => InvoiceStatus::PAID]);
    
    $drafts = Invoice::draft()->get();
    
    expect($drafts)->toHaveCount(1);
});

test('Invoice finalized scope filters finalized invoices', function () {
    Invoice::factory()->create(['status' => InvoiceStatus::DRAFT]);
    Invoice::factory()->create(['status' => InvoiceStatus::FINALIZED]);
    
    $finalized = Invoice::finalized()->get();
    
    expect($finalized)->toHaveCount(1);
});

test('Invoice forPeriod scope filters by billing period', function () {
    Invoice::factory()->create([
        'billing_period_start' => '2024-01-01',
        'billing_period_end' => '2024-01-31',
    ]);
    
    Invoice::factory()->create([
        'billing_period_start' => '2024-02-01',
        'billing_period_end' => '2024-02-29',
    ]);
    
    $invoices = Invoice::forPeriod('2024-01-01', '2024-01-31')->get();
    
    expect($invoices)->toHaveCount(1);
});

// Property Scopes
test('Property apartments scope filters apartments', function () {
    Property::factory()->create(['type' => PropertyType::APARTMENT]);
    Property::factory()->create(['type' => PropertyType::HOUSE]);
    
    $apartments = Property::apartments()->get();
    
    expect($apartments)->toHaveCount(1);
});

test('Property houses scope filters houses', function () {
    Property::factory()->create(['type' => PropertyType::APARTMENT]);
    Property::factory()->create(['type' => PropertyType::HOUSE]);
    
    $houses = Property::houses()->get();
    
    expect($houses)->toHaveCount(1);
});

// Meter Scopes
test('Meter ofType scope filters by meter type', function () {
    Meter::factory()->create(['type' => MeterType::ELECTRICITY]);
    Meter::factory()->create(['type' => MeterType::WATER_COLD]);
    
    $electricityMeters = Meter::ofType(MeterType::ELECTRICITY)->get();
    
    expect($electricityMeters)->toHaveCount(1);
});

test('Meter supportsZones scope filters zone-capable meters', function () {
    Meter::factory()->create(['supports_zones' => true]);
    Meter::factory()->create(['supports_zones' => false]);
    
    $zoneMeters = Meter::supportsZones()->get();
    
    expect($zoneMeters)->toHaveCount(1);
});

test('Meter withLatestReading scope eager loads latest reading', function () {
    $meter = Meter::factory()->create();
    
    MeterReading::factory()->for($meter)->create(['reading_date' => '2024-01-01', 'value' => 100]);
    MeterReading::factory()->for($meter)->create(['reading_date' => '2024-02-01', 'value' => 150]);
    
    $meterWithReading = Meter::withLatestReading()->first();
    
    expect($meterWithReading->readings)->toHaveCount(1);
    expect($meterWithReading->readings->first()->value)->toBe('150.00');
});
