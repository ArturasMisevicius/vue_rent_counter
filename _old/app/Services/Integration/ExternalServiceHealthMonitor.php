<?php

declare(strict_types=1);

namespace App\Services\Integration;

use App\Enums\IntegrationStatus;
use App\Traits\LogsTenantOperations;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Monitors the health and performance of external service integrations.
 * 
 * This service tracks service availability, response times, error rates,
 * and provides health status information for the circuit breaker system.
 * 
 * @package App\Services\Integration
 * @author Laravel Development Team
 * @since 1.0.0
 */
final readonly class ExternalServiceHealthMonitor
{
    use LogsTenantOperations;

    private const HEALTH_CHECK_TIMEOUT = 10; // 10 seconds
    private const HEALTH_HISTORY_RETENTION = 7; // 7 days
    private const PERFORMANCE_THRESHOLD_MS = 5000; // 5 seconds
    private const ERROR_RATE_THRESHOLD = 0.1; // 10%
    private const CACHE_TTL = 300; // 5 minutes

    /**
     * Service endpoint configurations.
     * 
     * @var array<string, array<string, mixed>>
     */
    private const SERVICE_ENDPOINTS = [
        'meter_reading_api' => [
            'url' => 'https://api.meter-provider.com/health',
            'method' => 'GET',
            'timeout' => 10,
            'expected_status' => 200,
        ],
        'utility_provider_api' => [
            'url' => 'https://api.utility-provider.com/status',
            'method' => 'GET',
            'timeout' => 15,
            'expected_status' => 200,
        ],
        'billing_integration' => [
            'url' => 'https://billing.external-service.com/ping',
            'method' => 'GET',
            'timeout' => 10,
            'expected_status' => 200,
        ],
        'ocr_service' => [
            'url' => 'https://ocr.service-provider.com/health',
            'method' => 'POST',
            'timeout' => 20,
            'expected_status' => 200,
            'payload' => ['test' => true],
        ],
    ];

    /**
     * Record successful operation for a service.
     * 
     * @param string $serviceName The service name
     * @param int $responseTimeMs Response time in milliseconds
     */
    public function recordSuccess(string $serviceName, int $responseTimeMs = 0): void
    {
        $this->recordHealthCheck($serviceName, IntegrationStatus::HEALTHY, $responseTimeMs);
        
        Log::debug("Service operation successful", [
            'service' => $serviceName,
            'response_time_ms' => $responseTimeMs,
        ]);
    }

    /**
     * Record failed operation for a service.
     * 
     * @param string $serviceName The service name
     * @param Throwable $exception The failure exception
     */
    public function recordFailure(string $serviceName, Throwable $exception): void
    {
        $status = $this->determineStatusFromException($exception);
        $this->recordHealthCheck($serviceName, $status, 0, $exception->getMessage());
        
        Log::warning("Service operation failed", [
            'service' => $serviceName,
            'error' => $exception->getMessage(),
            'status' => $status->value,
        ]);
    }

    /**
     * Record circuit breaker open event.
     * 
     * @param string $serviceName The service name
     */
    public function recordCircuitOpen(string $serviceName): void
    {
        $this->recordHealthCheck($serviceName, IntegrationStatus::CIRCUIT_OPEN, 0, 'Circuit breaker opened');
        
        Log::warning("Circuit breaker opened", [
            'service' => $serviceName,
        ]);
    }

    /**
     * Perform health check for a specific service.
     * 
     * @param string $serviceName The service name
     * 
     * @return array<string, mixed> Health check result
     */
    public function performHealthCheck(string $serviceName): array
    {
        $startTime = microtime(true);
        
        try {
            $config = self::SERVICE_ENDPOINTS[$serviceName] ?? null;
            
            if (!$config) {
                return [
                    'service' => $serviceName,
                    'status' => IntegrationStatus::UNKNOWN,
                    'response_time_ms' => 0,
                    'error' => 'Service configuration not found',
                    'checked_at' => now()->toISOString(),
                ];
            }

            $response = $this->makeHealthCheckRequest($config);
            $responseTime = (int) ((microtime(true) - $startTime) * 1000);
            
            $status = $response['success'] 
                ? $this->determineStatusFromResponseTime($responseTime)
                : IntegrationStatus::UNHEALTHY;
            
            $this->recordHealthCheck($serviceName, $status, $responseTime, $response['error'] ?? null);
            
            return [
                'service' => $serviceName,
                'status' => $status,
                'response_time_ms' => $responseTime,
                'error' => $response['error'] ?? null,
                'checked_at' => now()->toISOString(),
            ];
        } catch (Throwable $e) {
            $responseTime = (int) ((microtime(true) - $startTime) * 1000);
            $this->recordHealthCheck($serviceName, IntegrationStatus::UNHEALTHY, $responseTime, $e->getMessage());
            
            return [
                'service' => $serviceName,
                'status' => IntegrationStatus::UNHEALTHY,
                'response_time_ms' => $responseTime,
                'error' => $e->getMessage(),
                'checked_at' => now()->toISOString(),
            ];
        }
    }

    /**
     * Get health status for all monitored services.
     * 
     * @return array<string, array<string, mixed>> Service health data
     */
    public function getAllServicesStatus(): array
    {
        $services = [];
        
        foreach (array_keys(self::SERVICE_ENDPOINTS) as $serviceName) {
            $services[$serviceName] = $this->getServiceStatus($serviceName);
        }
        
        return $services;
    }

    /**
     * Get health status for a specific service.
     * 
     * @param string $serviceName The service name
     * 
     * @return array<string, mixed> Service health data
     */
    public function getServiceStatus(string $serviceName): array
    {
        $cacheKey = "service_health:{$serviceName}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($serviceName) {
            $latestCheck = DB::table('integration_health_checks')
                ->where('service_name', $serviceName)
                ->orderBy('checked_at', 'desc')
                ->first();
            
            if (!$latestCheck) {
                return [
                    'service' => $serviceName,
                    'status' => IntegrationStatus::UNKNOWN,
                    'response_time_ms' => 0,
                    'error' => null,
                    'last_checked' => null,
                    'uptime_percentage' => 0,
                    'avg_response_time_ms' => 0,
                ];
            }
            
            $metrics = $this->calculateServiceMetrics($serviceName);
            
            return [
                'service' => $serviceName,
                'status' => IntegrationStatus::from($latestCheck->status),
                'response_time_ms' => $latestCheck->response_time_ms,
                'error' => $latestCheck->error_message,
                'last_checked' => $latestCheck->checked_at,
                'uptime_percentage' => $metrics['uptime_percentage'],
                'avg_response_time_ms' => $metrics['avg_response_time_ms'],
                'error_rate' => $metrics['error_rate'],
            ];
        });
    }

    /**
     * Get service health history.
     * 
     * @param string $serviceName The service name
     * @param int $hours Number of hours to retrieve
     * 
     * @return array<array<string, mixed>> Health check history
     */
    public function getServiceHistory(string $serviceName, int $hours = 24): array
    {
        return DB::table('integration_health_checks')
            ->where('service_name', $serviceName)
            ->where('checked_at', '>=', now()->subHours($hours))
            ->orderBy('checked_at', 'desc')
            ->get()
            ->map(function ($check) {
                return [
                    'status' => IntegrationStatus::from($check->status),
                    'response_time_ms' => $check->response_time_ms,
                    'error' => $check->error_message,
                    'checked_at' => $check->checked_at,
                ];
            })
            ->toArray();
    }

    /**
     * Clean up old health check records.
     */
    public function cleanupOldRecords(): int
    {
        $cutoffDate = now()->subDays(self::HEALTH_HISTORY_RETENTION);
        
        return DB::table('integration_health_checks')
            ->where('checked_at', '<', $cutoffDate)
            ->delete();
    }

    /**
     * Record health check result in database.
     * 
     * @param string $serviceName The service name
     * @param IntegrationStatus $status Health status
     * @param int $responseTimeMs Response time in milliseconds
     * @param string|null $errorMessage Optional error message
     */
    private function recordHealthCheck(
        string $serviceName,
        IntegrationStatus $status,
        int $responseTimeMs,
        ?string $errorMessage = null
    ): void {
        try {
            DB::table('integration_health_checks')->insert([
                'service_name' => $serviceName,
                'endpoint' => self::SERVICE_ENDPOINTS[$serviceName]['url'] ?? 'unknown',
                'status' => $status->value,
                'response_time_ms' => $responseTimeMs,
                'error_message' => $errorMessage,
                'checked_at' => now(),
            ]);
            
            // Clear cache to force refresh
            Cache::forget("service_health:{$serviceName}");
        } catch (Throwable $e) {
            Log::error("Failed to record health check", [
                'service' => $serviceName,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Make HTTP request for health check.
     * 
     * @param array<string, mixed> $config Service configuration
     * 
     * @return array<string, mixed> Request result
     */
    private function makeHealthCheckRequest(array $config): array
    {
        try {
            $http = Http::timeout($config['timeout'] ?? self::HEALTH_CHECK_TIMEOUT);
            
            $response = match ($config['method']) {
                'GET' => $http->get($config['url']),
                'POST' => $http->post($config['url'], $config['payload'] ?? []),
                'PUT' => $http->put($config['url'], $config['payload'] ?? []),
                default => throw new \InvalidArgumentException("Unsupported HTTP method: {$config['method']}"),
            };
            
            $expectedStatus = $config['expected_status'] ?? 200;
            
            if ($response->status() === $expectedStatus) {
                return ['success' => true];
            } else {
                return [
                    'success' => false,
                    'error' => "Unexpected status code: {$response->status()}",
                ];
            }
        } catch (Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Determine status from exception type.
     */
    private function determineStatusFromException(Throwable $exception): IntegrationStatus
    {
        $message = strtolower($exception->getMessage());
        
        if (str_contains($message, 'timeout') || str_contains($message, 'connection')) {
            return IntegrationStatus::UNHEALTHY;
        }
        
        if (str_contains($message, 'rate limit') || str_contains($message, 'too many requests')) {
            return IntegrationStatus::DEGRADED;
        }
        
        if (str_contains($message, 'authentication') || str_contains($message, 'unauthorized')) {
            return IntegrationStatus::UNHEALTHY;
        }
        
        return IntegrationStatus::DEGRADED;
    }

    /**
     * Determine status from response time.
     */
    private function determineStatusFromResponseTime(int $responseTimeMs): IntegrationStatus
    {
        if ($responseTimeMs > self::PERFORMANCE_THRESHOLD_MS) {
            return IntegrationStatus::DEGRADED;
        }
        
        return IntegrationStatus::HEALTHY;
    }

    /**
     * Calculate service metrics.
     * 
     * @param string $serviceName The service name
     * 
     * @return array<string, mixed> Service metrics
     */
    private function calculateServiceMetrics(string $serviceName): array
    {
        $checks = DB::table('integration_health_checks')
            ->where('service_name', $serviceName)
            ->where('checked_at', '>=', now()->subHours(24))
            ->get();
        
        if ($checks->isEmpty()) {
            return [
                'uptime_percentage' => 0,
                'avg_response_time_ms' => 0,
                'error_rate' => 0,
            ];
        }
        
        $totalChecks = $checks->count();
        $healthyChecks = $checks->where('status', IntegrationStatus::HEALTHY->value)->count();
        $degradedChecks = $checks->where('status', IntegrationStatus::DEGRADED->value)->count();
        $uptimeChecks = $healthyChecks + $degradedChecks;
        
        $avgResponseTime = $checks->where('response_time_ms', '>', 0)->avg('response_time_ms') ?? 0;
        $errorRate = ($totalChecks - $uptimeChecks) / $totalChecks;
        
        return [
            'uptime_percentage' => round(($uptimeChecks / $totalChecks) * 100, 2),
            'avg_response_time_ms' => (int) round($avgResponseTime),
            'error_rate' => round($errorRate, 3),
        ];
    }
}
