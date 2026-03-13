<?php

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\Tariff;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Tariff Model Tests
test('Tariff isActiveOn returns true for active tariff', function () {
    $tariff = Tariff::factory()->make([
        'active_from' => Carbon::parse('2024-01-01'),
        'active_until' => null,
    ]);

    expect($tariff->isActiveOn(Carbon::parse('2024-06-15')))->toBeTrue();
});

test('Tariff isActiveOn returns false for expired tariff', function () {
    $tariff = Tariff::factory()->make([
        'active_from' => Carbon::parse('2024-01-01'),
        'active_until' => Carbon::parse('2024-05-31'),
    ]);

    expect($tariff->isActiveOn(Carbon::parse('2024-06-15')))->toBeFalse();
});

test('Tariff isFlatRate identifies flat tariffs', function () {
    $tariff = Tariff::factory()->flat()->make();

    expect($tariff->isFlatRate())->toBeTrue();
    expect($tariff->isTimeOfUse())->toBeFalse();
});

test('Tariff isTimeOfUse identifies time-of-use tariffs', function () {
    $tariff = Tariff::factory()->timeOfUse()->make();

    expect($tariff->isTimeOfUse())->toBeTrue();
    expect($tariff->isFlatRate())->toBeFalse();
});

test('Tariff getFlatRate returns rate for flat tariffs', function () {
    $tariff = Tariff::factory()->flat()->make([
        'configuration' => [
            'type' => 'flat',
            'rate' => 0.15,
        ],
    ]);

    expect($tariff->getFlatRate())->toBe(0.15);
});

test('Tariff getFlatRate returns null for time-of-use tariffs', function () {
    $tariff = Tariff::factory()->timeOfUse()->make();

    expect($tariff->getFlatRate())->toBeNull();
});

// Invoice Model Tests
test('Invoice finalize sets status and timestamp', function () {
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::DRAFT]);

    $invoice->finalize();

    expect($invoice->status)->toBe(InvoiceStatus::FINALIZED);
    expect($invoice->finalized_at)->not->toBeNull();
});

test('Invoice isFinalized returns correct status', function () {
    $draft = Invoice::factory()->create(['status' => InvoiceStatus::DRAFT]);
    $finalized = Invoice::factory()->create(['status' => InvoiceStatus::FINALIZED]);

    expect($draft->isFinalized())->toBeFalse();
    expect($finalized->isFinalized())->toBeTrue();
});

test('Invoice isDraft returns correct status', function () {
    $draft = Invoice::factory()->create(['status' => InvoiceStatus::DRAFT]);
    $finalized = Invoice::factory()->create(['status' => InvoiceStatus::FINALIZED]);

    expect($draft->isDraft())->toBeTrue();
    expect($finalized->isDraft())->toBeFalse();
});

test('Invoice isPaid returns correct status', function () {
    $draft = Invoice::factory()->create(['status' => InvoiceStatus::DRAFT]);
    $paid = Invoice::factory()->create(['status' => InvoiceStatus::PAID]);

    expect($draft->isPaid())->toBeFalse();
    expect($paid->isPaid())->toBeTrue();
});

// MeterReading Model Tests
test('MeterReading getConsumption calculates difference from previous reading', function () {
    $meter = Meter::factory()->create();

    MeterReading::factory()->for($meter)->create([
        'reading_date' => Carbon::parse('2024-01-01'),
        'value' => 100,
        'zone' => null,
    ]);

    $current = MeterReading::factory()->for($meter)->create([
        'reading_date' => Carbon::parse('2024-02-01'),
        'value' => 150,
        'zone' => null,
    ]);

    expect($current->getConsumption())->toBe(50.0);
});

test('MeterReading getConsumption returns null when no previous reading', function () {
    $meter = Meter::factory()->create();

    $reading = MeterReading::factory()->for($meter)->create([
        'reading_date' => Carbon::parse('2024-01-01'),
        'value' => 100,
        'zone' => null,
    ]);

    expect($reading->getConsumption())->toBeNull();
});

test('MeterReading getConsumption respects zones', function () {
    $meter = Meter::factory()->create(['supports_zones' => true]);

    MeterReading::factory()->for($meter)->create([
        'reading_date' => Carbon::parse('2024-01-01'),
        'value' => 100,
        'zone' => 'day',
    ]);

    MeterReading::factory()->for($meter)->create([
        'reading_date' => Carbon::parse('2024-01-01'),
        'value' => 50,
        'zone' => 'night',
    ]);

    $dayReading = MeterReading::factory()->for($meter)->create([
        'reading_date' => Carbon::parse('2024-02-01'),
        'value' => 150,
        'zone' => 'day',
    ]);

    expect($dayReading->getConsumption())->toBe(50.0);
});

// Property Model Tests
test('Property has correct relationships', function () {
    $property = Property::factory()->create();

    expect($property->building)->not->toBeNull();
    expect($property->tenants())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class);
    expect($property->meters())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
});
