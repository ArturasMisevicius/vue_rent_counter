<?php

declare(strict_types=1);

use App\Enums\PricingModel;
use App\Models\ServiceConfiguration;
use Carbon\Carbon;

test('ServiceConfiguration resolves legacy time slots that cross midnight', function () {
    $configuration = new ServiceConfiguration;
    $configuration->pricing_model = PricingModel::TIME_OF_USE;
    $configuration->rate_schedule = [
        'time_slots' => [
            [
                'zone' => 'night',
                'day_type' => 'all',
                'start_hour' => 22,
                'end_hour' => 6,
                'rate' => 0.11,
            ],
            [
                'zone' => 'day',
                'day_type' => 'all',
                'start_hour' => 6,
                'end_hour' => 22,
                'rate' => 0.20,
            ],
        ],
        'default_rate' => 0.18,
    ];

    expect($configuration->getEffectiveRate(Carbon::parse('2026-02-16 23:15:00'), 'night'))->toBe(0.11)
        ->and($configuration->getEffectiveRate(Carbon::parse('2026-02-16 03:15:00'), 'night'))->toBe(0.11)
        ->and($configuration->getEffectiveRate(Carbon::parse('2026-02-16 14:00:00'), 'day'))->toBe(0.20);
});

test('ServiceConfiguration resolves time windows by day type and month', function () {
    $configuration = new ServiceConfiguration;
    $configuration->pricing_model = PricingModel::TIME_OF_USE;
    $configuration->rate_schedule = [
        'time_windows' => [
            [
                'zone' => 'day',
                'start' => '07:00',
                'end' => '23:00',
                'rate' => 0.22,
                'day_types' => ['weekday'],
                'months' => [2],
            ],
            [
                'zone' => 'day',
                'start' => '07:00',
                'end' => '23:00',
                'rate' => 0.30,
                'day_types' => ['weekend'],
                'months' => [2],
            ],
        ],
        'default_rate' => 0.15,
    ];

    expect($configuration->getEffectiveRate(Carbon::parse('2026-02-16 10:00:00'), 'day'))->toBe(0.22)
        ->and($configuration->getEffectiveRate(Carbon::parse('2026-02-15 10:00:00'), 'day'))->toBe(0.30)
        ->and($configuration->getEffectiveRate(Carbon::parse('2026-03-15 10:00:00'), 'day'))->toBe(0.15);
});
