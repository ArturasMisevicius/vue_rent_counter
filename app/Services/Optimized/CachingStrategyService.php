<?php

declare(strict_types=1);

namespace App\Services\Optimized;

use App\Models\MeterReading;
use App\Models\Meter;
use App\Models\Tariff;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;

/**
 * Comprehensive Caching Strategy Service
 * 
 * Implements multi-level caching for utilities management system
 */
final readonly class CachingStrategyService
{
    public function __construct(
        private int $shortTtl = 300,    // 5 minutes
        private int $mediumTtl = 1800,  // 30 minutes  
        private int $longTtl = 3600,    // 1 hour
        private int $dailyTtl = 86400,  // 24 hours
    ) {}

    /**
     * CACHE KEY STRATEGY
     * 
     * Format: {service}:{entity}:{tenant_id}:{specific_params}:{version}
     * Examples:
     * - readings:dashboard:123:30d:v1
     * - meters:property:123:456:v1
     * - tariffs:active:123:electricity:v2
     */

    /**
     * 1. QUERY RESULT CACHING
     */
    public function getCachedMeterReadings(int $tenantId, string $period = '30d'): array
    {
        $cacheKey = "readings:dashboard:{$tenantId}:{$period}:v1";
        
        return Cache::remember($cacheKey, $this->mediumTtl, function () use ($tenantId, $period) {
            $days = (int) str_replace('d', '', $period);
            
            return MeterReading::where('tenant_id', $tenantId)
                ->where('created_at', '>=', now()->subDays($days))
                ->with(['meter:id,serial_number,type', 'enteredBy:id,name'])
                ->get()
                ->toArray();
        });
    }

    /**
     * 2. AGGREGATION CACHING
     */
    public function getCachedDashboardMetrics(int $tenantId): array
    {
        $cacheKey = "metrics:dashboard:{$tenantId}:v1";
        
        return Cache::remember($cacheKey, $this->shortTtl, function () use ($tenantId) {
            return [
                'total_readings' => MeterReading::where('tenant_id', $tenantId)->count(),
                'validated_readings' => MeterReading::where('tenant_id', $tenantId)
                    ->where('validation_status', 'validated')->count(),
                'total_meters' => Meter::where('tenant_id', $tenantId)->count(),
                'avg_consumption' => MeterReading::where('tenant_id', $tenantId)
                    ->where('created_at', '>=', now()->subDays(30))
                    ->avg('value'),
            ];
        });
    }

    /**
     * 3. RELATIONSHIP CACHING
     */
    public function getCachedMeterWithReadings(int $meterId): array
    {
        $cacheKey = "meter:full:{$meterId}:v1";
        
        return Cache::remember($cacheKey, $this->longTtl, function () use ($meterId) {
            return Meter::with([
                'property:id,name,building_id',
                'property.building:id,name,address',
                'readings' => function ($query) {
                    $query->latest()->limit(10);
                }
            ])->find($meterId)->toArray();
        });
    }

    /**
     * 4. CONFIGURATION CACHING (Long-term)
     */
    public function getCachedActiveTariffs(int $tenantId, string $serviceType): array
    {
        $cacheKey = "tariffs:active:{$tenantId}:{$serviceType}:v1";
        
        return Cache::remember($cacheKey, $this->dailyTtl, function () use ($tenantId, $serviceType) {
            return Tariff::where('tenant_id', $tenantId)
                ->where('type', $serviceType)
                ->where('active_from', '<=', now())
                ->where(function ($query) {
                    $query->whereNull('active_until')
                          ->orWhere('active_until', '>=', now());
                })
                ->get()
                ->toArray();
        });
    }

    /**
     * 5. CACHE TAGS FOR ORGANIZED CLEARING
     */
    public function getCachedWithTags(string $key, array $tags, int $ttl, callable $callback): mixed
    {
        return Cache::tags($tags)->remember($key, $ttl, $callback);
    }

    public function getMeterReadingsWithTags(int $tenantId, int $meterId): array
    {
        $cacheKey = "readings:meter:{$tenantId}:{$meterId}:v1";
        $tags = ["tenant:{$tenantId}", "meter:{$meterId}", 'readings'];
        
        return $this->getCachedWithTags($cacheKey, $tags, $this->mediumTtl, function () use ($tenantId, $meterId) {
            return MeterReading::where('tenant_id', $tenantId)
                ->where('meter_id', $meterId)
                ->latest()
                ->limit(50)
                ->get()
                ->toArray();
        });
    }

    /**
     * 6. REDIS-SPECIFIC OPTIMIZATIONS
     */
    public function getCachedWithRedisOptimization(string $key, callable $callback, int $ttl = null): mixed
    {
        $ttl = $ttl ?? $this->mediumTtl;
        
        // Try to get from Redis first
        $cached = Redis::get($key);
        
        if ($cached !== null) {
            return json_decode($cached, true);
        }
        
        // Generate data
        $data = $callback();
        
        // Store in Redis with compression for large datasets
        $serialized = json_encode($data);
        
        if (strlen($serialized) > 1024) {
            // Compress large data
            $compressed = gzcompress($serialized, 6);
            Redis::setex($key . ':compressed', $ttl, $compressed);
            Redis::setex($key . ':meta', $ttl, json_encode(['compressed' => true]));
        } else {
            Redis::setex($key, $ttl, $serialized);
        }
        
        return $data;
    }

    /**
     * 7. CACHE INVALIDATION STRATEGIES
     */
    
    /**
     * Clear cache when meter reading is created/updated
     */
    public function invalidateMeterReadingCache(int $tenantId, int $meterId): void
    {
        $patterns = [
            "readings:dashboard:{$tenantId}:*",
            "readings:meter:{$tenantId}:{$meterId}:*",
            "metrics:dashboard:{$tenantId}:*",
            "meter:full:{$meterId}:*",
        ];
        
        foreach ($patterns as $pattern) {
            $this->clearCachePattern($pattern);
        }
        
        // Clear tagged cache
        Cache::tags(["tenant:{$tenantId}", "meter:{$meterId}", 'readings'])->flush();
    }

    /**
     * Clear cache when tariff changes
     */
    public function invalidateTariffCache(int $tenantId, string $serviceType): void
    {
        $patterns = [
            "tariffs:active:{$tenantId}:{$serviceType}:*",
            "tariffs:active:{$tenantId}:*:*",
        ];
        
        foreach ($patterns as $pattern) {
            $this->clearCachePattern($pattern);
        }
        
        Cache::tags(["tenant:{$tenantId}", 'tariffs'])->flush();
    }

    /**
     * 8. CACHE WARMING STRATEGIES
     */
    public function warmDashboardCache(int $tenantId): void
    {
        // Pre-load commonly accessed data
        $this->getCachedDashboardMetrics($tenantId);
        $this->getCachedMeterReadings($tenantId, '30d');
        $this->getCachedMeterReadings($tenantId, '7d');
        
        // Pre-load active tariffs for all service types
        $serviceTypes = ['electricity', 'water', 'heating'];
        foreach ($serviceTypes as $type) {
            $this->getCachedActiveTariffs($tenantId, $type);
        }
    }

    /**
     * 9. CACHE PERFORMANCE MONITORING
     */
    public function getCacheStats(): array
    {
        return [
            'redis_info' => Redis::info(),
            'cache_hit_rate' => $this->calculateHitRate(),
            'memory_usage' => Redis::info('memory'),
            'key_count' => Redis::dbsize(),
        ];
    }

    /**
     * 10. CACHE LAYERS (L1: Memory, L2: Redis, L3: Database)
     */
    public function getWithMultiLevelCache(string $key, callable $callback, int $ttl = null): mixed
    {
        $ttl = $ttl ?? $this->mediumTtl;
        
        // L1: In-memory cache (APCu or array)
        static $memoryCache = [];
        if (isset($memoryCache[$key])) {
            return $memoryCache[$key];
        }
        
        // L2: Redis cache
        $redisValue = Redis::get($key);
        if ($redisValue !== null) {
            $data = json_decode($redisValue, true);
            $memoryCache[$key] = $data;
            return $data;
        }
        
        // L3: Generate from database
        $data = $callback();
        
        // Store in both levels
        $memoryCache[$key] = $data;
        Redis::setex($key, $ttl, json_encode($data));
        
        return $data;
    }

    /**
     * 11. CACHE VERSIONING FOR SAFE DEPLOYMENTS
     */
    public function getCacheVersion(): string
    {
        return Cache::rememberForever('cache:version', function () {
            return 'v' . time();
        });
    }

    public function bumpCacheVersion(): void
    {
        Cache::forget('cache:version');
        $this->getCacheVersion();
    }

    /**
     * 12. UTILITY METHODS
     */
    private function clearCachePattern(string $pattern): void
    {
        if (str_contains($pattern, '*')) {
            // Use Redis SCAN for pattern matching
            $keys = Redis::keys($pattern);
            if (!empty($keys)) {
                Redis::del($keys);
            }
        } else {
            Cache::forget($pattern);
        }
    }

    private function calculateHitRate(): float
    {
        $info = Redis::info('stats');
        $hits = $info['keyspace_hits'] ?? 0;
        $misses = $info['keyspace_misses'] ?? 0;
        
        if ($hits + $misses === 0) {
            return 0.0;
        }
        
        return round(($hits / ($hits + $misses)) * 100, 2);
    }

    /**
     * 13. CACHE PRELOADING FOR REPORTS
     */
    public function preloadReportData(int $tenantId, Carbon $startDate, Carbon $endDate): void
    {
        // Preload data in background for heavy reports
        $cacheKey = "report:consumption:{$tenantId}:{$startDate->format('Y-m-d')}:{$endDate->format('Y-m-d')}:v1";
        
        Cache::put($cacheKey, function () use ($tenantId, $startDate, $endDate) {
            return MeterReading::where('tenant_id', $tenantId)
                ->whereBetween('reading_date', [$startDate, $endDate])
                ->with(['meter.property'])
                ->get()
                ->groupBy('meter_id')
                ->map(function ($readings) {
                    return [
                        'total_consumption' => $readings->sum('value'),
                        'reading_count' => $readings->count(),
                        'avg_consumption' => $readings->avg('value'),
                    ];
                })
                ->toArray();
        }, $this->longTtl);
    }

    /**
     * 14. CACHE DEBUGGING
     */
    public function debugCache(string $key): array
    {
        return [
            'exists' => Cache::has($key),
            'ttl' => Redis::ttl($key),
            'size' => strlen(Redis::get($key) ?? ''),
            'type' => Redis::type($key),
        ];
    }
}