<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Illuminate\Support\Facades\Cache;

/**
 * Base class for cached statistical widgets.
 *
 * Extends ActionableWidget to provide caching capabilities for expensive
 * statistical calculations. Automatically handles cache key generation,
 * tenant scoping, and cache invalidation strategies.
 *
 * ## Key Features
 * - Automatic cache key generation with tenant scoping
 * - Configurable cache TTL (default: 15 minutes)
 * - Cache invalidation helpers
 * - Performance monitoring support
 * - Combines with ActionableWidget for clickable cached stats
 *
 * ## Usage Example
 * ```php
 * class ExpensiveStatsWidget extends CachedStatsWidget
 * {
 *     protected int $cacheTtl = 1800; // 30 minutes
 *     
 *     protected function calculateStats(): array
 *     {
 *         // Expensive calculations here
 *         return [
 *             Stat::make('Complex Metric', $this->complexCalculation())
 *         ];
 *     }
 * }
 * ```
 *
 * @package App\Filament\Widgets
 */
abstract class CachedStatsWidget extends ActionableWidget
{
    /**
     * Cache TTL in seconds (default: 15 minutes).
     *
     * @var int
     */
    protected int $cacheTtl = 900;
    
    /**
     * Get the stats with caching.
     *
     * Caches the result of calculateStats() using a tenant-scoped cache key.
     * Automatically handles cache misses and provides fallback behavior.
     *
     * @return array The cached or calculated stats
     */
    protected function getStats(): array
    {
        return Cache::remember(
            $this->getCacheKey(),
            now()->addSeconds($this->cacheTtl),
            fn () => $this->calculateStats()
        );
    }
    
    /**
     * Calculate the statistics (to be implemented by subclasses).
     *
     * This method should contain the expensive calculations that need to be
     * cached. It will be called only when the cache is empty or expired.
     *
     * @return array Array of Stat objects
     */
    abstract protected function calculateStats(): array;
    
    /**
     * Generate a cache key for this widget.
     *
     * Creates a unique cache key based on the widget class name and tenant ID
     * to ensure proper cache isolation between tenants.
     *
     * @return string The cache key
     */
    protected function getCacheKey(): string
    {
        return sprintf(
            'widget_%s_tenant_%s',
            class_basename(static::class),
            $this->getTenantId() ?? 'global'
        );
    }
    
    /**
     * Invalidate the cache for this widget.
     *
     * Useful for clearing cache when underlying data changes.
     * Can be called from observers or after data modifications.
     *
     * @return bool True if cache was cleared, false otherwise
     */
    public function invalidateCache(): bool
    {
        return Cache::forget($this->getCacheKey());
    }
    
    /**
     * Warm the cache by pre-calculating stats.
     *
     * Useful for background jobs or scheduled tasks to ensure
     * fast response times for users.
     *
     * @return array The calculated and cached stats
     */
    public function warmCache(): array
    {
        $this->invalidateCache();
        return $this->getStats();
    }
}