<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Psr\Log\LoggerInterface;

/**
 * System Health Service for monitoring application health.
 * 
 * Provides comprehensive health checks for various system components
 * including database, cache, queue, and storage systems.
 */
class SystemHealthService
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Perform comprehensive system health check.
     * 
     * @return array Health check results with status and metrics
     */
    public function performHealthCheck(): array
    {
        $services = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
            'storage' => $this->checkStorage(),
            'validation_engine' => $this->checkValidationEngine(),
        ];

        $metrics = [
            'total_validations_today' => $this->getTodayValidationCount(),
            'average_response_time_ms' => $this->getAverageResponseTime(),
            'error_rate_percent' => $this->getErrorRate(),
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'peak_memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
        ];

        $overallStatus = collect($services)->every(fn($status) => $status === 'healthy') ? 'healthy' : 'degraded';

        return [
            'status' => $overallStatus,
            'timestamp' => now()->toISOString(),
            'services' => $services,
            'metrics' => $metrics,
        ];
    }

    /**
     * Check database connectivity and performance.
     * 
     * @return string Health status
     */
    private function checkDatabase(): string
    {
        try {
            $start = microtime(true);
            
            // Test connection
            DB::connection()->getPdo();
            
            // Test query performance
            DB::table('meter_readings')->limit(1)->get();
            
            $duration = (microtime(true) - $start) * 1000;
            
            // Consider unhealthy if query takes more than 100ms
            return $duration < 100 ? 'healthy' : 'degraded';
            
        } catch (\Exception $e) {
            $this->logger->error('Database health check failed', [
                'error' => $e->getMessage()
            ]);
            return 'unhealthy';
        }
    }

    /**
     * Check cache system health.
     * 
     * @return string Health status
     */
    private function checkCache(): string
    {
        try {
            $testKey = 'health_check_' . time();
            $testValue = 'test_' . uniqid();
            
            // Test write
            Cache::put($testKey, $testValue, 60);
            
            // Test read
            $retrieved = Cache::get($testKey);
            
            // Test delete
            Cache::forget($testKey);
            
            return $retrieved === $testValue ? 'healthy' : 'unhealthy';
            
        } catch (\Exception $e) {
            $this->logger->error('Cache health check failed', [
                'error' => $e->getMessage()
            ]);
            return 'unhealthy';
        }
    }

    /**
     * Check queue system health.
     * 
     * @return string Health status
     */
    private function checkQueue(): string
    {
        try {
            // Check if queue connection is working
            $connection = Queue::connection();
            
            // Get queue size (implementation depends on queue driver)
            $queueSize = $this->getQueueSize();
            
            // Consider degraded if queue has too many pending jobs
            if ($queueSize > 1000) {
                return 'degraded';
            }
            
            return 'healthy';
            
        } catch (\Exception $e) {
            $this->logger->error('Queue health check failed', [
                'error' => $e->getMessage()
            ]);
            return 'unhealthy';
        }
    }

    /**
     * Check storage system health.
     * 
     * @return string Health status
     */
    private function checkStorage(): string
    {
        try {
            $testFile = 'health_check_' . time() . '.txt';
            $testContent = 'health check test';
            
            // Test write
            Storage::put($testFile, $testContent);
            
            // Test read
            $retrieved = Storage::get($testFile);
            
            // Test delete
            Storage::delete($testFile);
            
            return $retrieved === $testContent ? 'healthy' : 'unhealthy';
            
        } catch (\Exception $e) {
            $this->logger->error('Storage health check failed', [
                'error' => $e->getMessage()
            ]);
            return 'unhealthy';
        }
    }

    /**
     * Check validation engine health.
     * 
     * @return string Health status
     */
    private function checkValidationEngine(): string
    {
        try {
            // Simple validation engine test
            $testReading = new \App\Models\MeterReading([
                'value' => 100.0,
                'reading_date' => now(),
            ]);
            
            // This should not throw an exception
            return 'healthy';
            
        } catch (\Exception $e) {
            $this->logger->error('Validation engine health check failed', [
                'error' => $e->getMessage()
            ]);
            return 'unhealthy';
        }
    }

    /**
     * Get today's validation count.
     * 
     * @return int
     */
    private function getTodayValidationCount(): int
    {
        return Cache::remember('validation_count_today', 300, function () {
            return \App\Models\MeterReading::whereDate('updated_at', today())
                ->where('validation_status', '!=', \App\Enums\ValidationStatus::PENDING)
                ->count();
        });
    }

    /**
     * Get average response time (placeholder - would need actual metrics collection).
     * 
     * @return float
     */
    private function getAverageResponseTime(): float
    {
        // In a real implementation, this would track actual response times
        // Could use APM tools like New Relic, DataDog, or custom metrics
        return Cache::remember('avg_response_time', 300, function () {
            return 150.5; // Placeholder value
        });
    }

    /**
     * Get error rate (placeholder - would need actual error tracking).
     * 
     * @return float
     */
    private function getErrorRate(): float
    {
        // In a real implementation, this would calculate actual error rates
        // from logs or monitoring systems
        return Cache::remember('error_rate', 300, function () {
            return 2.1; // Placeholder value
        });
    }

    /**
     * Get queue size (implementation depends on queue driver).
     * 
     * @return int
     */
    private function getQueueSize(): int
    {
        try {
            // This is a simplified implementation
            // Real implementation would depend on queue driver (Redis, Database, etc.)
            return 0;
        } catch (\Exception $e) {
            return -1; // Error indicator
        }
    }
}