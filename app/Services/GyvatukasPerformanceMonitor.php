<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Performance monitoring service for gyvatukas calculations.
 */
final readonly class GyvatukasPerformanceMonitor
{
    private const METRICS_CACHE_KEY = 'gyvatukas:performance:metrics';
    private const METRICS_TTL = 3600; // 1 hour

    /**
     * Record calculation performance metrics.
     */
    public function recordCalculation(
        int $buildingId,
        string $calculationType,
        float $executionTime,
        bool $cacheHit = false
    ): void {
        $metrics = $this->getMetrics();
        
        $metrics['calculations']++;
        $metrics['total_execution_time'] += $executionTime;
        $metrics['cache_hits'] += $cacheHit ? 1 : 0;
        $metrics['cache_misses'] += $cacheHit ? 0 : 1;
        
        $metrics['by_type'][$calculationType] = ($metrics['by_type'][$calculationType] ?? 0) + 1;
        
        // Track slow calculations (>100ms)
        if ($executionTime > 0.1) {
            $metrics['slow_calculations']++;
            
            Log::warning('Slow gyvatukas calculation detected', [
                'building_id' => $buildingId,
                'type' => $calculationType,
                'execution_time' => $executionTime,
                'cache_hit' => $cacheHit,
            ]);
        }
        
        $this->storeMetrics($metrics);
    }

    /**
     * Record distribution calculation metrics.
     */
    public function recordDistribution(
        int $buildingId,
        string $method,
        int $propertyCount,
        float $executionTime,
        bool $cacheHit = false
    ): void {
        $metrics = $this->getMetrics();
        
        $metrics['distributions']++;
        $metrics['total_distribution_time'] += $executionTime;
        $metrics['properties_processed'] += $propertyCount;
        
        $metrics['distribution_cache_hits'] += $cacheHit ? 1 : 0;
        $metrics['distribution_cache_misses'] += $cacheHit ? 0 : 1;
        
        $metrics['by_distribution_method'][$method] = ($metrics['by_distribution_method'][$method] ?? 0) + 1;
        
        $this->storeMetrics($metrics);
    }

    /**
     * Get current performance metrics.
     */
    public function getMetrics(): array
    {
        return Cache::get(self::METRICS_CACHE_KEY, [
            'calculations' => 0,
            'distributions' => 0,
            'total_execution_time' => 0.0,
            'total_distribution_time' => 0.0,
            'cache_hits' => 0,
            'cache_misses' => 0,
            'distribution_cache_hits' => 0,
            'distribution_cache_misses' => 0,
            'slow_calculations' => 0,
            'properties_processed' => 0,
            'by_type' => [],
            'by_distribution_method' => [],
            'last_reset' => now()->toISOString(),
        ]);
    }

    /**
     * Get performance summary.
     */
    public function getSummary(): array
    {
        $metrics = $this->getMetrics();
        
        $totalCalculations = $metrics['calculations'];
        $totalDistributions = $metrics['distributions'];
        
        return [
            'calculations' => [
                'total' => $totalCalculations,
                'average_time' => $totalCalculations > 0 
                    ? round($metrics['total_execution_time'] / $totalCalculations * 1000, 2) 
                    : 0,
                'cache_hit_rate' => $totalCalculations > 0 
                    ? round($metrics['cache_hits'] / $totalCalculations * 100, 2) 
                    : 0,
                'slow_calculations' => $metrics['slow_calculations'],
                'by_type' => $metrics['by_type'],
            ],
            'distributions' => [
                'total' => $totalDistributions,
                'average_time' => $totalDistributions > 0 
                    ? round($metrics['total_distribution_time'] / $totalDistributions * 1000, 2) 
                    : 0,
                'cache_hit_rate' => $totalDistributions > 0 
                    ? round($metrics['distribution_cache_hits'] / $totalDistributions * 100, 2) 
                    : 0,
                'properties_processed' => $metrics['properties_processed'],
                'by_method' => $metrics['by_distribution_method'],
            ],
            'last_reset' => $metrics['last_reset'],
        ];
    }

    /**
     * Reset performance metrics.
     */
    public function resetMetrics(): void
    {
        Cache::forget(self::METRICS_CACHE_KEY);
        
        Log::info('Gyvatukas performance metrics reset');
    }

    /**
     * Store metrics in cache.
     */
    private function storeMetrics(array $metrics): void
    {
        Cache::put(self::METRICS_CACHE_KEY, $metrics, self::METRICS_TTL);
    }

    /**
     * Check if performance is degrading and log warnings.
     */
    public function checkPerformanceHealth(): array
    {
        $summary = $this->getSummary();
        $issues = [];

        // Check cache hit rate
        if ($summary['calculations']['cache_hit_rate'] < 80) {
            $issues[] = 'Low cache hit rate for calculations: ' . $summary['calculations']['cache_hit_rate'] . '%';
        }

        if ($summary['distributions']['cache_hit_rate'] < 70) {
            $issues[] = 'Low cache hit rate for distributions: ' . $summary['distributions']['cache_hit_rate'] . '%';
        }

        // Check average execution time
        if ($summary['calculations']['average_time'] > 50) {
            $issues[] = 'High average calculation time: ' . $summary['calculations']['average_time'] . 'ms';
        }

        if ($summary['distributions']['average_time'] > 30) {
            $issues[] = 'High average distribution time: ' . $summary['distributions']['average_time'] . 'ms';
        }

        // Check slow calculations
        $slowRate = $summary['calculations']['total'] > 0 
            ? ($summary['calculations']['slow_calculations'] / $summary['calculations']['total']) * 100 
            : 0;

        if ($slowRate > 5) {
            $issues[] = 'High rate of slow calculations: ' . round($slowRate, 2) . '%';
        }

        if (!empty($issues)) {
            Log::warning('Gyvatukas performance issues detected', [
                'issues' => $issues,
                'summary' => $summary,
            ]);
        }

        return $issues;
    }
}