<?php

declare(strict_types=1);

use App\Contracts\GyvatukasCalculatorInterface;
use App\Models\Building;
use App\Services\GyvatukasCalculator;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Psr\Log\LoggerInterface;

beforeEach(function () {
    $this->cache = Mockery::mock(CacheRepository::class);
    $this->config = Mockery::mock(ConfigRepository::class);
    $this->logger = Mockery::mock(LoggerInterface::class);
    $this->calculator = new GyvatukasCalculator($this->cache, $this->config, $this->logger);

    // Set up default config values
    $this->config->shouldReceive('get')
        ->with('gyvatukas.summer_months', [5, 6, 7, 8, 9])
        ->andReturn([5, 6, 7, 8, 9])
        ->byDefault();

    $this->config->shouldReceive('get')
        ->with('gyvatukas.default_circulation_rate', 15.0)
        ->andReturn(15.0)
        ->byDefault();

    $this->config->shouldReceive('get')
        ->with('gyvatukas.cache_ttl', 86400)
        ->andReturn(86400)
        ->byDefault();

    $this->config->shouldReceive('get')
        ->with('gyvatukas.summer_average_validity_months', 12)
        ->andReturn(12)
        ->byDefault();
});

test('isHeatingSeason returns true for heating season months', function () {
    $winterMonth = Carbon::create(2024, 12, 15);
    expect($this->calculator->isHeatingSeason($winterMonth))->toBeTrue();

    $springMonth = Carbon::create(2024, 3, 15);
    expect($this->calculator->isHeatingSeason($springMonth))->toBeTrue();
});

test('isHeatingSeason returns false for summer months', function () {
    $summerMonth = Carbon::create(2024, 7, 15);
    expect($this->calculator->isHeatingSeason($summerMonth))->toBeFalse();
});

test('isSummerPeriod returns true for summer months', function () {
    $summerMonth = Carbon::create(2024, 6, 15);
    expect($this->calculator->isSummerPeriod($summerMonth))->toBeTrue();
});

test('isSummerPeriod returns false for heating season months', function () {
    $winterMonth = Carbon::create(2024, 1, 15);
    expect($this->calculator->isSummerPeriod($winterMonth))->toBeFalse();
});

test('calculateSummerGyvatukas returns 0 for heating season months', function () {
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

test('calculateSummerGyvatukas calculates for summer months', function () {
    $building = Building::factory()->make(['id' => 1, 'total_apartments' => 10]);
    $summerMonth = Carbon::create(2024, 6, 15);

    $this->cache->shouldReceive('remember')
        ->once()
        ->with('gyvatukas:summer:1:2024-06', 86400, Mockery::type('callable'))
        ->andReturn(150.0);

    $result = $this->calculator->calculateSummerGyvatukas($building, $summerMonth);
    expect($result)->toBe(150.0);
});

test('calculateWinterGyvatukas returns 0 for summer months', function () {
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

test('calculateWinterGyvatukas calculates for heating season months', function () {
    $building = Building::factory()->make(['id' => 1, 'total_apartments' => 10]);
    $winterMonth = Carbon::create(2024, 12, 15);

    $this->cache->shouldReceive('remember')
        ->once()
        ->with('gyvatukas:winter:1:2024-12', 86400, Mockery::type('callable'))
        ->andReturn(195.0);

    $result = $this->calculator->calculateWinterGyvatukas($building, $winterMonth);
    expect($result)->toBe(195.0);
});

test('getSummerAverage returns cached value when valid', function () {
    $building = Building::factory()->make([
        'id' => 1,
        'gyvatukas_summer_average' => 150.0,
        'gyvatukas_last_calculated' => now()->subMonths(6),
    ]);

    $result = $this->calculator->getSummerAverage($building);
    expect($result)->toBe(150.0);
});

test('clearBuildingCache clears cache for specific building', function () {
    $building = Building::factory()->make(['id' => 1]);

    $this->cache->shouldReceive('forget')->twice();
    $this->logger->shouldReceive('info')
        ->once()
        ->with('Gyvatukas cache cleared for building', ['building_id' => 1]);

    $this->calculator->clearBuildingCache($building);
});

test('clearAllCache clears all cache', function () {
    $this->cache->shouldReceive('flush')->once();
    $this->logger->shouldReceive('info')
        ->once()
        ->with('All gyvatukas cache cleared');

    $this->calculator->clearAllCache();
});