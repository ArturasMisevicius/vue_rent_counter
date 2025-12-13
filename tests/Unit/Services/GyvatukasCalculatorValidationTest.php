<?php

declare(strict_types=1);

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
        ->with('gyvatukas.validation.max_apartments', 1000)
        ->andReturn(1000)
        ->byDefault();
});

test('throws exception for building with zero apartments', function () {
    $building = Building::factory()->make(['id' => 1, 'total_apartments' => 0]);
    $summerMonth = Carbon::create(2024, 6, 15);

    $this->cache->shouldReceive('remember')
        ->once()
        ->andReturnUsing(function ($key, $ttl, $callback) {
            return $callback();
        });

    expect(fn () => $this->calculator->calculateSummerGyvatukas($building, $summerMonth))
        ->toThrow(InvalidArgumentException::class, 'Building 1 has invalid apartment count: 0');
});

test('throws exception for building with negative apartments', function () {
    $building = Building::factory()->make(['id' => 1, 'total_apartments' => -5]);
    $summerMonth = Carbon::create(2024, 6, 15);

    $this->cache->shouldReceive('remember')
        ->once()
        ->andReturnUsing(function ($key, $ttl, $callback) {
            return $callback();
        });

    expect(fn () => $this->calculator->calculateSummerGyvatukas($building, $summerMonth))
        ->toThrow(InvalidArgumentException::class, 'Building 1 has invalid apartment count: -5');
});

test('throws exception for building exceeding maximum apartments', function () {
    $building = Building::factory()->make(['id' => 1, 'total_apartments' => 1500]);
    $summerMonth = Carbon::create(2024, 6, 15);

    $this->cache->shouldReceive('remember')
        ->once()
        ->andReturnUsing(function ($key, $ttl, $callback) {
            return $callback();
        });

    expect(fn () => $this->calculator->calculateSummerGyvatukas($building, $summerMonth))
        ->toThrow(InvalidArgumentException::class, 'Building 1 exceeds maximum apartment limit: 1500 > 1000');
});

test('handles cache failure gracefully in summer calculation', function () {
    $building = Building::factory()->make(['id' => 1, 'total_apartments' => 10]);
    $summerMonth = Carbon::create(2024, 6, 15);

    $this->cache->shouldReceive('remember')
        ->once()
        ->andThrow(new Exception('Cache connection failed'));

    $this->logger->shouldReceive('error')
        ->once()
        ->with('Cache failure during summer gyvatukas calculation, falling back to direct calculation', [
            'building_id' => 1,
            'month' => '2024-06',
            'error' => 'Cache connection failed',
        ]);

    $result = $this->calculator->calculateSummerGyvatukas($building, $summerMonth);
    expect($result)->toBeFloat();
    expect($result)->toBeGreaterThan(0);
});

test('handles cache failure gracefully in winter calculation', function () {
    $building = Building::factory()->make([
        'id' => 1,
        'total_apartments' => 10,
        'gyvatukas_summer_average' => 150.0,
        'gyvatukas_last_calculated' => now()->subMonths(6),
    ]);
    $winterMonth = Carbon::create(2024, 12, 15);

    $this->config->shouldReceive('get')
        ->with('gyvatukas.summer_average_validity_months', 12)
        ->andReturn(12);

    $this->config->shouldReceive('get')
        ->with('gyvatukas.peak_winter_months', [12, 1, 2])
        ->andReturn([12, 1, 2]);

    $this->config->shouldReceive('get')
        ->with('gyvatukas.peak_winter_adjustment', 1.3)
        ->andReturn(1.3);

    $this->cache->shouldReceive('remember')
        ->once()
        ->andThrow(new Exception('Cache connection failed'));

    $this->logger->shouldReceive('error')
        ->once()
        ->with('Cache failure during winter gyvatukas calculation, falling back to direct calculation', [
            'building_id' => 1,
            'month' => '2024-12',
            'error' => 'Cache connection failed',
        ]);

    $result = $this->calculator->calculateWinterGyvatukas($building, $winterMonth);
    expect($result)->toBeFloat();
    expect($result)->toBeGreaterThan(0);
});

test('handles cache clearing failure gracefully', function () {
    $building = Building::factory()->make(['id' => 1]);

    $this->cache->shouldReceive('forget')
        ->twice()
        ->andThrow(new Exception('Cache clearing failed'));

    $this->logger->shouldReceive('error')
        ->once()
        ->with('Failed to clear gyvatukas cache for building', [
            'building_id' => 1,
            'error' => 'Cache clearing failed',
        ]);

    // Should not throw exception
    $this->calculator->clearBuildingCache($building);
});

test('handles global cache clearing failure gracefully', function () {
    $this->cache->shouldReceive('flush')
        ->once()
        ->andThrow(new Exception('Cache flush failed'));

    $this->logger->shouldReceive('error')
        ->once()
        ->with('Failed to clear all gyvatukas cache', [
            'error' => 'Cache flush failed',
        ]);

    // Should not throw exception
    $this->calculator->clearAllCache();
});

test('validates building in winter calculation', function () {
    $building = Building::factory()->make(['id' => 1, 'total_apartments' => 0]);
    $winterMonth = Carbon::create(2024, 12, 15);

    $this->cache->shouldReceive('remember')
        ->once()
        ->andReturnUsing(function ($key, $ttl, $callback) {
            return $callback();
        });

    expect(fn () => $this->calculator->calculateWinterGyvatukas($building, $winterMonth))
        ->toThrow(InvalidArgumentException::class, 'Building 1 has invalid apartment count: 0');
});