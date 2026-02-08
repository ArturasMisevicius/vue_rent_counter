<?php

use App\Models\Tariff;
use App\Services\TariffCalculation\FlatRateStrategy;
use App\Services\TariffCalculation\TimeOfUseStrategy;
use Carbon\Carbon;

test('FlatRateStrategy supports flat tariff type', function () {
    $strategy = new FlatRateStrategy();
    
    expect($strategy->supports('flat'))->toBeTrue();
    expect($strategy->supports('time_of_use'))->toBeFalse();
});

test('FlatRateStrategy calculates cost correctly', function () {
    $strategy = new FlatRateStrategy();
    $tariff = new Tariff([
        'configuration' => [
            'type' => 'flat',
            'rate' => 0.15,
        ],
    ]);

    $cost = $strategy->calculate($tariff, 100.0, now());

    expect($cost)->toBe(15.0);
});

test('TimeOfUseStrategy supports time_of_use tariff type', function () {
    $strategy = new TimeOfUseStrategy();
    
    expect($strategy->supports('time_of_use'))->toBeTrue();
    expect($strategy->supports('flat'))->toBeFalse();
});

test('TimeOfUseStrategy calculates day rate correctly', function () {
    $strategy = new TimeOfUseStrategy();
    $tariff = new Tariff([
        'configuration' => [
            'type' => 'time_of_use',
            'currency' => 'EUR',
            'zones' => [
                ['id' => 'day', 'start' => '07:00', 'end' => '23:00', 'rate' => 0.18],
                ['id' => 'night', 'start' => '23:00', 'end' => '07:00', 'rate' => 0.10],
            ],
        ],
    ]);
    $timestamp = Carbon::parse('2024-06-17 14:00:00'); // Monday afternoon

    $cost = $strategy->calculate($tariff, 100.0, $timestamp);

    expect($cost)->toBe(18.0); // 100 * 0.18 (day rate)
});

test('TimeOfUseStrategy calculates night rate correctly', function () {
    $strategy = new TimeOfUseStrategy();
    $tariff = new Tariff([
        'configuration' => [
            'type' => 'time_of_use',
            'currency' => 'EUR',
            'zones' => [
                ['id' => 'day', 'start' => '07:00', 'end' => '23:00', 'rate' => 0.18],
                ['id' => 'night', 'start' => '23:00', 'end' => '07:00', 'rate' => 0.10],
            ],
        ],
    ]);
    $timestamp = Carbon::parse('2024-06-17 02:00:00'); // Monday night

    $cost = $strategy->calculate($tariff, 100.0, $timestamp);

    expect($cost)->toBe(10.0); // 100 * 0.10 (night rate)
});

test('TimeOfUseStrategy applies weekend logic', function () {
    $strategy = new TimeOfUseStrategy();
    $tariff = new Tariff([
        'configuration' => [
            'type' => 'time_of_use',
            'currency' => 'EUR',
            'zones' => [
                ['id' => 'day', 'start' => '07:00', 'end' => '23:00', 'rate' => 0.18],
                ['id' => 'night', 'start' => '23:00', 'end' => '07:00', 'rate' => 0.10],
            ],
            'weekend_logic' => 'apply_night_rate',
        ],
    ]);
    $timestamp = Carbon::parse('2024-06-15 14:00:00'); // Saturday afternoon

    $cost = $strategy->calculate($tariff, 100.0, $timestamp);

    expect($cost)->toBe(10.0); // 100 * 0.10 (night rate on weekend)
});

test('TimeOfUseStrategy handles midnight crossing ranges', function () {
    $strategy = new TimeOfUseStrategy();
    $tariff = new Tariff([
        'configuration' => [
            'type' => 'time_of_use',
            'currency' => 'EUR',
            'zones' => [
                ['id' => 'day', 'start' => '07:00', 'end' => '23:00', 'rate' => 0.18],
                ['id' => 'night', 'start' => '23:00', 'end' => '07:00', 'rate' => 0.10],
            ],
        ],
    ]);
    $timestamp = Carbon::parse('2024-06-17 23:30:00'); // Monday 23:30

    $cost = $strategy->calculate($tariff, 100.0, $timestamp);

    expect($cost)->toBe(10.0); // 100 * 0.10 (night rate)
});
