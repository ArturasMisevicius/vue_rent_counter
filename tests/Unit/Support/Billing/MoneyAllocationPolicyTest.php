<?php

declare(strict_types=1);

use App\Services\Billing\UniversalBillingCalculator;

it('allocates rounded money shares without losing the remainder cent', function (): void {
    $calculator = app(UniversalBillingCalculator::class);

    expect($calculator->allocate('100.00', ['1', '1', '1']))
        ->toBe([
            '33.34',
            '33.33',
            '33.33',
        ])
        ->and(array_sum(array_map(static fn (string $amount): float => (float) $amount, $calculator->allocate('100.00', ['1', '1', '1']))))
        ->toBe(100.0);
});

it('allocates proportional shares with a deterministic largest-remainder policy', function (): void {
    $calculator = app(UniversalBillingCalculator::class);

    expect($calculator->allocate('10.00', ['1', '2', '3']))
        ->toBe([
            '1.67',
            '3.33',
            '5.00',
        ])
        ->and(array_sum(array_map(static fn (string $amount): float => (float) $amount, $calculator->allocate('10.00', ['1', '2', '3']))))
        ->toBe(10.0);
});

it('returns zero allocations when no positive weight exists', function (): void {
    $calculator = app(UniversalBillingCalculator::class);

    expect($calculator->allocate('10.00', ['0', '0']))
        ->toBe([
            '0.00',
            '0.00',
        ]);
});
