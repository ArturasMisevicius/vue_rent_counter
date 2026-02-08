<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\ServiceRegistration\PolicyRegistryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Policy Registry Monitoring Service
 * 
 * Provides comprehensive monitoring and health checks for the policy registry system.
 * Tracks registration performance, error rates, and system health metrics.
 */
final readonly class PolicyRegistryMonitoringService
{
    private const CACHE_PREFIX = 'policy_registry_monitoring';
    private const METRICS_TTL = 3600; // 1 hour

    public function __construct(
        private PolicyRegistryInterface $policyRegistry
    ) {}

    /**
     * Perform comprehensive health check of policy registry
     * 
     * @return array{healthy: bool, metrics: array, issues: array}
     */
    public function healthCheck(): array
    {
        $startTime = microtime(true);
        
        $validation = $this->policyRegistry->validateConfiguration();
        $metrics = $this->collectMetrics();
        $issues = $this->identifyIssues($validation, $metrics);
        
        $duration = microtime(true) - $startTime;
        
        $healthy = $validation['valid'] && empty($issues['critical']);
        
        $result = [
            'healthy' => $healthy,
            'metrics' => [
                ...$metrics,
                'health_check_duration_ms' => round($duration * 1000, 2),
                'timestamp' => now()->toISOString(),
            ],
            'issues' => $issues,
            'validation' => $validation,
        ];
        
        // Cache health check results
        Cache::put(
            self::CACHE_PREFIX . '.last_health_check',
            $result,
            self::METRICS_TTL
        );
        
        // Log health status
        if (!$healthy) {
            Log::warning('Policy registry health check failed', [
                'issues_count' => count($issues['critical']) + count($issues['warnings']),
                'validation_errors' => count($validation['policies']['errors']) + count($validation['gates']['errors']),
            ]);
        }
        
        return $result;
    }

    /**
     * Collect performance and usage metrics
     * 
     * @return array
     */
    public function collectMetrics(): array
    {
        $policies = $this->policyRegistry->getModelPolicies();
        $gates = $this->policyRegistry->getSettingsGates();
        
        return [
            'total_policies' => count($policies),
            'total_gates' => count($gates),
            'cache_hit_rate' => $this->calculateCacheHitRate(),
            'average_registration_time' => $this->getAverageRegistrationTime(),
            'error_rate_24h' => $this->getErrorRate(),
        ];
    }

    /**
     * Identify system issues based on validation and metrics
     * 
     * @param array $validation
     * @param array $metrics
     * @return array{critical: array, warnings: array, info: array}
     */
    private function identifyIssues(array $validation, array $metrics): array
    {
        $issues = [
            'critical' => [],
            'warnings' => [],
            'info' => [],
        ];
        
        // Critical issues
        if (!$validation['valid']) {
            $issues['critical'][] = 'Policy configuration validation failed';
        }
        
        if ($metrics['error_rate_24h'] > 0.1) { // > 10% error rate
            $issues['critical'][] = 'High error rate detected in policy registration';
        }
        
        // Warnings
        if ($metrics['average_registration_time'] > 100) { // > 100ms
            $issues['warnings'][] = 'Policy registration performance degraded';
        }
        
        if ($metrics['cache_hit_rate'] < 0.8) { // < 80% hit rate
            $issues['warnings'][] = 'Low cache hit rate affecting performance';
        }
        
        // Info
        if ($validation['policies']['invalid'] > 0 || $validation['gates']['invalid'] > 0) {
            $issues['info'][] = 'Some policies or gates have configuration issues';
        }
        
        return $issues;
    }

    /**
     * Calculate cache hit rate for class existence checks
     */
    private function calculateCacheHitRate(): float
    {
        $hits = Cache::get(self::CACHE_PREFIX . '.cache_hits', 0);
        $misses = Cache::get(self::CACHE_PREFIX . '.cache_misses', 0);
        
        $total = $hits + $misses;
        return $total > 0 ? $hits / $total : 1.0;
    }

    /**
     * Get average registration time from recent operations
     */
    private function getAverageRegistrationTime(): float
    {
        $times = Cache::get(self::CACHE_PREFIX . '.registration_times', []);
        return empty($times) ? 0.0 : array_sum($times) / count($times);
    }

    /**
     * Calculate error rate over the last 24 hours
     */
    private function getErrorRate(): float
    {
        $errors = Cache::get(self::CACHE_PREFIX . '.errors_24h', 0);
        $total = Cache::get(self::CACHE_PREFIX . '.operations_24h', 1);
        
        return $errors / $total;
    }

    /**
     * Record registration performance metrics
     */
    public function recordRegistrationMetrics(float $duration, int $errors): void
    {
        // Record registration time
        $times = Cache::get(self::CACHE_PREFIX . '.registration_times', []);
        $times[] = $duration * 1000; // Convert to milliseconds
        
        // Keep only last 100 measurements
        if (count($times) > 100) {
            $times = array_slice($times, -100);
        }
        
        Cache::put(self::CACHE_PREFIX . '.registration_times', $times, self::METRICS_TTL);
        
        // Record error metrics
        if ($errors > 0) {
            Cache::increment(self::CACHE_PREFIX . '.errors_24h');
        }
        Cache::increment(self::CACHE_PREFIX . '.operations_24h');
    }

    /**
     * Get cached health check results
     */
    public function getLastHealthCheck(): ?array
    {
        return Cache::get(self::CACHE_PREFIX . '.last_health_check');
    }

    /**
     * Clear all monitoring metrics
     */
    public function clearMetrics(): void
    {
        $keys = [
            self::CACHE_PREFIX . '.cache_hits',
            self::CACHE_PREFIX . '.cache_misses',
            self::CACHE_PREFIX . '.registration_times',
            self::CACHE_PREFIX . '.errors_24h',
            self::CACHE_PREFIX . '.operations_24h',
            self::CACHE_PREFIX . '.last_health_check',
        ];
        
        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }
}