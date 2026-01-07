<?php

declare(strict_types=1);

namespace App\Services\Audit;

use Carbon\Carbon;

/**
 * Performance Metrics Collector
 * 
 * Collects performance metrics for audit reporting.
 */
final readonly class PerformanceMetricsCollector
{
    /**
     * Collect performance metrics for audit reporting.
     */
    public function collect(
        ?int $tenantId = null,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        array $serviceTypes = [],
    ): array {
        // Stub implementation - return empty metrics
        return [
            'response_times' => [],
            'throughput' => 0,
            'error_rates' => [],
            'availability' => 100.0,
        ];
    }
}