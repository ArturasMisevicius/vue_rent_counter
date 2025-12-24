<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Integration\ExternalServiceHealthMonitor;
use App\Services\Integration\IntegrationResilienceHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * API controller for integration health monitoring and management.
 * 
 * Provides endpoints for checking service health, viewing health history,
 * and managing integration status for external services.
 * 
 * @package App\Http\Controllers\Api
 * @author Laravel Development Team
 * @since 1.0.0
 */
final class IntegrationHealthController extends Controller
{
    public function __construct(
        private readonly ExternalServiceHealthMonitor $healthMonitor,
        private readonly IntegrationResilienceHandler $resilienceHandler,
    ) {}

    /**
     * Get health status for all monitored services.
     * 
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\IntegrationHealthCheck::class);

        $services = $this->resilienceHandler->getServicesHealthStatus();
        
        $summary = [
            'total_services' => count($services),
            'healthy_services' => 0,
            'degraded_services' => 0,
            'unhealthy_services' => 0,
            'last_updated' => now()->toISOString(),
        ];

        foreach ($services as $service) {
            match ($service['status']->value) {
                'healthy' => $summary['healthy_services']++,
                'degraded' => $summary['degraded_services']++,
                'unhealthy', 'circuit_open' => $summary['unhealthy_services']++,
                default => null,
            };
        }

        return response()->json([
            'data' => [
                'services' => $services,
                'summary' => $summary,
            ],
        ]);
    }

    /**
     * Perform health check for a specific service.
     * 
     * @param Request $request
     * @param string $service
     * @return JsonResponse
     */
    public function check(Request $request, string $service): JsonResponse
    {
        $this->authorize('create', \App\Models\IntegrationHealthCheck::class);

        $request->validate([
            'force' => 'boolean',
        ]);

        $force = $request->boolean('force', false);
        
        // Check if we should skip due to rate limiting (unless forced)
        if (!$force) {
            $lastCheck = Cache::get("health_check_last:{$service}");
            if ($lastCheck && now()->diffInMinutes($lastCheck) < 1) {
                return response()->json([
                    'message' => __('integration.health.check_rate_limited'),
                    'next_allowed_at' => $lastCheck->addMinute()->toISOString(),
                ], 429);
            }
        }

        try {
            $result = $this->resilienceHandler->performHealthCheck($service);
            
            // Cache the check time
            Cache::put("health_check_last:{$service}", now(), 300); // 5 minutes

            return response()->json([
                'data' => $result,
                'message' => __('integration.health.check_completed', ['service' => $service]),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => __('integration.health.check_failed', ['service' => $service]),
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get health check history for a service.
     * 
     * @param Request $request
     * @param string $service
     * @return JsonResponse
     */
    public function history(Request $request, string $service): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\IntegrationHealthCheck::class);

        $request->validate([
            'hours' => 'integer|min:1|max:168', // Max 1 week
            'limit' => 'integer|min:1|max:1000',
        ]);

        $hours = $request->integer('hours', 24);
        $limit = $request->integer('limit', 100);

        $history = $this->healthMonitor->getServiceHistory($service, $hours, $limit);

        $stats = [
            'total_checks' => count($history),
            'healthy_checks' => 0,
            'degraded_checks' => 0,
            'unhealthy_checks' => 0,
            'average_response_time' => 0,
            'uptime_percentage' => 0,
        ];

        if (!empty($history)) {
            $totalResponseTime = 0;
            $healthyCount = 0;

            foreach ($history as $check) {
                match ($check['status']) {
                    'healthy' => $stats['healthy_checks']++,
                    'degraded' => $stats['degraded_checks']++,
                    'unhealthy', 'circuit_open' => $stats['unhealthy_checks']++,
                    default => null,
                };

                if ($check['response_time_ms']) {
                    $totalResponseTime += $check['response_time_ms'];
                }

                if (in_array($check['status'], ['healthy', 'degraded'])) {
                    $healthyCount++;
                }
            }

            $stats['average_response_time'] = round($totalResponseTime / count($history), 2);
            $stats['uptime_percentage'] = round(($healthyCount / count($history)) * 100, 2);
        }

        return response()->json([
            'data' => [
                'service' => $service,
                'period_hours' => $hours,
                'history' => $history,
                'statistics' => $stats,
            ],
        ]);
    }

    /**
     * Enable maintenance mode for a service.
     * 
     * @param Request $request
     * @param string $service
     * @return JsonResponse
     */
    public function enableMaintenance(Request $request, string $service): JsonResponse
    {
        $this->authorize('create', \App\Models\IntegrationHealthCheck::class);

        $request->validate([
            'duration_minutes' => 'required|integer|min:1|max:1440', // Max 24 hours
            'reason' => 'string|max:500',
        ]);

        $durationMinutes = $request->integer('duration_minutes');
        $reason = $request->string('reason', 'Scheduled maintenance');

        $this->resilienceHandler->enableMaintenanceMode($service, $durationMinutes);

        return response()->json([
            'message' => __('integration.maintenance.enabled', ['service' => $service]),
            'data' => [
                'service' => $service,
                'duration_minutes' => $durationMinutes,
                'reason' => $reason,
                'enabled_at' => now()->toISOString(),
                'expires_at' => now()->addMinutes($durationMinutes)->toISOString(),
            ],
        ]);
    }

    /**
     * Disable maintenance mode for a service.
     * 
     * @param string $service
     * @return JsonResponse
     */
    public function disableMaintenance(string $service): JsonResponse
    {
        $this->authorize('create', \App\Models\IntegrationHealthCheck::class);

        Cache::forget("maintenance_mode:{$service}");

        return response()->json([
            'message' => __('integration.maintenance.disabled', ['service' => $service]),
            'data' => [
                'service' => $service,
                'disabled_at' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Get system-wide integration health dashboard data.
     * 
     * @return JsonResponse
     */
    public function dashboard(): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\IntegrationHealthCheck::class);

        $services = $this->resilienceHandler->getServicesHealthStatus();
        
        $dashboard = [
            'overview' => [
                'total_services' => count($services),
                'healthy_services' => 0,
                'degraded_services' => 0,
                'unhealthy_services' => 0,
                'maintenance_services' => 0,
            ],
            'critical_alerts' => [],
            'recent_incidents' => [],
            'performance_metrics' => [
                'average_response_time' => 0,
                'total_requests_24h' => 0,
                'error_rate_24h' => 0,
                'uptime_percentage' => 0,
            ],
            'last_updated' => now()->toISOString(),
        ];

        $totalResponseTime = 0;
        $responseTimeCount = 0;

        foreach ($services as $serviceName => $service) {
            match ($service['status']->value) {
                'healthy' => $dashboard['overview']['healthy_services']++,
                'degraded' => $dashboard['overview']['degraded_services']++,
                'unhealthy', 'circuit_open' => $dashboard['overview']['unhealthy_services']++,
                'maintenance' => $dashboard['overview']['maintenance_services']++,
                default => null,
            };

            // Add critical alerts
            if ($service['status']->requiresAttention()) {
                $dashboard['critical_alerts'][] = [
                    'service' => $serviceName,
                    'status' => $service['status']->value,
                    'message' => $service['last_error'] ?? 'Service requires attention',
                    'priority' => $service['status']->getAlertPriority(),
                    'timestamp' => $service['last_check'] ?? now()->toISOString(),
                ];
            }

            // Aggregate performance metrics
            if (isset($service['response_time_ms']) && $service['response_time_ms'] > 0) {
                $totalResponseTime += $service['response_time_ms'];
                $responseTimeCount++;
            }
        }

        // Calculate average response time
        if ($responseTimeCount > 0) {
            $dashboard['performance_metrics']['average_response_time'] = round($totalResponseTime / $responseTimeCount, 2);
        }

        // Sort alerts by priority
        usort($dashboard['critical_alerts'], fn($a, $b) => $a['priority'] <=> $b['priority']);

        // Get recent incidents (placeholder - would come from incident tracking)
        $dashboard['recent_incidents'] = $this->getRecentIncidents();

        return response()->json([
            'data' => $dashboard,
        ]);
    }

    /**
     * Get recent incidents (placeholder implementation).
     * 
     * @return array<array<string, mixed>>
     */
    private function getRecentIncidents(): array
    {
        // This would typically query an incidents table or external monitoring system
        return [
            [
                'id' => 'INC-001',
                'service' => 'meter_reading_api',
                'title' => 'API Response Time Degradation',
                'status' => 'resolved',
                'started_at' => now()->subHours(2)->toISOString(),
                'resolved_at' => now()->subHour()->toISOString(),
                'impact' => 'medium',
            ],
            [
                'id' => 'INC-002',
                'service' => 'ocr_service',
                'title' => 'OCR Processing Failures',
                'status' => 'investigating',
                'started_at' => now()->subMinutes(30)->toISOString(),
                'resolved_at' => null,
                'impact' => 'low',
            ],
        ];
    }
}