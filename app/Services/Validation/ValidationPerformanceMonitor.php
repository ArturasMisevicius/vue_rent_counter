<?php

declare(strict_types=1);

namespace App\Services\Validation;

use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;

/**
 * Performance monitoring service for validation operations.
 * 
 * Tracks and reports on validation performance metrics to identify
 * bottlenecks and optimization opportunities.
 */
final class ValidationPerformanceMonitor
{
    private array $metrics = [];
    private array $queryLog = [];
    private float $startTime;
    private int $startMemory;

    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Start performance monitoring for a validation operation.
     */
    public function startMonitoring(string $operation, array $context = []): void
    {
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage(true);
        
        // Enable query logging if not already enabled
        if (!DB::logging()) {
            DB::enableQueryLog();
            $this->queryLog = [];
        } else {
            $this->queryLog = DB::getQueryLog();
        }
        
        $this->metrics[$operation] = [
            'start_time' => $this->startTime,
            'start_memory' => $this->startMemory,
            'context' => $context,
        ];
    }

    /**
     * Stop monitoring and record metrics.
     */
    public function stopMonitoring(string $operation, array $additionalMetrics = []): array
    {
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);
        
        $currentQueries = DB::getQueryLog();
        $queryCount = count($currentQueries) - count($this->queryLog);
        
        $metrics = [
            'operation' => $operation,
            'duration_ms' => round(($endTime - $this->startTime) * 1000, 2),
            'memory_used_mb' => round(($endMemory - $this->startMemory) / 1024 / 1024, 2),
            'memory_peak_mb' => round($peakMemory / 1024 / 1024, 2),
            'query_count' => $queryCount,
            'timestamp' => now()->toISOString(),
        ];
        
        // Add any additional metrics
        $metrics = array_merge($metrics, $additionalMetrics);
        
        // Log performance metrics
        $this->logPerformanceMetrics($metrics);
        
        // Store for batch reporting
        $this->metrics[$operation]['end_metrics'] = $metrics;
        
        return $metrics;
    }

    /**
     * Monitor a validation operation with automatic start/stop.
     */
    public function monitor(string $operation, callable $callback, array $context = []): mixed
    {
        $this->startMonitoring($operation, $context);
        
        try {
            $result = $callback();
            
            $additionalMetrics = [];
            if (is_array($result) && isset($result['total_readings'])) {
                $additionalMetrics['readings_processed'] = $result['total_readings'];
                $additionalMetrics['readings_per_second'] = $this->calculateReadingsPerSecond(
                    $result['total_readings'],
                    microtime(true) - $this->startTime
                );
            }
            
            $this->stopMonitoring($operation, $additionalMetrics);
            
            return $result;
            
        } catch (\Exception $e) {
            $this->stopMonitoring($operation, [
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
            ]);
            
            throw $e;
        }
    }

    /**
     * Get performance summary for all monitored operations.
     */
    public function getPerformanceSummary(): array
    {
        $summary = [
            'total_operations' => count($this->metrics),
            'operations' => [],
            'aggregate_metrics' => [
                'total_duration_ms' => 0,
                'total_memory_mb' => 0,
                'total_queries' => 0,
                'average_duration_ms' => 0,
                'average_memory_mb' => 0,
                'average_queries' => 0,
            ],
        ];
        
        foreach ($this->metrics as $operation => $data) {
            if (isset($data['end_metrics'])) {
                $metrics = $data['end_metrics'];
                $summary['operations'][$operation] = $metrics;
                
                // Aggregate metrics
                $summary['aggregate_metrics']['total_duration_ms'] += $metrics['duration_ms'];
                $summary['aggregate_metrics']['total_memory_mb'] += $metrics['memory_used_mb'];
                $summary['aggregate_metrics']['total_queries'] += $metrics['query_count'];
            }
        }
        
        // Calculate averages
        if ($summary['total_operations'] > 0) {
            $summary['aggregate_metrics']['average_duration_ms'] = 
                $summary['aggregate_metrics']['total_duration_ms'] / $summary['total_operations'];
            $summary['aggregate_metrics']['average_memory_mb'] = 
                $summary['aggregate_metrics']['total_memory_mb'] / $summary['total_operations'];
            $summary['aggregate_metrics']['average_queries'] = 
                $summary['aggregate_metrics']['total_queries'] / $summary['total_operations'];
        }
        
        return $summary;
    }

    /**
     * Identify performance bottlenecks.
     */
    public function identifyBottlenecks(): array
    {
        $bottlenecks = [];
        
        foreach ($this->metrics as $operation => $data) {
            if (!isset($data['end_metrics'])) continue;
            
            $metrics = $data['end_metrics'];
            
            // Check for slow operations (>1 second)
            if ($metrics['duration_ms'] > 1000) {
                $bottlenecks[] = [
                    'type' => 'slow_operation',
                    'operation' => $operation,
                    'duration_ms' => $metrics['duration_ms'],
                    'severity' => $metrics['duration_ms'] > 5000 ? 'high' : 'medium',
                ];
            }
            
            // Check for high memory usage (>100MB)
            if ($metrics['memory_used_mb'] > 100) {
                $bottlenecks[] = [
                    'type' => 'high_memory_usage',
                    'operation' => $operation,
                    'memory_mb' => $metrics['memory_used_mb'],
                    'severity' => $metrics['memory_used_mb'] > 500 ? 'high' : 'medium',
                ];
            }
            
            // Check for excessive queries (>50 queries)
            if ($metrics['query_count'] > 50) {
                $bottlenecks[] = [
                    'type' => 'excessive_queries',
                    'operation' => $operation,
                    'query_count' => $metrics['query_count'],
                    'severity' => $metrics['query_count'] > 100 ? 'high' : 'medium',
                ];
            }
            
            // Check for low throughput in batch operations
            if (isset($metrics['readings_per_second']) && $metrics['readings_per_second'] < 10) {
                $bottlenecks[] = [
                    'type' => 'low_throughput',
                    'operation' => $operation,
                    'readings_per_second' => $metrics['readings_per_second'],
                    'severity' => $metrics['readings_per_second'] < 5 ? 'high' : 'medium',
                ];
            }
        }
        
        return $bottlenecks;
    }

    /**
     * Generate performance recommendations.
     */
    public function getRecommendations(): array
    {
        $bottlenecks = $this->identifyBottlenecks();
        $recommendations = [];
        
        foreach ($bottlenecks as $bottleneck) {
            $recommendations[] = match ($bottleneck['type']) {
                'slow_operation' => [
                    'issue' => "Operation '{$bottleneck['operation']}' is slow ({$bottleneck['duration_ms']}ms)",
                    'recommendation' => 'Consider adding caching, optimizing queries, or implementing async processing',
                    'priority' => $bottleneck['severity'],
                ],
                'high_memory_usage' => [
                    'issue' => "Operation '{$bottleneck['operation']}' uses high memory ({$bottleneck['memory_mb']}MB)",
                    'recommendation' => 'Implement chunked processing, reduce data loading, or use streaming',
                    'priority' => $bottleneck['severity'],
                ],
                'excessive_queries' => [
                    'issue' => "Operation '{$bottleneck['operation']}' executes many queries ({$bottleneck['query_count']})",
                    'recommendation' => 'Add eager loading, implement bulk operations, or use query optimization',
                    'priority' => $bottleneck['severity'],
                ],
                'low_throughput' => [
                    'issue' => "Operation '{$bottleneck['operation']}' has low throughput ({$bottleneck['readings_per_second']} readings/sec)",
                    'recommendation' => 'Optimize batch processing, add parallel processing, or improve caching',
                    'priority' => $bottleneck['severity'],
                ],
                default => [
                    'issue' => "Unknown performance issue in '{$bottleneck['operation']}'",
                    'recommendation' => 'Review operation implementation for optimization opportunities',
                    'priority' => 'medium',
                ],
            };
        }
        
        return $recommendations;
    }

    /**
     * Reset monitoring data.
     */
    public function reset(): void
    {
        $this->metrics = [];
        $this->queryLog = [];
    }

    /**
     * Calculate readings per second throughput.
     */
    private function calculateReadingsPerSecond(int $readingCount, float $durationSeconds): float
    {
        return $durationSeconds > 0 ? round($readingCount / $durationSeconds, 2) : 0;
    }

    /**
     * Log performance metrics.
     */
    private function logPerformanceMetrics(array $metrics): void
    {
        $this->logger->info('Validation performance metrics', $metrics);
        
        // Log warnings for performance issues
        if ($metrics['duration_ms'] > 5000) {
            $this->logger->warning('Slow validation operation detected', [
                'operation' => $metrics['operation'],
                'duration_ms' => $metrics['duration_ms'],
            ]);
        }
        
        if ($metrics['query_count'] > 100) {
            $this->logger->warning('High query count in validation operation', [
                'operation' => $metrics['operation'],
                'query_count' => $metrics['query_count'],
            ]);
        }
    }
}