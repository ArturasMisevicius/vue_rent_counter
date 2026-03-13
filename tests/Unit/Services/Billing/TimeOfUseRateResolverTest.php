<?php

declare(strict_types=1);

use App\Services\Billing\TimeOfUseRateResolver;
use Carbon\Carbon;

it('resolves time window rates for the billing context', function () {
    $resolver = app(TimeOfUseRateResolver::class);

    $result = $resolver->resolve([
        'time_windows' => [
            [
                'zone' => 'day',
                'start' => '07:00',
                'end' => '23:00',
                'rate' => 0.24,
                'day_types' => ['weekday'],
                'months' => [1, 2, 3],
            ],
            [
                'zone' => 'night',
                'start' => '23:00',
                'end' => '07:00',
                'rate' => 0.12,
                'day_types' => ['weekday'],
                'months' => [1, 2, 3],
            ],
        ],
        'default_rate' => 0.20,
    ], Carbon::parse('2026-02-16 10:00:00'));

    expect($result['source'])->toBe('time_windows')
        ->and($result['zone_rates']['day'])->toBe(0.24)
        ->and($result['zone_rates']['night'])->toBe(0.12)
        ->and($result['zone_rates']['default'])->toBe(0.20)
        ->and($result['context']['day_type'])->toBe('weekday')
        ->and($result['context']['month'])->toBe(2);
});

it('rejects overlapping time windows', function () {
    $resolver = app(TimeOfUseRateResolver::class);

    $resolver->resolve([
        'time_windows' => [
            [
                'zone' => 'peak',
                'start' => '08:00',
                'end' => '12:00',
                'rate' => 0.28,
                'day_types' => ['weekday'],
            ],
            [
                'zone' => 'day',
                'start' => '11:00',
                'end' => '16:00',
                'rate' => 0.20,
                'day_types' => ['weekday'],
            ],
        ],
    ], Carbon::parse('2026-02-16 10:00:00'));
})->throws(InvalidArgumentException::class);

it('rejects ambiguous rates for the same zone in one context', function () {
    $resolver = app(TimeOfUseRateResolver::class);

    $resolver->resolve([
        'time_windows' => [
            [
                'zone' => 'day',
                'start' => '07:00',
                'end' => '12:00',
                'rate' => 0.20,
                'day_types' => ['weekday'],
            ],
            [
                'zone' => 'day',
                'start' => '12:00',
                'end' => '23:00',
                'rate' => 0.22,
                'day_types' => ['weekday'],
            ],
        ],
    ], Carbon::parse('2026-02-16 10:00:00'));
})->throws(InvalidArgumentException::class);

it('supports legacy time slots and default fallback', function () {
    $resolver = app(TimeOfUseRateResolver::class);

    $result = $resolver->resolve([
        'time_slots' => [
            [
                'zone' => 'day',
                'day_type' => 'weekday',
                'start_hour' => 7,
                'end_hour' => 23,
                'rate' => 0.19,
            ],
            [
                'zone' => 'night',
                'day_type' => 'weekday',
                'start_hour' => 23,
                'end_hour' => 7,
                'rate' => 0.11,
            ],
        ],
        'default_rate' => 0.17,
    ], Carbon::parse('2026-02-16 12:00:00'));

    expect($result['source'])->toBe('time_slots')
        ->and($result['zone_rates']['day'])->toBe(0.19)
        ->and($result['zone_rates']['night'])->toBe(0.11)
        ->and($result['zone_rates']['default'])->toBe(0.17);
});
