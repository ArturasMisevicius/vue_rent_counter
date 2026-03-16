<?php

declare(strict_types=1);

namespace App\Services\Security;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Facades\Cache;
use Psr\Log\LoggerInterface;

/**
 * Security Performance Monitor
 * 
 * Tracks and reports on security header performance metrics
 * for optimization and monitoring purposes.
 */
final class SecurityPerformanceMonitor
{
    private const METRICS_CACHE_KEY = 'security_performance_metrics';
    private const METRICS_TTL = 3600; // 1 hour

    public function __construct(
        private readonly ConfigRepository $config,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Record a performance metric.
     */
    public function recordMetric(string $operation, float $durationMs, array $context = []): void
    {
        if (!$this->config->get('security.performance.enabled', true)) {
            return;
        }

        $metrics = $this->getMetrics();
        
        $metrics['operations'][$operation] = $metrics['operations'][$operation] ?? [
            'count' => 0,
            'total_time' => 0,
            'min_time' => PHP_FLOAT_MAX,
            'max_time' => 0,
            'last_recorded' => null,
        ];

        $op = &$metrics['operations'][$operation];
        $op['count']++;
        $op['total_time'] += $durationMs;
        $op['min_time'] = min($op['min_time'], $durationMs);
        $op['max_time'] = max($op['max_time'], $durationMs);
        $op['last_recorded'] = now()->toISOString();

        // Check thresholds
        $warningThreshold = $this->config->get('security.performance.thresholds.warning_ms', 15);
        $errorThreshold = $this->config->get('security.performance.thresholds.error_ms', 50);

        if ($durationMs > $errorThreshold) {
            $this->logger->error('Security operation exceeded error threshold', [
                'operation' => $operation,
                'duration_ms' => $durationMs,
                'threshold_ms' => $errorThreshold,
                'context' => $context,
            ]);
        } elseif ($durationMs > $warningThreshold) {
            $this->logger->warning('Security operation exceeded warning threshold', [
                'operation' => $operation,
                'duration_ms' => $durationMs,
                'threshold_ms' => $warningThreshold,
                'context' => $context,
            ]);
        }

        $this->storeMetrics($metrics);
    }

    /**
     * Get current performance metrics.
     */
    public function getMetrics(): array
    {
        return Cache::get(self::METRICS_CACHE_KEY, [
            'operations' => [],
            'cache_stats' => [
                'hits' => 0,
                'misses' => 0,
                'hit_rate' => 0,
            ],
            'last_reset' => now()->toISOString(),
        ]);
    }

    /**
     * Get performance summary.
     */
    public function getSummary(): array
    {
        $metrics = $this->getMetrics();
        $summary = [];

        foreach ($metrics['operations'] as $operation => $data) {
            $avgTime = $data['count'] > 0 ? $data['total_time'] / $data['count'] : 0;
            
            $summary[$operation] = [
                'count' => $data['count'],
                'avg_time_ms' => round($avgTime, 2),
                'min_time_ms' => $data['min_time'] === PHP_FLOAT_MAX ? 0 : round($data['min_time'], 2),
                'max_time_ms' => round($data['max_time'], 2),
                'total_time_ms' => round($data['total_time'], 2),
                'last_recorded' => $data['last_recorded'],
            ];
        }

        return [
            'operations' => $summary,
            'cache_stats' => $metrics['cache_stats'],
            'last_reset' => $metrics['last_reset'],
        ];
    }

    /**
     * Record cache hit.
     */
    public function recordCacheHit(): void
    {
        $metrics = $this->getMetrics();
        $metrics['cache_stats']['hits']++;
        $this->updateCacheHitRate($metrics);
        $this->storeMetrics($metrics);
    }

    /**
     * Record cache miss.
     */
    public function recordCacheMiss(): void
    {
        $metrics = $this->getMetrics();
        $metrics['cache_stats']['misses']++;
        $this->updateCacheHitRate($metrics);
        $this->storeMetrics($metrics);
    }

    /**
     * Reset all metrics.
     */
    public function resetMetrics(): void
    {
        Cache::forget(self::METRICS_CACHE_KEY);
    }

    /**
     * Check if performance is within acceptable bounds.
     */
    public function isPerformanceHealthy(): bool
    {
        $summary = $this->getSummary();
        $warningThreshold = $this->config->get('security.performance.thresholds.warning_ms', 15);

        foreach ($summary['operations'] as $operation => $data) {
            if ($data['avg_time_ms'] > $warningThreshold) {
                return false;
            }
        }

        return true;
    }

    /**
     * Store metrics in cache.
     */
    private function storeMetrics(array $metrics): void
    {
        Cache::put(self::METRICS_CACHE_KEY, $metrics, self::METRICS_TTL);
    }

    /**
     * Update cache hit rate.
     */
    private function updateCacheHitRate(array &$metrics): void
    {
        $total = $metrics['cache_stats']['hits'] + $metrics['cache_stats']['misses'];
        $metrics['cache_stats']['hit_rate'] = $total > 0 
            ? round(($metrics['cache_stats']['hits'] / $total) * 100, 2)
            : 0;
    }
}