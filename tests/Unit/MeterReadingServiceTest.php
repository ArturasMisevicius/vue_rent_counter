<?php

use App\Models\Meter;
use App\Models\MeterReading;
use App\Services\MeterReadingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new MeterReadingService();
    $this->meter = Meter::factory()->create();
});

test('getPreviousReading returns most recent reading before date', function () {
    MeterReading::factory()->for($this->meter)->create([
        'reading_date' => Carbon::parse('2024-01-01'),
        'value' => 100,
        'zone' => null,
    ]);

    $reading2 = MeterReading::factory()->for($this->meter)->create([
        'reading_date' => Carbon::parse('2024-02-01'),
        'value' => 150,
        'zone' => null,
    ]);

    MeterReading::factory()->for($this->meter)->create([
        'reading_date' => Carbon::parse('2024-03-01'),
        'value' => 200,
        'zone' => null,
    ]);

    $previous = $this->service->getPreviousReading($this->meter, null, '2024-03-01');

    expect($previous->id)->toBe($reading2->id);
});

test('getPreviousReading filters by zone', function () {
    MeterReading::factory()->for($this->meter)->create([
        'reading_date' => Carbon::parse('2024-01-01'),
        'value' => 100,
        'zone' => 'day',
    ]);

    $nightReading = MeterReading::factory()->for($this->meter)->create([
        'reading_date' => Carbon::parse('2024-02-01'),
        'value' => 50,
        'zone' => 'night',
    ]);

    $previous = $this->service->getPreviousReading($this->meter, 'night');

    expect($previous->id)->toBe($nightReading->id);
});

test('getNextReading returns earliest reading after date', function () {
    $reading1 = MeterReading::factory()->for($this->meter)->create([
        'reading_date' => Carbon::parse('2024-01-01'),
        'value' => 100,
        'zone' => null,
    ]);

    $reading2 = MeterReading::factory()->for($this->meter)->create([
        'reading_date' => Carbon::parse('2024-02-01'),
        'value' => 150,
        'zone' => null,
    ]);

    MeterReading::factory()->for($this->meter)->create([
        'reading_date' => Carbon::parse('2024-03-01'),
        'value' => 200,
        'zone' => null,
    ]);

    $next = $this->service->getNextReading($this->meter, null, $reading1->reading_date->toDateString());

    expect($next->id)->toBe($reading2->id);
});

test('getAdjacentReading returns previous reading', function () {
    MeterReading::factory()->for($this->meter)->create([
        'reading_date' => Carbon::parse('2024-01-01'),
        'value' => 100,
        'zone' => null,
    ]);

    $current = MeterReading::factory()->for($this->meter)->create([
        'reading_date' => Carbon::parse('2024-02-01'),
        'value' => 150,
        'zone' => null,
    ]);

    MeterReading::factory()->for($this->meter)->create([
        'reading_date' => Carbon::parse('2024-03-01'),
        'value' => 200,
        'zone' => null,
    ]);

    $previous = $this->service->getAdjacentReading($current, null, 'previous');

    expect($previous->value)->toBe('100.00');
});

test('getAdjacentReading returns next reading', function () {
    MeterReading::factory()->for($this->meter)->create([
        'reading_date' => Carbon::parse('2024-01-01'),
        'value' => 100,
        'zone' => null,
    ]);

    $current = MeterReading::factory()->for($this->meter)->create([
        'reading_date' => Carbon::parse('2024-02-01'),
        'value' => 150,
        'zone' => null,
    ]);

    MeterReading::factory()->for($this->meter)->create([
        'reading_date' => Carbon::parse('2024-03-01'),
        'value' => 200,
        'zone' => null,
    ]);

    $next = $this->service->getAdjacentReading($current, null, 'next');

    expect($next->value)->toBe('200.00');
});
