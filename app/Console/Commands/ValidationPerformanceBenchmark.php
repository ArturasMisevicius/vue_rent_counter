<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\MeterReading;
use App\Services\ServiceValidationEngine;
use App\Services\Validation\ValidationPerformanceMonitor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Command to run validation performance benchmarks.
 * 
 * Usage:
 * php artisan validation:benchmark
 * php artisan validation:benchmark --readings=100 --iterations=5
 */
class ValidationPerformanceBenchmark extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'validation:benchmark 
                            {--readings=50 : Number of readings to validate}
                            {--iterations=3 : Number of benchmark iterations}
                            {--warmup=true : Enable cache warmup}
                            {--report=true : Generate detailed report}';

    /**
     * The console command description.
     */
    protected $description = 'Run performance benchmarks for the validation engine';

    /**
     * Execute the console command.
     */
    public function handle(
        ServiceValidationEngine $validationEngine,
        ValidationPerformanceMonitor $performanceMonitor
    ): int {
        $readingCount = (int) $this->option('readings');
        $iterations = (int) $this->option('iterations');
        $enableWarmup = $this->option('warmup') !== 'false';
        $generateReport = $this->option('report') !== 'false';

        $this->info("Starting validation performance benchmark");
        $this->info("Readings per iteration: {$readingCount}");
        $this->info("Iterations: {$iterations}");
        $this->info("Cache warmup: " . ($enableWarmup ? 'enabled' : 'disabled'));

        // Prepare test data
        $this->info("Preparing test data...");
        $testReadings = $this->prepareTestData($readingCount);
        
        if ($testReadings->isEmpty()) {
            $this->error("No test data available. Please ensure you have meter readings in the database.");
            return 1;
        }

        $this->info("Test data prepared: {$testReadings->count()} readings");

        // Warmup cache if enabled
        if ($enableWarmup) {
            $this->info("Warming up cache...");
            $this->warmupCache($validationEngine, $testReadings->take(10));
        }

        // Run benchmark iterations
        $results = [];
        $progressBar = $this->output->createProgressBar($iterations);
        $progressBar->start();

        for ($i = 1; $i <= $iterations; $i++) {
            // Enable query logging for this iteration
            DB::flushQueryLog();
            DB::enableQueryLog();

            $iterationResult = $performanceMonitor->monitor(
                "benchmark_iteration_{$i}",
                function () use ($validationEngine, $testReadings) {
                    return $validationEngine->batchValidateReadings($testReadings);
                },
                [
                    'reading_count' => $testReadings->count(),
                    'iteration' => $i,
                ]
            );

            $results[] = $iterationResult;
            $progressBar->advance();

            // Small delay between iterations
            usleep(100000); // 100ms
        }

        $progressBar->finish();
        $this->newLine(2);

        // Analyze results
        $this->analyzeResults($performanceMonitor, $results, $readingCount);

        // Generate detailed report if requested
        if ($generateReport) {
            $this->generateDetailedReport($performanceMonitor, $results);
        }

        // Check for performance regressions
        $this->checkPerformanceRegressions($performanceMonitor);

        $this->info("Benchmark completed successfully!");
        return 0;
    }

    /**
     * Prepare test data for benchmarking.
     */
    private function prepareTestData(int $count): \Illuminate\Support\Collection
    {
        // Get existing readings for realistic testing
        $readings = MeterReading::with([
            'meter.serviceConfiguration.utilityService',
            'meter.serviceConfiguration.tariff',
            'meter.serviceConfiguration.provider'
        ])
        ->inRandomOrder()
        ->limit($count)
        ->get();

        return $readings;
    }

    /**
     * Warmup cache with sample data.
     */
    private function warmupCache(ServiceValidationEngine $validationEngine, \Illuminate\Support\Collection $sampleReadings): void
    {
        // Validate a few readings to warm up the cache
        foreach ($sampleReadings as $reading) {
            $validationEngine->validateMeterReading($reading);
        }
    }

    /**
     * Analyze benchmark results and display summary.
     */
    private function analyzeResults(
        ValidationPerformanceMonitor $performanceMonitor, 
        array $results, 
        int $readingCount
    ): void {
        $summary = $performanceMonitor->getPerformanceSummary();
        
        // Calculate statistics
        $durations = [];
        $queryCount = [];
        $memoryUsage = [];
        $throughput = [];

        foreach ($summary['operations'] as $operation => $metrics) {
            if (str_starts_with($operation, 'benchmark_iteration_')) {
                $durations[] = $metrics['duration_ms'];
                $queryCount[] = $metrics['query_count'];
                $memoryUsage[] = $metrics['memory_used_mb'];
                
                if (isset($metrics['readings_per_second'])) {
                    $throughput[] = $metrics['readings_per_second'];
                }
            }
        }

        // Display results table
        $this->table(
            ['Metric', 'Average', 'Min', 'Max', 'Target', 'Status'],
            [
                [
                    'Duration (ms)',
                    round(array_sum($durations) / count($durations), 2),
                    min($durations),
                    max($durations),
                    '< 100ms',
                    max($durations) < 100 ? '✅ PASS' : '❌ FAIL'
                ],
                [
                    'Queries per Reading',
                    round(array_sum($queryCount) / count($queryCount) / $readingCount, 2),
                    round(min($queryCount) / $readingCount, 2),
                    round(max($queryCount) / $readingCount, 2),
                    '< 2.0',
                    max($queryCount) / $readingCount < 2.0 ? '✅ PASS' : '❌ FAIL'
                ],
                [
                    'Memory Usage (MB)',
                    round(array_sum($memoryUsage) / count($memoryUsage), 2),
                    min($memoryUsage),
                    max($memoryUsage),
                    '< 50MB',
                    max($memoryUsage) < 50 ? '✅ PASS' : '❌ FAIL'
                ],
                [
                    'Throughput (readings/sec)',
                    !empty($throughput) ? round(array_sum($throughput) / count($throughput), 2) : 'N/A',
                    !empty($throughput) ? min($throughput) : 'N/A',
                    !empty($throughput) ? max($throughput) : 'N/A',
                    '> 20',
                    !empty($throughput) && min($throughput) > 20 ? '✅ PASS' : '❌ FAIL'
                ],
            ]
        );
    }

    /**
     * Generate detailed performance report.
     */
    private function generateDetailedReport(ValidationPerformanceMonitor $performanceMonitor, array $results): void
    {
        $this->info("\n=== DETAILED PERFORMANCE REPORT ===");
        
        $summary = $performanceMonitor->getPerformanceSummary();
        
        $this->info("Total Operations: {$summary['total_operations']}");
        $this->info("Average Duration: {$summary['aggregate_metrics']['average_duration_ms']}ms");
        $this->info("Average Memory: {$summary['aggregate_metrics']['average_memory_mb']}MB");
        $this->info("Average Queries: {$summary['aggregate_metrics']['average_queries']}");

        // Show individual iteration results
        $this->info("\n--- Individual Iteration Results ---");
        foreach ($summary['operations'] as $operation => $metrics) {
            if (str_starts_with($operation, 'benchmark_iteration_')) {
                $iteration = str_replace('benchmark_iteration_', '', $operation);
                $this->line("Iteration {$iteration}: {$metrics['duration_ms']}ms, {$metrics['query_count']} queries, {$metrics['memory_used_mb']}MB");
            }
        }

        // Show bottlenecks and recommendations
        $bottlenecks = $performanceMonitor->identifyBottlenecks();
        if (!empty($bottlenecks)) {
            $this->warn("\n--- Performance Bottlenecks Detected ---");
            foreach ($bottlenecks as $bottleneck) {
                $this->line("• {$bottleneck['type']}: {$bottleneck['operation']} (severity: {$bottleneck['severity']})");
            }
        }

        $recommendations = $performanceMonitor->getRecommendations();
        if (!empty($recommendations)) {
            $this->info("\n--- Performance Recommendations ---");
            foreach ($recommendations as $recommendation) {
                $this->line("• [{$recommendation['priority']}] {$recommendation['issue']}");
                $this->line("  → {$recommendation['recommendation']}");
            }
        }
    }

    /**
     * Check for performance regressions against baseline.
     */
    private function checkPerformanceRegressions(ValidationPerformanceMonitor $performanceMonitor): void
    {
        $bottlenecks = $performanceMonitor->identifyBottlenecks();
        $highSeverityIssues = array_filter($bottlenecks, fn($b) => $b['severity'] === 'high');

        if (!empty($highSeverityIssues)) {
            $this->error("\n❌ PERFORMANCE REGRESSION DETECTED!");
            $this->error("High-severity performance issues found:");
            
            foreach ($highSeverityIssues as $issue) {
                $this->error("• {$issue['type']}: {$issue['operation']}");
            }
            
            $this->error("Please review the performance optimizations and fix these issues.");
        } else {
            $this->info("\n✅ No performance regressions detected.");
        }
    }
}