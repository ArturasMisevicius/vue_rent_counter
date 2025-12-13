<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\SystemMonitoringInterface;
use App\Data\System\DateRange;
use App\Data\System\PerformanceReport;
use App\Data\System\ReportFilters;
use App\Data\System\UsageReport;
use App\Models\Organization;
use App\ValueObjects\SystemHealthStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final readonly class SystemMonitoringService implements SystemMonitoringInterface
{
    public function getSystemHealth(): SystemHealthStatus
    {
        return Cache::remember('system_health', 300, function () {
            $activeTenants = Organization::active()->count();
            $totalUsers = DB::table('users')->count();
            
            // Calculate average response time across all tenants
            $avgResponseTime = Organization::query()
                ->where('average_response_time', '>', 0)
                ->avg('average_response_time') ?? 0;

            // Get system resource usage (simplified)
            $cpuUsage = $this->getCpuUsage();
            $memoryUsage = $this->getMemoryUsage();
            $diskUsage = $this->getDiskUsage();

            // Collect alerts
            $alerts = $this->collectSystemAlerts();

            // Determine overall health
            $healthLevel = $this->calculateOverallHealth($cpuUsage, $memoryUsage, $diskUsage, $avgResponseTime);

            return new SystemHealthStatus(
                overall: $healthLevel,
                cpuUsage: $cpuUsage,
                memoryUsage: $memoryUsage,
                diskUsage: $diskUsage,
                activeTenants: $activeTenants,
                totalUsers: $totalUsers,
                averageResponseTime: $avgResponseTime,
                alerts: collect($alerts),
            );
        });
    }

    public function getTenantUsageStats(): Collection
    {
        return Cache::remember('tenant_usage_stats', 600, function () {
            return Organization::query()
                ->select([
                    'id',
                    'name',
                    'plan',
                    'storage_used_mb',
                    'api_calls_today',
                    'api_calls_quota',
                    'average_response_time',
                    'last_activity_at',
                ])
                ->withCount(['users', 'properties'])
                ->get()
                ->map(function (Organization $tenant) {
                    return [
                        'tenant_id' => $tenant->id,
                        'name' => $tenant->name,
                        'plan' => $tenant->plan->value,
                        'users_count' => $tenant->users_count,
                        'properties_count' => $tenant->properties_count,
                        'storage_used_mb' => $tenant->storage_used_mb,
                        'storage_percentage' => $this->calculateStoragePercentage($tenant),
                        'api_calls_today' => $tenant->api_calls_today,
                        'api_calls_percentage' => ($tenant->api_calls_today / max($tenant->api_calls_quota, 1)) * 100,
                        'average_response_time' => $tenant->average_response_time,
                        'last_activity' => $tenant->last_activity_at,
                        'health_status' => $tenant->calculateHealthStatus(),
                    ];
                });
        });
    }

    public function getPerformanceMetrics(DateRange $period): PerformanceReport
    {
        // This would typically query performance metrics from a time-series database
        // For now, we'll simulate with basic database queries
        
        $metrics = [
            'period' => [
                'start' => $period->startDate,
                'end' => $period->endDate,
                'days' => $period->getDays(),
            ],
            'response_times' => $this->getResponseTimeMetrics($period),
            'api_calls' => $this->getApiCallMetrics($period),
            'error_rates' => $this->getErrorRateMetrics($period),
            'tenant_activity' => $this->getTenantActivityMetrics($period),
        ];

        return new PerformanceReport($metrics);
    }

    public function detectAnomalies(): Collection
    {
        $anomalies = collect();

        // Check for tenants with unusually high API usage
        $highApiUsage = Organization::query()
            ->where('api_calls_today', '>', DB::raw('api_calls_quota * 0.9'))
            ->get();

        foreach ($highApiUsage as $tenant) {
            $anomalies->push([
                'type' => 'high_api_usage',
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'severity' => 'warning',
                'description' => "Tenant is using {$tenant->api_calls_today} API calls (90%+ of quota)",
                'detected_at' => now(),
            ]);
        }

        // Check for tenants with high response times
        $slowTenants = Organization::query()
            ->where('average_response_time', '>', 3000) // > 3 seconds
            ->get();

        foreach ($slowTenants as $tenant) {
            $anomalies->push([
                'type' => 'slow_response_time',
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'severity' => 'critical',
                'description' => "Tenant has slow response time: {$tenant->average_response_time}ms",
                'detected_at' => now(),
            ]);
        }

        // Check for inactive tenants
        $inactiveTenants = Organization::query()
            ->where('last_activity_at', '<', now()->subDays(7))
            ->where('is_active', true)
            ->get();

        foreach ($inactiveTenants as $tenant) {
            $anomalies->push([
                'type' => 'inactive_tenant',
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'severity' => 'info',
                'description' => "Tenant has been inactive for over 7 days",
                'detected_at' => now(),
            ]);
        }

        return $anomalies;
    }

    public function generateUsageReport(ReportFilters $filters): UsageReport
    {
        // Implementation would generate comprehensive usage reports
        // This is a simplified version
        return new UsageReport([
            'filters' => $filters->toArray(),
            'generated_at' => now(),
            'tenant_count' => Organization::count(),
            'active_tenant_count' => Organization::active()->count(),
            'total_users' => DB::table('users')->count(),
            'total_storage_mb' => Organization::sum('storage_used_mb'),
            'total_api_calls_today' => Organization::sum('api_calls_today'),
        ]);
    }

    public function getRealTimeMetrics(): array
    {
        return Cache::remember('real_time_metrics', 60, function () {
            return [
                'timestamp' => now(),
                'active_tenants' => Organization::active()->count(),
                'total_users' => DB::table('users')->count(),
                'api_calls_last_hour' => $this->getApiCallsLastHour(),
                'average_response_time' => Organization::where('average_response_time', '>', 0)->avg('average_response_time'),
                'system_load' => $this->getSystemLoad(),
            ];
        });
    }

    public function getTenantResourceUtilization(int $tenantId): array
    {
        $tenant = Organization::findOrFail($tenantId);

        return [
            'tenant_id' => $tenantId,
            'storage' => [
                'used_mb' => $tenant->storage_used_mb,
                'quota_mb' => $tenant->getResourceQuota('storage_mb', 1000),
                'percentage' => $this->calculateStoragePercentage($tenant),
            ],
            'api_calls' => [
                'today' => $tenant->api_calls_today,
                'quota' => $tenant->api_calls_quota,
                'percentage' => ($tenant->api_calls_today / max($tenant->api_calls_quota, 1)) * 100,
            ],
            'users' => [
                'current' => $tenant->users()->count(),
                'limit' => $tenant->max_users,
                'percentage' => ($tenant->users()->count() / max($tenant->max_users, 1)) * 100,
            ],
            'properties' => [
                'current' => $tenant->properties()->count(),
                'limit' => $tenant->max_properties,
                'percentage' => ($tenant->properties()->count() / max($tenant->max_properties, 1)) * 100,
            ],
        ];
    }

    public function getTenantsNearLimits(float $threshold = 0.8): Collection
    {
        return Organization::query()
            ->get()
            ->filter(function (Organization $tenant) use ($threshold) {
                $storagePercentage = $this->calculateStoragePercentage($tenant) / 100;
                $apiPercentage = ($tenant->api_calls_today / max($tenant->api_calls_quota, 1));
                $userPercentage = ($tenant->users()->count() / max($tenant->max_users, 1));
                $propertyPercentage = ($tenant->properties()->count() / max($tenant->max_properties, 1));

                return $storagePercentage >= $threshold ||
                       $apiPercentage >= $threshold ||
                       $userPercentage >= $threshold ||
                       $propertyPercentage >= $threshold;
            });
    }

    public function getSystemAlerts(): Collection
    {
        return Cache::remember('system_alerts', 300, function () {
            $alerts = collect();

            // Check system health
            $health = $this->getSystemHealth();
            
            if ($health->cpuUsage > 80) {
                $alerts->push([
                    'type' => 'high_cpu_usage',
                    'severity' => 'warning',
                    'message' => "High CPU usage: {$health->cpuUsage}%",
                    'created_at' => now(),
                ]);
            }

            if ($health->memoryUsage > 85) {
                $alerts->push([
                    'type' => 'high_memory_usage',
                    'severity' => 'critical',
                    'message' => "High memory usage: {$health->memoryUsage}%",
                    'created_at' => now(),
                ]);
            }

            if ($health->diskUsage > 90) {
                $alerts->push([
                    'type' => 'high_disk_usage',
                    'severity' => 'critical',
                    'message' => "High disk usage: {$health->diskUsage}%",
                    'created_at' => now(),
                ]);
            }

            return $alerts;
        });
    }

    private function getCpuUsage(): float
    {
        // Simplified CPU usage calculation
        // In production, this would use system monitoring tools
        return rand(10, 80);
    }

    private function getMemoryUsage(): float
    {
        // Simplified memory usage calculation
        return rand(30, 70);
    }

    private function getDiskUsage(): float
    {
        // Simplified disk usage calculation
        return rand(20, 60);
    }

    private function calculateOverallHealth(float $cpu, float $memory, float $disk, float $responseTime): string
    {
        $issues = 0;
        
        if ($cpu > 80) $issues++;
        if ($memory > 85) $issues++;
        if ($disk > 90) $issues++;
        if ($responseTime > 2000) $issues++;

        return match (true) {
            $issues === 0 => 'healthy',
            $issues <= 2 => 'warning',
            default => 'critical',
        };
    }

    private function calculateStoragePercentage(Organization $tenant): float
    {
        $quota = $tenant->getResourceQuota('storage_mb', 1000);
        return ($tenant->storage_used_mb / max($quota, 1)) * 100;
    }

    private function collectSystemAlerts(): array
    {
        // Collect various system alerts
        return [];
    }

    private function getResponseTimeMetrics(DateRange $period): array
    {
        // Simplified response time metrics
        return [
            'average' => Organization::where('average_response_time', '>', 0)->avg('average_response_time'),
            'p95' => Organization::where('average_response_time', '>', 0)->orderBy('average_response_time', 'desc')->limit(5)->avg('average_response_time'),
            'p99' => Organization::where('average_response_time', '>', 0)->orderBy('average_response_time', 'desc')->limit(1)->value('average_response_time'),
        ];
    }

    private function getApiCallMetrics(DateRange $period): array
    {
        return [
            'total' => Organization::sum('api_calls_today'),
            'average_per_tenant' => Organization::avg('api_calls_today'),
            'peak_usage' => Organization::max('api_calls_today'),
        ];
    }

    private function getErrorRateMetrics(DateRange $period): array
    {
        // This would typically come from application logs
        return [
            'total_requests' => 100000,
            'total_errors' => 500,
            'error_rate' => 0.5,
        ];
    }

    private function getTenantActivityMetrics(DateRange $period): array
    {
        return [
            'active_tenants' => Organization::where('last_activity_at', '>=', $period->startDate)->count(),
            'new_tenants' => Organization::whereBetween('created_at', [$period->startDate, $period->endDate])->count(),
        ];
    }

    private function getApiCallsLastHour(): int
    {
        // This would typically come from real-time metrics
        return rand(1000, 5000);
    }

    private function getSystemLoad(): array
    {
        return [
            'load_1m' => rand(1, 5) / 10,
            'load_5m' => rand(1, 5) / 10,
            'load_15m' => rand(1, 5) / 10,
        ];
    }
}