<?php

declare(strict_types=1);

use App\Models\Building;
use App\ValueObjects\ConsumptionData;

describe('ConsumptionData Value Object', function () {
    test('creates from building correctly', function () {
        $building = Building::factory()->make([
            'total_apartments' => 20,
            'gyvatukas_summer_average' => 150.0,
        ]);

        $data = ConsumptionData::fromBuilding($building, 15.0);

        expect($data->totalApartments)->toBe(20);
        expect($data->baseCirculationRate)->toBe(15.0);
        expect($data->summerAverage)->toBe(150.0);
        expect($data->buildingEfficiencyFactor)->toBe(1.0); // Medium building
    });

    test('calculates efficiency factors correctly', function () {
        // Large building (>= 50 apartments)
        $largeBuilding = Building::factory()->make(['total_apartments' => 60]);
        $largeData = ConsumptionData::fromBuilding($largeBuilding, 15.0);
        expect($largeData->buildingEfficiencyFactor)->toBe(0.95);

        // Small building (< 10 apartments)
        $smallBuilding = Building::factory()->make(['total_apartments' => 5]);
        $smallData = ConsumptionData::fromBuilding($smallBuilding, 15.0);
        expect($smallData->buildingEfficiencyFactor)->toBe(1.1);

        // Medium building (10-49 apartments)
        $mediumBuilding = Building::factory()->make(['total_apartments' => 25]);
        $mediumData = ConsumptionData::fromBuilding($mediumBuilding, 15.0);
        expect($mediumData->buildingEfficiencyFactor)->toBe(1.0);
    });

    test('calculates base energy correctly', function () {
        $data = new ConsumptionData(
            totalApartments: 20,
            baseCirculationRate: 15.0
        );

        expect($data->calculateBaseEnergy())->toBe(300.0); // 20 * 15.0
    });

    test('calculates adjusted energy correctly', function () {
        $data = new ConsumptionData(
            totalApartments: 20,
            baseCirculationRate: 15.0,
            buildingEfficiencyFactor: 0.95
        );

        expect($data->calculateAdjustedEnergy())->toBe(285.0); // 300.0 * 0.95
    });

    test('validates apartment count', function () {
        expect(fn () => new ConsumptionData(
            totalApartments: 0,
            baseCirculationRate: 15.0
        ))->toThrow(InvalidArgumentException::class, 'Total apartments must be greater than 0');

        expect(fn () => new ConsumptionData(
            totalApartments: -5,
            baseCirculationRate: 15.0
        ))->toThrow(InvalidArgumentException::class, 'Total apartments must be greater than 0');
    });

    test('validates circulation rate', function () {
        expect(fn () => new ConsumptionData(
            totalApartments: 10,
            baseCirculationRate: -5.0
        ))->toThrow(InvalidArgumentException::class, 'Base circulation rate cannot be negative');
    });

    test('validates efficiency factor', function () {
        expect(fn () => new ConsumptionData(
            totalApartments: 10,
            baseCirculationRate: 15.0,
            buildingEfficiencyFactor: 0.0
        ))->toThrow(InvalidArgumentException::class, 'Building efficiency factor must be greater than 0');

        expect(fn () => new ConsumptionData(
            totalApartments: 10,
            baseCirculationRate: 15.0,
            buildingEfficiencyFactor: -0.5
        ))->toThrow(InvalidArgumentException::class, 'Building efficiency factor must be greater than 0');
    });
});