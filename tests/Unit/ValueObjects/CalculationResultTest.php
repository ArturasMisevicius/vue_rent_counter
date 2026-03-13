<?php

declare(strict_types=1);

use App\ValueObjects\CalculationResult;
use Carbon\Carbon;

describe('CalculationResult Value Object', function () {
    test('creates result with proper data', function () {
        $result = CalculationResult::create(
            energy: 150.5,
            calculationType: 'summer',
            buildingId: 1,
            cacheKey: 'test-key',
            metadata: ['test' => 'value']
        );

        expect($result->energy)->toBe(150.5);
        expect($result->calculationType)->toBe('summer');
        expect($result->buildingId)->toBe(1);
        expect($result->cacheKey)->toBe('test-key');
        expect($result->metadata)->toBe(['test' => 'value']);
        expect($result->calculatedAt)->toBeInstanceOf(Carbon::class);
    });

    test('rounds energy to 2 decimal places', function () {
        $result = CalculationResult::create(
            energy: 150.555,
            calculationType: 'summer',
            buildingId: 1
        );

        expect($result->energy)->toBe(150.56);
    });

    test('ensures minimum energy of zero', function () {
        $result = CalculationResult::create(
            energy: -50.0,
            calculationType: 'summer',
            buildingId: 1
        );

        expect($result->energy)->toBe(0.0);
    });

    test('converts to array correctly', function () {
        $result = CalculationResult::create(
            energy: 150.0,
            calculationType: 'summer',
            buildingId: 1,
            metadata: ['apartments' => 10]
        );

        $array = $result->toArray();

        expect($array)->toHaveKeys([
            'energy',
            'calculated_at',
            'calculation_type',
            'building_id',
            'cache_key',
            'metadata'
        ]);
        expect($array['energy'])->toBe(150.0);
        expect($array['calculation_type'])->toBe('summer');
        expect($array['building_id'])->toBe(1);
        expect($array['metadata'])->toBe(['apartments' => 10]);
    });

    test('checks if energy is zero', function () {
        $zeroResult = CalculationResult::create(0.0, 'summer', 1);
        $nonZeroResult = CalculationResult::create(150.0, 'summer', 1);

        expect($zeroResult->isZero())->toBeTrue();
        expect($nonZeroResult->isZero())->toBeFalse();
    });

    test('handles metadata operations', function () {
        $result = CalculationResult::create(
            energy: 150.0,
            calculationType: 'summer',
            buildingId: 1,
            metadata: ['apartments' => 10, 'efficiency' => 0.95]
        );

        expect($result->hasMetadata('apartments'))->toBeTrue();
        expect($result->hasMetadata('nonexistent'))->toBeFalse();
        expect($result->getMetadata('apartments'))->toBe(10);
        expect($result->getMetadata('nonexistent', 'default'))->toBe('default');
    });
});