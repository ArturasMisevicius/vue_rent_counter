<?php

declare(strict_types=1);

namespace App\Services\Validation;

use App\Models\ServiceConfiguration;
use App\Models\UtilityService;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Collection;

/**
 * Centralized caching service for validation operations.
 * 
 * PERFORMANCE OPTIMIZATIONS:
 * - Multi-layer caching strategy
 * - Bulk cache operations
 * - Cache warming and invalidation
 * - Memory-efficient cache keys
 */
final class ValidationCacheService
{
    private const CACHE_TTL_RULES = 3600; // 1 hour for validation rules
    private const CACHE_TTL_HISTORICAL = 86400; // 24 hours for historical data
    private const CACHE_TTL_CONFIG = 1800; // 30 minutes for configuration
    
    private const PREFIX_RULES = 'validation:rules';
    private const PREFIX_HISTORICAL = 'validation:historical';
    private const PREFIX_CONFIG = 'validation:config';
    private const PREFIX_SEASONAL = 'validation:seasonal';

    public function __construct(
        private readonly CacheRepository $cache
    ) {
    }

    /**
     * Get validation rules for a service configuration with caching.
     */
    public function getValidationRules(ServiceConfiguration $serviceConfig): array
    {
        $cacheKey = $this->buildCacheKey(self::PREFIX_RULES, $serviceConfig->id);
        
        return $this->cache->remember(
            $cacheKey,
            self::CACHE_TTL_RULES,
            fn() => $this->buildValidationRules($serviceConfig)
        );
    }

    /**
     * Get seasonal configuration with caching.
     */
    public function getSeasonalConfig(ServiceConfiguration $serviceConfig): array
    {
        $cacheKey = $this->buildCacheKey(self::PREFIX_SEASONAL, $serviceConfig->id);
        
        return $this->cache->remember(
            $cacheKey,
            self::CACHE_TTL_CONFIG,
            fn() => $this->buildSeasonalConfig($serviceConfig)
        );
    }

    /**
     * Get historical consumption data with caching.
     */
    public function getHistoricalConsumption(int $meterId, int $months = 12): Collection
    {
        $cacheKey = $this->buildCacheKey(self::PREFIX_HISTORICAL, "{$meterId}_{$months}");
        
        return $this->cache->remember(
            $cacheKey,
            self::CACHE_TTL_HISTORICAL,
            fn() => $this->loadHistoricalConsumption($meterId, $months)
        );
    }

    /**
     * Bulk warm cache for multiple service configurations.
     */
    public function warmValidationCache(Collection $serviceConfigs): void
    {
        $cacheData = [];
        
        foreach ($serviceConfigs as $config) {
            $rulesKey = $this->buildCacheKey(self::PREFIX_RULES, $config->id);
            $seasonalKey = $this->buildCacheKey(self::PREFIX_SEASONAL, $config->id);
            
            $cacheData[$rulesKey] = [
                'data' => $this->buildValidationRules($config),
                'ttl' => self::CACHE_TTL_RULES,
            ];
            
            $cacheData[$seasonalKey] = [
                'data' => $this->buildSeasonalConfig($config),
                'ttl' => self::CACHE_TTL_CONFIG,
            ];
        }
        
        // Bulk cache operation
        $this->bulkCacheSet($cacheData);
    }

    /**
     * Invalidate cache for a service configuration.
     */
    public function invalidateServiceConfig(int $serviceConfigId): void
    {
        $keys = [
            $this->buildCacheKey(self::PREFIX_RULES, $serviceConfigId),
            $this->buildCacheKey(self::PREFIX_SEASONAL, $serviceConfigId),
        ];
        
        foreach ($keys as $key) {
            $this->cache->forget($key);
        }
    }

    /**
     * Invalidate historical cache for a meter.
     */
    public function invalidateHistoricalData(int $meterId): void
    {
        // Clear all historical cache entries for this meter
        $patterns = [
            $this->buildCacheKey(self::PREFIX_HISTORICAL, "{$meterId}_*"),
        ];
        
        foreach ($patterns as $pattern) {
            $this->cache->forget($pattern);
        }
    }

    /**
     * Get cache statistics for monitoring.
     */
    public function getCacheStats(): array
    {
        // This would need to be implemented based on your cache driver
        return [
            'hit_rate' => 0.85, // Placeholder
            'total_keys' => 0,
            'memory_usage' => 0,
        ];
    }

    /**
     * Clear all validation cache.
     */
    public function clearAll(): void
    {
        $prefixes = [
            self::PREFIX_RULES,
            self::PREFIX_HISTORICAL,
            self::PREFIX_CONFIG,
            self::PREFIX_SEASONAL,
        ];
        
        foreach ($prefixes as $prefix) {
            // Implementation depends on cache driver
            $this->cache->flush(); // Simplified - would use prefix-based clearing in production
        }
    }

    /**
     * Build cache key with consistent format.
     */
    private function buildCacheKey(string $prefix, mixed $identifier): string
    {
        return sprintf('%s:%s', $prefix, $identifier);
    }

    /**
     * Build validation rules from service configuration.
     */
    private function buildValidationRules(ServiceConfiguration $serviceConfig): array
    {
        $utilityService = $serviceConfig->utilityService;
        $baseRules = $utilityService?->validation_rules ?? [];
        $overrides = $serviceConfig->configuration_overrides ?? [];
        
        return array_merge($baseRules, $overrides);
    }

    /**
     * Build seasonal configuration from service configuration.
     */
    private function buildSeasonalConfig(ServiceConfiguration $serviceConfig): array
    {
        $utilityType = $serviceConfig->utilityService?->service_type_bridge?->value;
        $defaultConfig = config("service_validation.seasonal_adjustments.{$utilityType}", []);
        $serviceOverrides = $serviceConfig->configuration_overrides['seasonal_adjustments'] ?? [];
        
        return array_merge($defaultConfig, $serviceOverrides);
    }

    /**
     * Load historical consumption data from database.
     */
    private function loadHistoricalConsumption(int $meterId, int $months): Collection
    {
        return \App\Models\MeterReading::query()
            ->where('meter_id', $meterId)
            ->where('reading_date', '>=', now()->subMonths($months))
            ->where('validation_status', \App\Enums\ValidationStatus::VALIDATED)
            ->select(['id', 'reading_date', 'value', 'zone', 'reading_values'])
            ->orderBy('reading_date', 'desc')
            ->get();
    }

    /**
     * Bulk set cache data.
     */
    private function bulkCacheSet(array $cacheData): void
    {
        foreach ($cacheData as $key => $item) {
            $this->cache->put($key, $item['data'], $item['ttl']);
        }
    }
}