<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\ServiceValidationEngine;
use App\Models\MeterReading;

/**
 * Performance Monitoring Command for ServiceValidationEngine
 * 
 * This command provides comprehensive performance monitoring and N+1 query detection
 * for the ServiceValidationEngine in production environments.
 */
class PerformanceMonitoringCommand extends Command
{
    protected $signature = 'performance:monitor 
                           {--baseline : Generate performance baseline}
                           {--analyze : Analyze current performance}
                           {--compare : Compare with baseline}
                           {--detect-n1 : Detect N+1 query patterns}
                           {--sample-size=100 : Number of readings to test with}
                           {--output= : Output file for results}';

    protected $description = 'Monitor ServiceValidationEngine performance and detect N+1 queries';

    public function handle(): int
    {
        $this->info('🚀 ServiceValidationEngine Performance Monitor');
        $this->newLine();

        if ($this->option('baseline')) {
            return $this->generateBaseline();
        }

        if ($this->option('analyze')) {
            return $this->analyzePerformance();
        }

        if ($this->option('compare')) {
            return $this->compareWithBaseline();
        }

        if ($this->option('detect-n1')) {
            return $this->detectN1Queries();
        }

        // Default: Run all analyses
        $this->runComprehensiveAnalysis();
        return 0;
    }

    /**
     * Generate performance baseline
     */
    private function generateBaseline(): int
    {
        $this->info('📊 Generating Performance Baseline...');
        
        $sampleSize = (int) $this->option('sample-size');
        $validationEngine = app(ServiceValidationEngine::class);
        
        // Create test data
        $readings = MeterReading::with(['meter.serviceConfiguration'])
            ->limit($sampleSize)
            ->get();

        if ($readings->isEmpty()) {
            $this->error('No readings found for baseline generation');
            return 1;
        }

        // Measure performance
        $metrics = $this->measurePerformance($validationEngine, $readings);
        
        // Save baseline
        $baseline = [
            'timestamp' => now()->toISOString(),
            'sample_size' => $sampleSize,
            'metrics' => $metrics,
            'environment' => app()->environment(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ];

        $outputFile = $this->option('output') ?: 'performance-baseline.json';
        file_put_contents($outputFile, json_encode($baseline, JSON_PRETTY_PRINT));

        $this->info("✅ Baseline saved to: {$outputFile}");
        $this->displayMetrics($metrics);

        return 0;
    }

    /**
     * Analyze current performance
     */
    private function analyzePerformance(): int
    {
        $this->info('🔍 Analyzing Current Performance...');
        
        $sampleSize = (int) $this->option('sample-size');
        $validationEngine = app(ServiceValidationEngine::class);
        
        $readings = MeterReading::with(['meter.serviceConfiguration'])
            ->limit($sampleSize)
            ->get();

        if ($readings->isEmpty()) {
            $this->error('No readings found for analysis');
            return 1;
        }

        $metrics = $this->measurePerformance($validationEngine, $readings);
        
        $this->displayMetrics($metrics);
        $this->analyzeQueryPatterns($metrics['queries']);
        $this->checkPerformanceThresholds($metrics);

        return 0;
    }

    /**
     * Compare with baseline
     */
    private function compareWithBaseline(): int
    {
        $this->info('📈 Comparing with Baseline...');
        
        $baselineFile = 'performance-baseline.json';
        if (!file_exists($baselineFile)) {
            $this->error('Baseline file not found. Run with --baseline first.');
            return 1;
        }

        $baseline = json_decode(file_get_contents($baselineFile), true);
        
        // Generate current metrics
        $sampleSize = (int) $this->option('sample-size');
        $validationEngine = app(ServiceValidationEngine::class);
        
        $readings = MeterReading::with(['meter.serviceConfiguration'])
            ->limit($sampleSize)
            ->get();

        $currentMetrics = $this->measurePerformance($validationEngine, $readings);
        
        $this->displayComparison($baseline['metrics'], $currentMetrics);

        return 0;
    }

    /**
     * Detect N+1 query patterns
     */
    private function detectN1Queries(): int
    {
        $this->info('🔍 Detecting N+1 Query Patterns...');
        
        $sampleSize = (int) $this->option('sample-size');
        $validationEngine = app(ServiceValidationEngine::class);
        
        // Test with different batch sizes to detect N+1 patterns
        $testSizes = [10, 25, 50, 100];
        $results = [];

        foreach ($testSizes as $size) {
            if ($size > $sampleSize) continue;
            
            $readings = MeterReading::with(['meter.serviceConfiguration'])
                ->limit($size)
                ->get();

            $metrics = $this->measurePerformance($validationEngine, $readings);
            $results[$size] = $metrics;
        }

        $this->analyzeN1Patterns($results);

        return 0;
    }

    /**
     * Run comprehensive analysis
     */
    private function runComprehensiveAnalysis(): void
    {
        $this->info('🔬 Running Comprehensive Performance Analysis...');
        
        $this->analyzePerformance();
        $this->newLine();
        $this->detectN1Queries();
        $this->newLine();
        $this->checkSystemHealth();
    }

    /**
     * Measure performance metrics
     */
    private function measurePerformance(ServiceValidationEngine $validationEngine, $readings): array
    {
        // Clear query log
        DB::flushQueryLog();
        DB::enableQueryLog();
        
        // Measure memory before
        $memoryBefore = memory_get_usage(true);
        $peakMemoryBefore = memory_get_peak_usage(true);
        
        // Measure execution time
        $startTime = microtime(true);
        
        // Execute validation
        $result = $validationEngine->batchValidateReadings($readings);
        
        $endTime = microtime(true);
        
        // Measure memory after
        $memoryAfter = memory_get_usage(true);
        $peakMemoryAfter = memory_get_peak_usage(true);
        
        // Get query log
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        return [
            'execution_time' => [
                'duration_seconds' => round($endTime - $startTime, 4),
                'duration_ms' => round(($endTime - $startTime) * 1000, 2),
            ],
            'memory' => [
                'used_mb' => round(($memoryAfter - $memoryBefore) / 1024 / 1024, 2),
                'peak_mb' => round($peakMemoryAfter / 1024 / 1024, 2),
            ],
            'queries' => [
                'total_count' => count($queries),
                'queries_per_reading' => count($readings) > 0 ? round(count($queries) / count($readings), 2) : 0,
                'total_time_ms' => round(collect($queries)->sum('time'), 2),
                'average_time_ms' => count($queries) > 0 ? round(collect($queries)->avg('time'), 2) : 0,
                'slowest_query_ms' => count($queries) > 0 ? round(collect($queries)->max('time'), 2) : 0,
                'query_details' => $queries,
            ],
            'validation_results' => [
                'total_readings' => $result['total_readings'] ?? 0,
                'valid_readings' => $result['valid_readings'] ?? 0,
                'invalid_readings' => $result['invalid_readings'] ?? 0,
            ],
            'sample_size' => count($readings),
        ];
    }

    /**
     * Display performance metrics
     */
    private function displayMetrics(array $metrics): void
    {
        $this->table(
            ['Metric', 'Value'],
            [
                ['Sample Size', $metrics['sample_size']],
                ['Execution Time', $metrics['execution_time']['duration_ms'] . ' ms'],
                ['Memory Used', $metrics['memory']['used_mb'] . ' MB'],
                ['Peak Memory', $metrics['memory']['peak_mb'] . ' MB'],
                ['Total Queries', $metrics['queries']['total_count']],
                ['Queries per Reading', $metrics['queries']['queries_per_reading']],
                ['Query Time', $metrics['queries']['total_time_ms'] . ' ms'],
                ['Average Query Time', $metrics['queries']['average_time_ms'] . ' ms'],
                ['Slowest Query', $metrics['queries']['slowest_query_ms'] . ' ms'],
            ]
        );
    }

    /**
     * Analyze query patterns for N+1 detection
     */
    private function analyzeQueryPatterns(array $queryMetrics): void
    {
        $this->info('🔍 Query Pattern Analysis:');
        
        $queries = $queryMetrics['query_details'];
        $queryPatterns = [];
        
        foreach ($queries as $query) {
            $sql = $query['query'];
            
            // Normalize query for pattern detection
            $pattern = preg_replace('/\b\d+\b/', '?', $sql);
            $pattern = preg_replace('/\s+/', ' ', $pattern);
            
            if (!isset($queryPatterns[$pattern])) {
                $queryPatterns[$pattern] = [
                    'count' => 0,
                    'total_time' => 0,
                    'example' => $sql,
                ];
            }
            
            $queryPatterns[$pattern]['count']++;
            $queryPatterns[$pattern]['total_time'] += $query['time'];
        }
        
        // Sort by frequency
        uasort($queryPatterns, fn($a, $b) => $b['count'] <=> $a['count']);
        
        $this->table(
            ['Pattern', 'Count', 'Total Time (ms)', 'Avg Time (ms)'],
            collect($queryPatterns)->take(10)->map(function ($data, $pattern) {
                return [
                    substr($pattern, 0, 80) . (strlen($pattern) > 80 ? '...' : ''),
                    $data['count'],
                    round($data['total_time'], 2),
                    round($data['total_time'] / $data['count'], 2),
                ];
            })->toArray()
        );
        
        // Detect potential N+1 patterns
        $suspiciousPatterns = collect($queryPatterns)->filter(function ($data) {
            return $data['count'] > 5; // More than 5 similar queries might indicate N+1
        });
        
        if ($suspiciousPatterns->isNotEmpty()) {
            $this->warn('⚠️  Potential N+1 Query Patterns Detected:');
            foreach ($suspiciousPatterns->take(3) as $pattern => $data) {
                $this->line("  • {$data['count']} similar queries: " . substr($pattern, 0, 100));
            }
        } else {
            $this->info('✅ No obvious N+1 query patterns detected');
        }
    }

    /**
     * Check performance thresholds
     */
    private function checkPerformanceThresholds(array $metrics): void
    {
        $this->info('🎯 Performance Threshold Check:');
        
        $thresholds = [
            'queries_per_reading' => ['value' => $metrics['queries']['queries_per_reading'], 'threshold' => 0.2, 'unit' => ''],
            'duration_ms' => ['value' => $metrics['execution_time']['duration_ms'], 'threshold' => 1000, 'unit' => 'ms'],
            'memory_mb' => ['value' => $metrics['memory']['peak_mb'], 'threshold' => 100, 'unit' => 'MB'],
            'slowest_query_ms' => ['value' => $metrics['queries']['slowest_query_ms'], 'threshold' => 100, 'unit' => 'ms'],
        ];
        
        foreach ($thresholds as $name => $data) {
            $status = $data['value'] <= $data['threshold'] ? '✅' : '❌';
            $this->line("  {$status} {$name}: {$data['value']}{$data['unit']} (threshold: {$data['threshold']}{$data['unit']})");
        }
    }

    /**
     * Analyze N+1 patterns across different batch sizes
     */
    private function analyzeN1Patterns(array $results): void
    {
        $this->info('📊 N+1 Pattern Analysis:');
        
        $tableData = [];
        foreach ($results as $size => $metrics) {
            $tableData[] = [
                $size,
                $metrics['queries']['total_count'],
                $metrics['queries']['queries_per_reading'],
                $metrics['execution_time']['duration_ms'],
            ];
        }
        
        $this->table(
            ['Batch Size', 'Total Queries', 'Queries/Reading', 'Duration (ms)'],
            $tableData
        );
        
        // Analyze scaling patterns
        if (count($results) >= 2) {
            $sizes = array_keys($results);
            $firstSize = $sizes[0];
            $lastSize = end($sizes);
            
            $firstQueries = $results[$firstSize]['queries']['queries_per_reading'];
            $lastQueries = $results[$lastSize]['queries']['queries_per_reading'];
            
            if ($lastQueries > $firstQueries * 1.5) {
                $this->warn("⚠️  Query count scaling detected: {$firstQueries} → {$lastQueries} queries per reading");
                $this->line("   This may indicate N+1 query problems");
            } else {
                $this->info("✅ Query scaling looks good: {$firstQueries} → {$lastQueries} queries per reading");
            }
        }
    }

    /**
     * Display comparison with baseline
     */
    private function displayComparison(array $baseline, array $current): void
    {
        $this->info('📈 Performance Comparison:');
        
        $comparisons = [
            ['Metric', 'Baseline', 'Current', 'Change'],
            [
                'Execution Time (ms)',
                $baseline['execution_time']['duration_ms'],
                $current['execution_time']['duration_ms'],
                $this->formatChange($baseline['execution_time']['duration_ms'], $current['execution_time']['duration_ms'], 'ms')
            ],
            [
                'Total Queries',
                $baseline['queries']['total_count'],
                $current['queries']['total_count'],
                $this->formatChange($baseline['queries']['total_count'], $current['queries']['total_count'])
            ],
            [
                'Queries per Reading',
                $baseline['queries']['queries_per_reading'],
                $current['queries']['queries_per_reading'],
                $this->formatChange($baseline['queries']['queries_per_reading'], $current['queries']['queries_per_reading'])
            ],
            [
                'Memory Usage (MB)',
                $baseline['memory']['peak_mb'],
                $current['memory']['peak_mb'],
                $this->formatChange($baseline['memory']['peak_mb'], $current['memory']['peak_mb'], 'MB')
            ],
        ];
        
        $this->table($comparisons[0], array_slice($comparisons, 1));
    }

    /**
     * Format change percentage
     */
    private function formatChange(float $baseline, float $current, string $unit = ''): string
    {
        if ($baseline == 0) return 'N/A';
        
        $change = (($current - $baseline) / $baseline) * 100;
        $symbol = $change > 0 ? '+' : '';
        $color = abs($change) > 10 ? ($change > 0 ? 'red' : 'green') : 'yellow';
        
        return "<fg={$color}>{$symbol}" . round($change, 1) . "%</fg>";
    }

    /**
     * Check system health
     */
    private function checkSystemHealth(): void
    {
        $this->info('🏥 System Health Check:');
        
        // Check cache
        try {
            Cache::put('health_check', 'ok', 60);
            $cacheStatus = Cache::get('health_check') === 'ok' ? '✅' : '❌';
        } catch (\Exception $e) {
            $cacheStatus = '❌';
        }
        
        // Check database
        try {
            DB::connection()->getPdo();
            $dbStatus = '✅';
        } catch (\Exception $e) {
            $dbStatus = '❌';
        }
        
        // Check memory
        $memoryUsage = memory_get_usage(true) / 1024 / 1024;
        $memoryLimit = ini_get('memory_limit');
        $memoryStatus = $memoryUsage < 100 ? '✅' : '⚠️';
        
        $this->line("  Cache: {$cacheStatus}");
        $this->line("  Database: {$dbStatus}");
        $this->line("  Memory: {$memoryStatus} " . round($memoryUsage, 1) . "MB / {$memoryLimit}");
    }
}