<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Data\System\DateRange;
use App\Data\System\PerformanceReport;
use App\Data\System\ReportFilters;
use App\Data\System\UsageReport;
use App\ValueObjects\SystemHealthStatus;
use Illuminate\Database\Eloquent\Collection;

interface SystemMonitoringInterface
{
    /**
     * Get overall system health status
     */
    public function getSystemHealth(): SystemHealthStatus;

    /**
     * Get usage statistics for all tenants
     */
    public function getTenantUsageStats(): Collection;

    /**
     * Get performance metrics for a specific period
     */
    public function getPerformanceMetrics(DateRange $period): PerformanceReport;

    /**
     * Detect anomalies in tenant behavior or system performance
     */
    public function detectAnomalies(): Collection;

    /**
     * Generate comprehensive usage report
     */
    public function generateUsageReport(ReportFilters $filters): UsageReport;

    /**
     * Get real-time system metrics
     */
    public function getRealTimeMetrics(): array;

    /**
     * Get tenant resource utilization
     */
    public function getTenantResourceUtilization(int $tenantId): array;

    /**
     * Check for tenants approaching resource limits
     */
    public function getTenantsNearLimits(float $threshold = 0.8): Collection;

    /**
     * Get system alerts and warnings
     */
    public function getSystemAlerts(): Collection;
}