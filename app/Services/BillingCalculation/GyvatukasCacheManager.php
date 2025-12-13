<?php

declare(strict_types=1);

namespace App\Services\BillingCalculation;

use App\Enums\GyvatukasCalculationType;
use App\Models\Building;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Psr\Log\LoggerInterface;

/**
 * Manages caching for gyvatukas calculations.
 */
final readonly class GyvatukasCacheManager
{
    private const CACHE_PREFIX = 'gyvatukas';

    public function __construct(
        private CacheRepository $cache,
        private ConfigRepository $config,
        private LoggerInterface $logger,
    ) {}

    public function remember(
        GyvatukasCalculationType $type,
        Building $building,
        Carbon $month,
        callable $callback,
    ): mixed {
        $cacheKey = $this->buildCacheKey($type, $building->id, $month);
        $ttl = $this->getCacheTtl();

        try {
            return $this->cache->remember($cacheKey, $ttl, $callback);
        } catch (\Exception $e) {
            $this->logger->error('Cache failure during gyvatukas calculation', [
                'type' => $type->value,
                'building_id' => $building->id,
                'month' => $month->format('Y-m'),
                'error' => $e->getMessage(),
            ]);

            // Fallback to direct calculation
            return $callback();
        }
    }

    public function clearBuildingCache(Building $building): void
    {
        try {
            foreach (GyvatukasCalculationType::cases() as $type) {
                $pattern = $this->buildCacheKeyPattern($type, $building->id);
                $this->cache->forget($pattern);
            }

            $this->logger->info('Gyvatukas cache cleared for building', [
                'building_id' => $building->id,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to clear gyvatukas cache for building', [
                'building_id' => $building->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function clearAllCache(): void
    {
        try {
            $this->cache->flush();
            $this->logger->info('All gyvatukas cache cleared');
        } catch (\Exception $e) {
            $this->logger->error('Failed to clear all gyvatukas cache', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function buildCacheKey(GyvatukasCalculationType $type, int $buildingId, Carbon $month): string
    {
        return sprintf('%s:%s:%d:%s', 
            self::CACHE_PREFIX, 
            $type->value, 
            $buildingId, 
            $month->format('Y-m')
        );
    }

    private function buildCacheKeyPattern(GyvatukasCalculationType $type, int $buildingId): string
    {
        return sprintf('%s:%s:%d:*', self::CACHE_PREFIX, $type->value, $buildingId);
    }

    private function getCacheTtl(): int
    {
        return $this->config->get('gyvatukas.cache_ttl', 86400);
    }
}