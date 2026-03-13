<?php

declare(strict_types=1);

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Models\ServiceConfiguration;
use App\Models\UtilityService;
use App\ValueObjects\Audit\AuditReportData;
use App\ValueObjects\Audit\AuditSummary;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Universal Service Audit Reporter
 * 
 * Provides comprehensive audit reporting for universal services including
 * change tracking, performance metrics, and compliance reporting.
 */
final readonly class UniversalServiceAuditReporter
{
    public function __construct(
        private ConfigurationChangeAuditor $configurationAuditor,
        private PerformanceMetricsCollector $performanceCollector,
        private ComplianceReportGenerator $complianceGenerator,
    ) {}

    /**
     * Generate comprehensive audit report for universal services.
     */
    public function generateReport(
        ?int $tenantId = null,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        array $serviceTypes = [],
    ): AuditReportData {
        $cacheKey = $this->buildCacheKey($tenantId, $startDate, $endDate, $serviceTypes);
        
        return Cache::remember($cacheKey, 300, function () use ($tenantId, $startDate, $endDate, $serviceTypes) {
            $startDate ??= now()->subDays(30);
            $endDate ??= now();
            
            return new AuditReportData(
                summary: $this->generateSummary($tenantId, $startDate, $endDate, $serviceTypes),
                configurationChanges: $this->configurationAuditor->getChanges($tenantId, $startDate, $endDate, $serviceTypes),
                performanceMetrics: $this->performanceCollector->collect($tenantId, $startDate, $endDate, $serviceTypes),
                complianceStatus: $this->complianceGenerator->getStatus($tenantId, $startDate, $endDate, $serviceTypes),
                anomalies: $this->detectAnomalies($tenantId, $startDate, $endDate, $serviceTypes),
                generatedAt: now(),
            );
        });
    }

    /**
     * Generate audit summary statistics.
     */
    private function generateSummary(
        ?int $tenantId,
        Carbon $startDate,
        Carbon $endDate,
        array $serviceTypes,
    ): AuditSummary {
        $query = AuditLog::query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('auditable_type', [UtilityService::class, ServiceConfiguration::class]);
            
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }
        
        $totalChanges = $query->count();
        $userChanges = $query->whereNotNull('user_id')->count();
        $systemChanges = $totalChanges - $userChanges;
        
        $eventCounts = $query->select('event', DB::raw('count(*) as count'))
            ->groupBy('event')
            ->pluck('count', 'event')
            ->toArray();
            
        $modelCounts = $query->select('auditable_type', DB::raw('count(*) as count'))
            ->groupBy('auditable_type')
            ->pluck('count', 'auditable_type')
            ->toArray();
        
        return new AuditSummary(
            totalChanges: $totalChanges,
            userChanges: $userChanges,
            systemChanges: $systemChanges,
            eventBreakdown: $eventCounts,
            modelBreakdown: $modelCounts,
            periodStart: $startDate,
            periodEnd: $endDate,
        );
    }

    /**
     * Detect audit anomalies and suspicious patterns.
     */
    private function detectAnomalies(
        ?int $tenantId,
        Carbon $startDate,
        Carbon $endDate,
        array $serviceTypes,
    ): array {
        $anomalies = [];
        
        // Detect unusual change frequency
        $changeFrequency = $this->analyzeChangeFrequency($tenantId, $startDate, $endDate);
        if ($changeFrequency['isAnomalous']) {
            $anomalies[] = [
                'type' => 'high_change_frequency',
                'severity' => 'warning',
                'description' => 'Unusually high number of configuration changes detected',
                'details' => $changeFrequency,
                'detected_at' => now(),
            ];
        }
        
        // Detect bulk changes without proper authorization
        $bulkChanges = $this->detectBulkChanges($tenantId, $startDate, $endDate);
        if (!empty($bulkChanges)) {
            $anomalies[] = [
                'type' => 'bulk_changes',
                'severity' => 'high',
                'description' => 'Multiple rapid changes detected from single user',
                'details' => $bulkChanges,
                'detected_at' => now(),
            ];
        }
        
        // Detect configuration rollbacks
        $rollbacks = $this->detectConfigurationRollbacks($tenantId, $startDate, $endDate);
        if (!empty($rollbacks)) {
            $anomalies[] = [
                'type' => 'configuration_rollbacks',
                'severity' => 'medium',
                'description' => 'Configuration rollbacks detected',
                'details' => $rollbacks,
                'detected_at' => now(),
            ];
        }
        
        return $anomalies;
    }

    /**
     * Analyze change frequency patterns.
     */
    private function analyzeChangeFrequency(?int $tenantId, Carbon $startDate, Carbon $endDate): array
    {
        $query = AuditLog::query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('auditable_type', [UtilityService::class, ServiceConfiguration::class]);
            
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }
        
        $dailyCounts = $query->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();
        
        if (empty($dailyCounts)) {
            return ['isAnomalous' => false, 'average' => 0, 'peak' => 0];
        }
        
        $average = array_sum($dailyCounts) / count($dailyCounts);
        $peak = max($dailyCounts);
        $threshold = $average * 3; // 3x average is considered anomalous
        
        return [
            'isAnomalous' => $peak > $threshold,
            'average' => round($average, 2),
            'peak' => $peak,
            'threshold' => round($threshold, 2),
            'daily_counts' => $dailyCounts,
        ];
    }

    /**
     * Detect bulk changes from single users.
     */
    private function detectBulkChanges(?int $tenantId, Carbon $startDate, Carbon $endDate): array
    {
        $query = AuditLog::query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('auditable_type', [UtilityService::class, ServiceConfiguration::class])
            ->whereNotNull('user_id');
            
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }
        
        // Find users with more than 10 changes in a 1-hour window
        $bulkChanges = $query->select('user_id', 'created_at')
            ->orderBy('user_id')
            ->orderBy('created_at')
            ->get()
            ->groupBy('user_id')
            ->map(function ($userChanges) {
                $changes = $userChanges->sortBy('created_at');
                $bulkWindows = [];
                
                foreach ($changes as $i => $change) {
                    $windowStart = Carbon::parse($change->created_at);
                    $windowEnd = $windowStart->copy()->addHour();
                    
                    $windowChanges = $changes->filter(function ($c) use ($windowStart, $windowEnd) {
                        $changeTime = Carbon::parse($c->created_at);
                        return $changeTime->between($windowStart, $windowEnd);
                    });
                    
                    if ($windowChanges->count() > 10) {
                        $bulkWindows[] = [
                            'window_start' => $windowStart,
                            'window_end' => $windowEnd,
                            'change_count' => $windowChanges->count(),
                        ];
                    }
                }
                
                return $bulkWindows;
            })
            ->filter(fn($windows) => !empty($windows))
            ->toArray();
        
        return $bulkChanges;
    }

    /**
     * Detect configuration rollbacks.
     */
    private function detectConfigurationRollbacks(?int $tenantId, Carbon $startDate, Carbon $endDate): array
    {
        $query = AuditLog::query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('auditable_type', [UtilityService::class, ServiceConfiguration::class])
            ->where('event', 'updated')
            ->orderBy('auditable_type')
            ->orderBy('auditable_id')
            ->orderBy('created_at');
            
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }
        
        $rollbacks = [];
        $changes = $query->get()->groupBy(['auditable_type', 'auditable_id']);
        
        foreach ($changes as $modelType => $modelChanges) {
            foreach ($modelChanges as $modelId => $modelHistory) {
                $history = $modelHistory->sortBy('created_at');
                
                // Look for A->B->A patterns (rollbacks)
                for ($i = 0; $i < $history->count() - 2; $i++) {
                    $change1 = $history->values()[$i];
                    $change2 = $history->values()[$i + 1];
                    $change3 = $history->values()[$i + 2];
                    
                    // Check if change3 reverts change2 back to change1 state
                    if ($this->isRollback($change1, $change2, $change3)) {
                        $rollbacks[] = [
                            'model_type' => $modelType,
                            'model_id' => $modelId,
                            'original_change' => $change1->id,
                            'reverted_change' => $change2->id,
                            'rollback_change' => $change3->id,
                            'rollback_time' => $change3->created_at,
                        ];
                    }
                }
            }
        }
        
        return $rollbacks;
    }

    /**
     * Check if a change is a rollback of a previous change.
     */
    private function isRollback(AuditLog $original, AuditLog $intermediate, AuditLog $potential): bool
    {
        if (!$original->new_values || !$intermediate->new_values || !$potential->new_values) {
            return false;
        }
        
        // Compare key fields to see if potential change reverts to original state
        $originalValues = $original->new_values;
        $potentialValues = $potential->new_values;
        
        $keyFields = ['name', 'configuration', 'pricing_model', 'rate_schedule'];
        $matches = 0;
        $totalFields = 0;
        
        foreach ($keyFields as $field) {
            if (isset($originalValues[$field]) && isset($potentialValues[$field])) {
                $totalFields++;
                if ($originalValues[$field] === $potentialValues[$field]) {
                    $matches++;
                }
            }
        }
        
        // Consider it a rollback if 80% of key fields match
        return $totalFields > 0 && ($matches / $totalFields) >= 0.8;
    }

    /**
     * Build cache key for audit report.
     */
    private function buildCacheKey(?int $tenantId, ?Carbon $startDate, ?Carbon $endDate, array $serviceTypes): string
    {
        $parts = [
            'audit_report',
            $tenantId ?? 'all',
            $startDate?->format('Y-m-d') ?? 'no_start',
            $endDate?->format('Y-m-d') ?? 'no_end',
            implode(',', $serviceTypes) ?: 'all_types',
        ];
        
        return implode(':', $parts);
    }
}