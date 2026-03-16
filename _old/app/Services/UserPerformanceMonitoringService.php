<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * User Performance Monitoring Service
 * 
 * Monitors and tracks performance metrics for User model operations.
 * Provides insights into query performance, cache hit rates, and optimization opportunities.
 */
class UserPerformanceMonitoringService
{
    private const METRICS_CACHE_TTL = 3600; // 1 hour
    private const METRICS_CACHE_PREFIX = 'user_perf_metrics:';

    /**
     * Track query performance for a specific operation.
     */
    public function trackQueryPerformance(string $operation, callable $callback): mixed
    {
        $startTime = microtime(true);
        $startQueries = $this->getQueryCount();
        
        $result = $callback();
        
        $endTime = microtime(true);
        $endQueries = $this->getQueryCount();
        
        $metrics = [
            'operation' => $operation,
            'execution_time' => round(($endTime - $startTime) * 1000, 2), // ms
            'query_count' => $endQueries - $startQueries,
            'timestamp' => now()->toISOString(),
        ];
        
        $this->recordMetrics($operation, $metrics);
        
        // Log slow operations
        if ($metrics['execution_time'] > 100) { // > 100ms
            Log::warning('Slow User operation detected', $metrics);
        }
        
        return $result;
    }

    /**
     * Get cache hit rate for user operations.
     */
    public function getCacheHitRate(string $operation = null): array
    {
        $cacheKey = self::METRICS_CACHE_PREFIX . 'cache_stats' . ($operation ? ":{$operation}" : '');
        
        return Cache::get($cacheKey, [
            'hits' => 0,
            'misses' => 0,
            'hit_rate' => 0.0,
        ]);
    }

    /**
     * Record cache hit.
     */
    public function recordCacheHit(string $operation): void
    {
        $this->updateCacheStats($operation, 'hits');
    }

    /**
     * Record cache miss.
     */
    public function recordCacheMiss(string $operation): void
    {
        $this->updateCacheStats($operation, 'misses');
    }

    /**
     * Get performance summary for user operations.
     */
    public function getPerformanceSummary(): array
    {
        $cacheKey = self::METRICS_CACHE_PREFIX . 'summary';
        
        return Cache::remember($cacheKey, self::METRICS_CACHE_TTL, function () {
            $operations = [
                'user_login',
                'user_role_check',
                'user_capabilities',
                'user_projects',
                'user_tasks',
                'user_statistics',
            ];
            
            $summary = [];
            
            foreach ($operations as $operation) {
                $metrics = $this->getOperationMetrics($operation);
                $cacheStats = $this->getCacheHitRate($operation);
                
                $summary[$operation] = [
                    'avg_execution_time' => $metrics['avg_execution_time'] ?? 0,
                    'avg_query_count' => $metrics['avg_query_count'] ?? 0,
                    'total_executions' => $metrics['total_executions'] ?? 0,
                    'cache_hit_rate' => $cacheStats['hit_rate'],
                    'performance_grade' => $this->calculatePerformanceGrade($metrics, $cacheStats),
                ];
            }
            
            return $summary;
        });
    }

    /**
     * Get detailed metrics for a specific operation.
     */
    public function getOperationMetrics(string $operation): array
    {
        $cacheKey = self::METRICS_CACHE_PREFIX . "operation:{$operation}";
        
        return Cache::get($cacheKey, [
            'total_executions' => 0,
            'total_execution_time' => 0,
            'total_query_count' => 0,
            'avg_execution_time' => 0,
            'avg_query_count' => 0,
            'max_execution_time' => 0,
            'min_execution_time' => PHP_FLOAT_MAX,
        ]);
    }

    /**
     * Analyze slow queries and provide optimization recommendations.
     */
    public function analyzeSlowQueries(): array
    {
        $slowQueries = Cache::get(self::METRICS_CACHE_PREFIX . 'slow_queries', []);
        
        $recommendations = [];
        
        foreach ($slowQueries as $query) {
            $recommendations[] = $this->generateQueryRecommendation($query);
        }
        
        return [
            'slow_queries_count' => count($slowQueries),
            'recommendations' => $recommendations,
            'common_issues' => $this->identifyCommonIssues($slowQueries),
        ];
    }

    /**
     * Monitor memory usage for user operations.
     */
    public function monitorMemoryUsage(string $operation, callable $callback): mixed
    {
        $startMemory = memory_get_usage(true);
        $startPeakMemory = memory_get_peak_usage(true);
        
        $result = $callback();
        
        $endMemory = memory_get_usage(true);
        $endPeakMemory = memory_get_peak_usage(true);
        
        $memoryMetrics = [
            'operation' => $operation,
            'memory_used' => $endMemory - $startMemory,
            'peak_memory_increase' => $endPeakMemory - $startPeakMemory,
            'timestamp' => now()->toISOString(),
        ];
        
        // Log high memory usage
        if ($memoryMetrics['memory_used'] > 10 * 1024 * 1024) { // > 10MB
            Log::warning('High memory usage detected', $memoryMetrics);
        }
        
        $this->recordMemoryMetrics($operation, $memoryMetrics);
        
        return $result;
    }

    /**
     * Get optimization recommendations based on collected metrics.
     */
    public function getOptimizationRecommendations(): array
    {
        $summary = $this->getPerformanceSummary();
        $recommendations = [];
        
        foreach ($summary as $operation => $metrics) {
            if ($metrics['avg_execution_time'] > 50) { // > 50ms
                $recommendations[] = [
                    'operation' => $operation,
                    'issue' => 'Slow execution time',
                    'recommendation' => 'Consider adding caching or optimizing queries',
                    'priority' => 'high',
                ];
            }
            
            if ($metrics['avg_query_count'] > 5) {
                $recommendations[] = [
                    'operation' => $operation,
                    'issue' => 'High query count',
                    'recommendation' => 'Implement eager loading or query optimization',
                    'priority' => 'medium',
                ];
            }
            
            if ($metrics['cache_hit_rate'] < 0.8) {
                $recommendations[] = [
                    'operation' => $operation,
                    'issue' => 'Low cache hit rate',
                    'recommendation' => 'Review cache TTL and invalidation strategy',
                    'priority' => 'medium',
                ];
            }
        }
        
        return $recommendations;
    }

    /**
     * Record performance metrics for an operation.
     */
    private function recordMetrics(string $operation, array $metrics): void
    {
        $cacheKey = self::METRICS_CACHE_PREFIX . "operation:{$operation}";
        $existingMetrics = $this->getOperationMetrics($operation);
        
        $updatedMetrics = [
            'total_executions' => $existingMetrics['total_executions'] + 1,
            'total_execution_time' => $existingMetrics['total_execution_time'] + $metrics['execution_time'],
            'total_query_count' => $existingMetrics['total_query_count'] + $metrics['query_count'],
            'max_execution_time' => max($existingMetrics['max_execution_time'], $metrics['execution_time']),
            'min_execution_time' => min($existingMetrics['min_execution_time'], $metrics['execution_time']),
        ];
        
        $updatedMetrics['avg_execution_time'] = $updatedMetrics['total_execution_time'] / $updatedMetrics['total_executions'];
        $updatedMetrics['avg_query_count'] = $updatedMetrics['total_query_count'] / $updatedMetrics['total_executions'];
        
        Cache::put($cacheKey, $updatedMetrics, self::METRICS_CACHE_TTL);
        
        // Record slow queries
        if ($metrics['execution_time'] > 100) {
            $this->recordSlowQuery($operation, $metrics);
        }
    }

    /**
     * Update cache statistics.
     */
    private function updateCacheStats(string $operation, string $type): void
    {
        $cacheKey = self::METRICS_CACHE_PREFIX . "cache_stats:{$operation}";
        $stats = $this->getCacheHitRate($operation);
        
        $stats[$type]++;
        $total = $stats['hits'] + $stats['misses'];
        $stats['hit_rate'] = $total > 0 ? $stats['hits'] / $total : 0;
        
        Cache::put($cacheKey, $stats, self::METRICS_CACHE_TTL);
    }

    /**
     * Record slow query for analysis.
     */
    private function recordSlowQuery(string $operation, array $metrics): void
    {
        $cacheKey = self::METRICS_CACHE_PREFIX . 'slow_queries';
        $slowQueries = Cache::get($cacheKey, []);
        
        $slowQueries[] = array_merge($metrics, ['operation' => $operation]);
        
        // Keep only last 100 slow queries
        if (count($slowQueries) > 100) {
            $slowQueries = array_slice($slowQueries, -100);
        }
        
        Cache::put($cacheKey, $slowQueries, self::METRICS_CACHE_TTL);
    }

    /**
     * Record memory usage metrics.
     */
    private function recordMemoryMetrics(string $operation, array $metrics): void
    {
        $cacheKey = self::METRICS_CACHE_PREFIX . "memory:{$operation}";
        $existingMetrics = Cache::get($cacheKey, [
            'total_executions' => 0,
            'total_memory_used' => 0,
            'avg_memory_used' => 0,
            'max_memory_used' => 0,
        ]);
        
        $updatedMetrics = [
            'total_executions' => $existingMetrics['total_executions'] + 1,
            'total_memory_used' => $existingMetrics['total_memory_used'] + $metrics['memory_used'],
            'max_memory_used' => max($existingMetrics['max_memory_used'], $metrics['memory_used']),
        ];
        
        $updatedMetrics['avg_memory_used'] = $updatedMetrics['total_memory_used'] / $updatedMetrics['total_executions'];
        
        Cache::put($cacheKey, $updatedMetrics, self::METRICS_CACHE_TTL);
    }

    /**
     * Get current query count from database connection.
     */
    private function getQueryCount(): int
    {
        return count(DB::getQueryLog());
    }

    /**
     * Calculate performance grade based on metrics.
     */
    private function calculatePerformanceGrade(array $metrics, array $cacheStats): string
    {
        $score = 0;
        
        // Execution time score (0-40 points)
        $avgTime = $metrics['avg_execution_time'] ?? 0;
        if ($avgTime <= 10) $score += 40;
        elseif ($avgTime <= 25) $score += 30;
        elseif ($avgTime <= 50) $score += 20;
        elseif ($avgTime <= 100) $score += 10;
        
        // Query count score (0-30 points)
        $avgQueries = $metrics['avg_query_count'] ?? 0;
        if ($avgQueries <= 1) $score += 30;
        elseif ($avgQueries <= 3) $score += 20;
        elseif ($avgQueries <= 5) $score += 10;
        
        // Cache hit rate score (0-30 points)
        $hitRate = $cacheStats['hit_rate'];
        if ($hitRate >= 0.9) $score += 30;
        elseif ($hitRate >= 0.8) $score += 25;
        elseif ($hitRate >= 0.7) $score += 20;
        elseif ($hitRate >= 0.5) $score += 10;
        
        return match (true) {
            $score >= 90 => 'A',
            $score >= 80 => 'B',
            $score >= 70 => 'C',
            $score >= 60 => 'D',
            default => 'F',
        };
    }

    /**
     * Generate optimization recommendation for a query.
     */
    private function generateQueryRecommendation(array $query): array
    {
        $recommendations = [];
        
        if ($query['execution_time'] > 200) {
            $recommendations[] = 'Consider adding database indexes';
        }
        
        if ($query['query_count'] > 10) {
            $recommendations[] = 'Implement eager loading to reduce N+1 queries';
        }
        
        return [
            'operation' => $query['operation'],
            'execution_time' => $query['execution_time'],
            'recommendations' => $recommendations,
        ];
    }

    /**
     * Identify common performance issues.
     */
    private function identifyCommonIssues(array $slowQueries): array
    {
        $issues = [];
        $operationCounts = [];
        
        foreach ($slowQueries as $query) {
            $operation = $query['operation'];
            $operationCounts[$operation] = ($operationCounts[$operation] ?? 0) + 1;
        }
        
        foreach ($operationCounts as $operation => $count) {
            if ($count > 5) {
                $issues[] = "Frequent slow queries in operation: {$operation}";
            }
        }
        
        return $issues;
    }
}