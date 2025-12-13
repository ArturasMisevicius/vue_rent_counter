<?php

declare(strict_types=1);

use App\Enums\DistributionMethod;
use App\Enums\GyvatukasCalculationType;
use App\Models\Building;
use App\Services\BillingCalculation\CirculationCostDistributor;
use App\Services\BillingCalculation\GyvatukasCacheManager;
use App\Services\BillingCalculation\StandardWinterAdjustmentStrategy;
use App\Services\GyvatukasCalculatorService;
use App\ValueObjects\CalculationResult;
use Carbon\Carbon;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Psr\Log\LoggerInterface;

beforeEach(function () {
    $this->cacheManager = Mockery::mock(GyvatukasCacheManager::class);
    $this->distributor = Mockery::mock(CirculationCostDistributor::class);
    $this->winterStrategy = Mockery::mock(StandardWinterAdjustmentStrategy::class);
    $this->config = Mockery::mock(ConfigRepository::class);
    $this->logger = Mockery::mock(LoggerInterface::class);

    $this->calculator = new GyvatukasCalculatorService(
        $this->cacheManager,
        $this->distributor,
        $this->winterStrategy,
        $this->config,
        $this->logger
    );

    // Default config setup
    $this->config->shouldReceive('get')
        ->with('gyvatukas.summer_months', [5, 6, 7, 8, 9])
        ->andReturn([5, 6, 7, 8, 9])
        ->byDefault();

    $this->config->shouldReceive('get')
        ->with('gyvatukas.default_circulation_rate', 15.0)
        ->andReturn(15.0)
        ->byDefault();

    $this->config->shouldReceive('get')
        ->with('gyvatukas.validation.max_apartments', 1000)
        ->andReturn(1000)
        ->byDefault();

    $this->config->shouldReceive('get')
        ->with('gyvatukas.summer_average_validity_months', 12)
        ->andReturn(12)
        ->byDefault();
});

describe('Season Detection', function () {
    test('correctly identifies heating season months', function () {
        expect($this->calculator->isHeatingSeason(Carbon::create(2024, 12, 15)))->toBeTrue();
        expect($this->calculator->isHeatingSeason(Carbon::create(2024, 3, 15)))->toBeTrue();
        expect($this->calculator->isHeatingSeason(Carbon::create(2024, 10, 15)))->toBeTrue();
    });

    test('correctly identifies summer period months', function () {
        expect($this->calculator->isSummerPeriod(Carbon::create(2024, 6, 15)))->toBeTrue();
        expect($this->calculator->isSummerPeriod(Carbon::create(2024, 8, 15)))->toBeTrue();
        expect($this->calculator->isSummerPeriod(Carbon::create(2024, 5, 15)))->toBeTrue();
    });

    test('heating season and summer period are mutually exclusive', function () {
        $testMonths = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
        
        foreach ($testMonths as $month) {
            $date = Carbon::create(2024, $month, 15);
            $isHeating = $this->calculator->isHeatingSeason($date);
            $isSummer = $this->calculator->isSummerPeriod($date);
            
            expect($isHeating)->not->toBe($isSummer);
        }
    });
});

describe('Summer Calculations', function () {
    test('returns zero for heating season months', function () {
        $building = Building::factory()->make(['id' => 1, 'total_apartments' => 10]);
        $winterMonth = Carbon::create(2024, 12, 15);

        $this->logger->shouldReceive('warning')
            ->once()
            ->with('Summer gyvatukas calculation requested for heating season month', [
                'building_id' => 1,
                'month' => '2024-12',
            ]);

        $result = $this->calculator->calculateSummerGyvatukas($building, $winterMonth);
        expect($result)->toBe(0.0);
    });

    test('calculates summer gyvatukas correctly', function () {
        $building = Building::factory()->make(['id' => 1, 'total_apartments' => 20]);
        $summerMonth = Carbon::create(2024, 6, 15);

        $expectedResult = CalculationResult::create(
            energy: 300.0,
            calculationType: GyvatukasCalculationType::SUMMER->value,
            buildingId: 1
        );

        $this->cacheManager->shouldReceive('remember')
            ->once()
            ->with(
                GyvatukasCalculationType::SUMMER,
                $building,
                $summerMonth,
                Mockery::type('callable')
            )
            ->andReturn($expectedResult);

        $result = $this->calculator->calculateSummerGyvatukas($building, $summerMonth);
        expect($result)->toBe(300.0);
    });
});

describe('Winter Calculations', function () {
    test('returns zero for summer months', function () {
        $building = Building::factory()->make(['id' => 1, 'total_apartments' => 10]);
        $summerMonth = Carbon::create(2024, 7, 15);

        $this->logger->shouldReceive('warning')
            ->once()
            ->with('Winter gyvatukas calculation requested for summer month', [
                'building_id' => 1,
                'month' => '2024-07',
            ]);

        $result = $this->calculator->calculateWinterGyvatukas($building, $summerMonth);
        expect($result)->toBe(0.0);
    });

    test('calculates winter gyvatukas with adjustment factors', function () {
        $building = Building::factory()->make([
            'id' => 1,
            'total_apartments' => 20,
            'gyvatukas_summer_average' => 150.0,
            'gyvatukas_last_calculated' => now()->subMonths(6),
        ]);
        $winterMonth = Carbon::create(2024, 12, 15);

        $expectedResult = CalculationResult::create(
            energy: 195.0, // 150.0 * 1.3 (peak winter adjustment)
            calculationType: GyvatukasCalculationType::WINTER->value,
            buildingId: 1
        );

        $this->cacheManager->shouldReceive('remember')
            ->once()
            ->andReturn($expectedResult);

        $result = $this->calculator->calculateWinterGyvatukas($building, $winterMonth);
        expect($result)->toBe(195.0);
    });
});

describe('Distribution Methods', function () {
    test('distributes costs using enum-based methods', function () {
        $building = Building::factory()->make(['id' => 1]);
        $totalCost = 1000.0;

        $this->distributor->shouldReceive('distribute')
            ->once()
            ->with($building, $totalCost, DistributionMethod::EQUAL)
            ->andReturn([1 => 333.33, 2 => 333.33, 3 => 333.34]);

        $result = $this->calculator->distributeCirculationCost($building, $totalCost, 'equal');
        
        expect($result)->toBe([1 => 333.33, 2 => 333.33, 3 => 333.34]);
    });

    test('handles area-based distribution', function () {
        $building = Building::factory()->make(['id' => 1]);
        $totalCost = 1000.0;

        $this->distributor->shouldReceive('distribute')
            ->once()
            ->with($building, $totalCost, DistributionMethod::AREA)
            ->andReturn([1 => 400.0, 2 => 600.0]);

        $result = $this->calculator->distributeCirculationCost($building, $totalCost, 'area');
        
        expect($result)->toBe([1 => 400.0, 2 => 600.0]);
    });
});

describe('Cache Management', function () {
    test('clears building cache correctly', function () {
        $building = Building::factory()->make(['id' => 1]);

        $this->cacheManager->shouldReceive('clearBuildingCache')
            ->once()
            ->with($building);

        $this->calculator->clearBuildingCache($building);
    });

    test('clears all cache correctly', function () {
        $this->cacheManager->shouldReceive('clearAllCache')
            ->once();

        $this->calculator->clearAllCache();
    });
});

describe('Validation', function () {
    test('throws exception for invalid apartment count', function () {
        $building = Building::factory()->make(['id' => 1, 'total_apartments' => 0]);
        $summerMonth = Carbon::create(2024, 6, 15);

        expect(fn () => $this->calculator->calculateSummerGyvatukas($building, $summerMonth))
            ->toThrow(InvalidArgumentException::class, 'Building 1 has invalid apartment count: 0');
    });

    test('throws exception for excessive apartment count', function () {
        $building = Building::factory()->make(['id' => 1, 'total_apartments' => 1500]);
        $summerMonth = Carbon::create(2024, 6, 15);

        expect(fn () => $this->calculator->calculateSummerGyvatukas($building, $summerMonth))
            ->toThrow(InvalidArgumentException::class, 'Building 1 exceeds maximum apartment limit: 1500 > 1000');
    });
});

describe('Backward Compatibility', function () {
    test('calculate method works for both seasons', function () {
        $building = Building::factory()->make(['id' => 1, 'total_apartments' => 10]);
        
        // Summer month
        $summerMonth = Carbon::create(2024, 6, 15);
        $summerResult = CalculationResult::create(150.0, 'summer', 1);
        
        $this->cacheManager->shouldReceive('remember')
            ->once()
            ->andReturn($summerResult);

        $result = $this->calculator->calculate($building, $summerMonth);
        expect($result)->toBe(150.0);

        // Winter month
        $winterMonth = Carbon::create(2024, 12, 15);
        $winterResult = CalculationResult::create(195.0, 'winter', 1);
        
        $this->cacheManager->shouldReceive('remember')
            ->once()
            ->andReturn($winterResult);

        $result = $this->calculator->calculate($building, $winterMonth);
        expect($result)->toBe(195.0);
    });
});