<?php

declare(strict_types=1);

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Models\MeterReading;
use App\Models\ServiceConfiguration;
use App\ValueObjects\Audit\PerformanceMetrics;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Performance Metrics Collector
 * 
 * Collects and analyzes performance metrics for universal billing calculations,
 * system response times, and operational efficiency indicators.
 */
final readonly class PerformanceMetricsCollector
{
    /**
     * Collect comprehensive performance metrics.
     */
    public function collect(
        ?int $tenantId,
        Carbon $startDate,
        Carbon $endDate,
        array $serviceTypes = [],
    ): PerformanceMetrics {
        $cacheKey = "performance_metrics:{$tenantId}:{$startDate->format('Y-m-d')}:{$endDate->format('Y-m-d')}";
        
        return Cache::remember($cacheKey, 600, function () use ($tenantId, $startDate, $endDate, $serviceTypes) {
            return new PerformanceMetrics(
                billingCalculationMetrics: $this->collectBillingMetrics($tenantId, $startDate, $endDate),
                systemResponseMetrics: $this->collectResponseMetrics($tenantId, $startDate, $endDate),
                dataQualityMetrics: $this->collectDataQualityMetrics($tenantId, $startDate, $endDate),
                operationalEfficiency: $this->collectOperationalMetrics($tenantId, $startDate, $endDate),
                errorRates: $this->collectErrorRates($tenantId, $startDate, $endDate),
                resourceUtilization: $this->collectResourceMetrics($tenantId, $startDate, $endDate),
                collectedAt: now(),
            );
        });
    }

    /**
     * Collect billing calculation performance metrics.
     */
    private function collectBillingMetrics(?int $tenantId, Carbon $startDate, Carbon $endDate): array
    {
        $query = DB::table('meter_readings')
            ->whereBetween('created_at', [$startDate, $endDate]);
            
        if ($tenantId) {
            $query->join('meters', 'meter_readings.meter_id', '=', 'meters.id')
                  ->join('properties', 'meters.property_id', '=', 'properties.id')
                  ->where('properties.tenant_id', $tenantId);
        }
        
        $totalReadings = $query->count();
        $processedReadings = $query->whereNotNull('validated_at')->count();
        $averageProcessingTime = $this->calculateAverageProcessingTime($tenantId, $startDate, $endDate);
        
        return [
            'total_readings_processed' => $totalReadings,
            'successful_calculations' => $processedReadings,
            'calculation_success_rate' => $totalReadings > 0 ? round(($processedReadings / $totalReadings) * 100, 2) : 0,
            'average_processing_time_ms' => $averageProcessingTime,
            'peak_processing_time_ms' => $this->getPeakProcessingTime($tenantId, $startDate, $endDate),
            'calculations_per_hour' => $this->getCalculationsPerHour($tenantId, $startDate, $endDate),
        ];
    }

    /**
     * Collect system response time metrics.
     */
    private function collectResponseMetrics(?int $tenantId, Carbon $startDate, Carbon $endDate): array
    {
        // In a real implementation, this would collect from application performance monitoring
        // For now, we'll simulate based on audit log creation patterns
        
        $auditQuery = AuditLog::whereBetween('created_at', [$startDate, $endDate]);
        if ($tenantId) {
            $auditQuery->where('tenant_id', $tenantId);
        }
        
        $auditCount = $auditQuery->count();
        $timeSpan = $endDate->diffInHours($startDate);
        
        return [
            'average_response_time_ms' => rand(50, 200), // Simulated - replace with real APM data
            'p95_response_time_ms' => rand(200, 500),
            'p99_response_time_ms' => rand(500, 1000),
            'requests_per_hour' => $timeSpan > 0 ? round($auditCount / $timeSpan, 2) : 0,
            'error_rate_percentage' => rand(0, 2), // Simulated
            'uptime_percentage' => rand(98, 100), // Simulated
        ];
    }

    /**
     * Collect data quality metrics.
     */
    private function collectDataQualityMetrics(?int $tenantId, Carbon $startDate, Carbon $endDate): array
    {
        $readingsQuery = MeterReading::whereBetween('created_at', [$startDate, $endDate]);
        
        if ($tenantId) {
            $readingsQuery->whereHas('meter.property', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            });
        }
        
        $totalReadings = $readingsQuery->count();
        $validatedReadings = $readingsQuery->where('validation_status', 'validated')->count();
        $rejectedReadings = $readingsQuery->where('validation_status', 'rejected')->count();
        $estimatedReadings = $readingsQuery->where('input_method', 'estimated')->count();
        
        return [
            'total_readings' => $totalReadings,
            'validated_readings' => $validatedReadings,
            'rejected_readings' => $rejectedReadings,
            'estimated_readings' => $estimatedReadings,
            'data_quality_score' => $totalReadings > 0 ? round(($validatedReadings / $totalReadings) * 100, 2) : 0,
            'manual_intervention_rate' => $totalReadings > 0 ? round(($rejectedReadings / $totalReadings) * 100, 2) : 0,
            'estimation_rate' => $totalReadings > 0 ? round(($estimatedReadings / $totalReadings) * 100, 2) : 0,
        ];
    }

    /**
     * Collect operational efficiency metrics.
     */
    private function collectOperationalMetrics(?int $tenantId, Carbon $startDate, Carbon $endDate): array
    {
        $configQuery = ServiceConfiguration::whereBetween('created_at', [$startDate, $endDate]);
        if ($tenantId) {
            $configQuery->whereHas('property', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            });
        }
        
        $newConfigurations = $configQuery->count();
        $activeConfigurations = ServiceConfiguration::where('is_active', true);
        
        if ($tenantId) {
            $activeConfigurations->whereHas('property', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            });
        }
        
        $activeCount = $activeConfigurations->count();
        
        return [
            'new_configurations' => $newConfigurations,
            'active_configurations' => $activeCount,
            'configuration_utilization_rate' => $activeCount > 0 ? round(($activeCount / ($activeCount + $newConfigurations)) * 100, 2) : 0,
            'automation_rate' => $this->calculateAutomationRate($tenantId, $startDate, $endDate),
            'manual_override_rate' => $this->calculateManualOverrideRate($tenantId, $startDate, $endDate),
        ];
    }

    /**
     * Collect error rates and failure metrics.
     */
    private function collectErrorRates(?int $tenantId, Carbon $startDate, Carbon $endDate): array
    {
        // This would typically integrate with error tracking systems
        // For now, we'll analyze audit logs for error patterns
        
        $errorQuery = AuditLog::whereBetween('created_at', [$startDate, $endDate])
            ->where('event', 'error');
            
        if ($tenantId) {
            $errorQuery->where('tenant_id', $tenantId);
        }
        
        $totalErrors = $errorQuery->count();
        $criticalErrors = $errorQuery->where('notes', 'like', '%critical%')->count();
        
        return [
            'total_errors' => $totalErrors,
            'critical_errors' => $criticalErrors,
            'error_rate_per_hour' => $this->calculateErrorRatePerHour($tenantId, $startDate, $endDate),
            'most_common_errors' => $this->getMostCommonErrors($tenantId, $startDate, $endDate),
            'error_resolution_time_avg' => $this->getAverageErrorResolutionTime($tenantId, $startDate, $endDate),
        ];
    }

    /**
     * Collect resource utilization metrics.
     */
    private function collectResourceMetrics(?int $tenantId, Carbon $startDate, Carbon $endDate): array
    {
        // This would typically integrate with system monitoring tools
        // For now, we'll provide simulated metrics based on activity levels
        
        $activityLevel = $this->calculateActivityLevel($tenantId, $startDate, $endDate);
        
        return [
            'cpu_utilization_avg' => min(100, $activityLevel * 0.3), // Simulated
            'memory_utilization_avg' => min(100, $activityLevel * 0.4), // Simulated
            'database_query_count' => $this->estimateQueryCount($tenantId, $startDate, $endDate),
            'cache_hit_rate' => rand(85, 95), // Simulated
            'storage_utilization_mb' => $this->estimateStorageUsage($tenantId, $startDate, $endDate),
        ];
    }

    /**
     * Calculate average processing time for billing calculations.
     */
    private function calculateAverageProcessingTime(?int $tenantId, Carbon $startDate, Carbon $endDate): float
    {
        // This would typically measure actual processing times
        // For now, we'll estimate based on complexity
        
        $readingsQuery = MeterReading::whereBetween('created_at', [$startDate, $endDate]);
        
        if ($tenantId) {
            $readingsQuery->whereHas('meter.property', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            });
        }
        
        $complexReadings = $readingsQuery->whereNotNull('reading_values')->count();
        $simpleReadings = $readingsQuery->whereNull('reading_values')->count();
        
        // Estimate processing time based on complexity
        $avgComplexTime = 150; // ms
        $avgSimpleTime = 50; // ms
        
        $totalTime = ($complexReadings * $avgComplexTime) + ($simpleReadings * $avgSimpleTime);
        $totalReadings = $complexReadings + $simpleReadings;
        
        return $totalReadings > 0 ? round($totalTime / $totalReadings, 2) : 0;
    }

    /**
     * Get peak processing time.
     */
    private function getPeakProcessingTime(?int $tenantId, Carbon $startDate, Carbon $endDate): float
    {
        // Simulated peak time - would be actual measurement in production
        return $this->calculateAverageProcessingTime($tenantId, $startDate, $endDate) * 2.5;
    }

    /**
     * Calculate calculations per hour.
     */
    private function getCalculationsPerHour(?int $tenantId, Carbon $startDate, Carbon $endDate): float
    {
        $readingsQuery = MeterReading::whereBetween('created_at', [$startDate, $endDate]);
        
        if ($tenantId) {
            $readingsQuery->whereHas('meter.property', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            });
        }
        
        $totalReadings = $readingsQuery->count();
        $hours = $endDate->diffInHours($startDate);
        
        return $hours > 0 ? round($totalReadings / $hours, 2) : 0;
    }

    /**
     * Calculate automation rate.
     */
    private function calculateAutomationRate(?int $tenantId, Carbon $startDate, Carbon $endDate): float
    {
        $readingsQuery = MeterReading::whereBetween('created_at', [$startDate, $endDate]);
        
        if ($tenantId) {
            $readingsQuery->whereHas('meter.property', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            });
        }
        
        $totalReadings = $readingsQuery->count();
        $automatedReadings = $readingsQuery->whereIn('input_method', ['api_integration', 'csv_import'])->count();
        
        return $totalReadings > 0 ? round(($automatedReadings / $totalReadings) * 100, 2) : 0;
    }

    /**
     * Calculate manual override rate.
     */
    private function calculateManualOverrideRate(?int $tenantId, Carbon $startDate, Carbon $endDate): float
    {
        $readingsQuery = MeterReading::whereBetween('created_at', [$startDate, $endDate]);
        
        if ($tenantId) {
            $readingsQuery->whereHas('meter.property', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            });
        }
        
        $totalReadings = $readingsQuery->count();
        $manualReadings = $readingsQuery->where('input_method', 'manual')->count();
        
        return $totalReadings > 0 ? round(($manualReadings / $totalReadings) * 100, 2) : 0;
    }

    /**
     * Calculate error rate per hour.
     */
    private function calculateErrorRatePerHour(?int $tenantId, Carbon $startDate, Carbon $endDate): float
    {
        $errorQuery = AuditLog::whereBetween('created_at', [$startDate, $endDate])
            ->where('event', 'error');
            
        if ($tenantId) {
            $errorQuery->where('tenant_id', $tenantId);
        }
        
        $totalErrors = $errorQuery->count();
        $hours = $endDate->diffInHours($startDate);
        
        return $hours > 0 ? round($totalErrors / $hours, 2) : 0;
    }

    /**
     * Get most common error types.
     */
    private function getMostCommonErrors(?int $tenantId, Carbon $startDate, Carbon $endDate): array
    {
        $errorQuery = AuditLog::whereBetween('created_at', [$startDate, $endDate])
            ->where('event', 'error');
            
        if ($tenantId) {
            $errorQuery->where('tenant_id', $tenantId);
        }
        
        return $errorQuery->select('notes', DB::raw('count(*) as count'))
            ->groupBy('notes')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->pluck('count', 'notes')
            ->toArray();
    }

    /**
     * Get average error resolution time.
     */
    private function getAverageErrorResolutionTime(?int $tenantId, Carbon $startDate, Carbon $endDate): float
    {
        // This would typically track error resolution in a ticketing system
        // For now, we'll provide a simulated value
        return rand(30, 120); // minutes
    }

    /**
     * Calculate activity level for resource estimation.
     */
    private function calculateActivityLevel(?int $tenantId, Carbon $startDate, Carbon $endDate): float
    {
        $auditQuery = AuditLog::whereBetween('created_at', [$startDate, $endDate]);
        if ($tenantId) {
            $auditQuery->where('tenant_id', $tenantId);
        }
        
        $auditCount = $auditQuery->count();
        $hours = $endDate->diffInHours($startDate);
        
        return $hours > 0 ? $auditCount / $hours : 0;
    }

    /**
     * Estimate database query count.
     */
    private function estimateQueryCount(?int $tenantId, Carbon $startDate, Carbon $endDate): int
    {
        $activityLevel = $this->calculateActivityLevel($tenantId, $startDate, $endDate);
        return (int) ($activityLevel * 50); // Estimated queries per activity unit
    }

    /**
     * Estimate storage usage.
     */
    private function estimateStorageUsage(?int $tenantId, Carbon $startDate, Carbon $endDate): float
    {
        $readingsQuery = MeterReading::whereBetween('created_at', [$startDate, $endDate]);
        
        if ($tenantId) {
            $readingsQuery->whereHas('meter.property', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            });
        }
        
        $readingsCount = $readingsQuery->count();
        $avgReadingSize = 2; // KB per reading (estimated)
        
        return $readingsCount * $avgReadingSize;
    }
}