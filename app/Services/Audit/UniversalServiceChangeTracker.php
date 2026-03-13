<?php

declare(strict_types=1);

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Models\ServiceConfiguration;
use App\Models\UtilityService;
use App\ValueObjects\Audit\ConfigurationChange;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Universal Service Change Tracker
 * 
 * Provides detailed tracking and analysis of universal service changes
 * including configuration history, rollback capabilities, and change impact analysis.
 */
final readonly class UniversalServiceChangeTracker
{
    /**
     * Track configuration changes for a specific service.
     */
    public function trackServiceChanges(
        int $serviceId,
        ?int $tenantId = null,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
    ): Collection {
        $cacheKey = "service_changes:{$serviceId}:{$tenantId}:" . 
                   ($startDate?->format('Y-m-d') ?? 'all') . ':' . 
                   ($endDate?->format('Y-m-d') ?? 'all');
        
        return Cache::remember($cacheKey, 600, function () use ($serviceId, $tenantId, $startDate, $endDate) {
            $query = AuditLog::where('auditable_type', UtilityService::class)
                ->where('auditable_id', $serviceId)
                ->orderBy('created_at', 'desc');
                
            if ($tenantId) {
                $query->where('tenant_id', $tenantId);
            }
            
            if ($startDate) {
                $query->where('created_at', '>=', $startDate);
            }
            
            if ($endDate) {
                $query->where('created_at', '<=', $endDate);
            }
            
            return $query->get()->map(function (AuditLog $audit) {
                return $this->buildConfigurationChange($audit);
            });
        });
    }

    /**
     * Track configuration changes for a specific property service configuration.
     */
    public function trackConfigurationChanges(
        int $configurationId,
        ?int $tenantId = null,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
    ): Collection {
        $cacheKey = "config_changes:{$configurationId}:{$tenantId}:" . 
                   ($startDate?->format('Y-m-d') ?? 'all') . ':' . 
                   ($endDate?->format('Y-m-d') ?? 'all');
        
        return Cache::remember($cacheKey, 600, function () use ($configurationId, $tenantId, $startDate, $endDate) {
            $query = AuditLog::where('auditable_type', ServiceConfiguration::class)
                ->where('auditable_id', $configurationId)
                ->orderBy('created_at', 'desc');
                
            if ($tenantId) {
                $query->where('tenant_id', $tenantId);
            }
            
            if ($startDate) {
                $query->where('created_at', '>=', $startDate);
            }
            
            if ($endDate) {
                $query->where('created_at', '<=', $endDate);
            }
            
            return $query->get()->map(function (AuditLog $audit) {
                return $this->buildConfigurationChange($audit);
            });
        });
    }

    /**
     * Get all changes for a tenant within a time period.
     */
    public function getTenantChanges(
        int $tenantId,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        array $serviceTypes = [],
    ): Collection {
        $startDate ??= now()->subDays(30);
        $endDate ??= now();
        
        $cacheKey = "tenant_changes:{$tenantId}:{$startDate->format('Y-m-d')}:{$endDate->format('Y-m-d')}:" . 
                   implode(',', $serviceTypes);
        
        return Cache::remember($cacheKey, 300, function () use ($tenantId, $startDate, $endDate, $serviceTypes) {
            $query = AuditLog::where('tenant_id', $tenantId)
                ->whereIn('auditable_type', [UtilityService::class, ServiceConfiguration::class])
                ->whereBetween('created_at', [$startDate, $endDate])
                ->orderBy('created_at', 'desc');
            
            if (!empty($serviceTypes)) {
                // Filter by service types if specified
                $serviceIds = UtilityService::where('tenant_id', $tenantId)
                    ->whereIn('service_type', $serviceTypes)
                    ->pluck('id');
                    
                $configIds = ServiceConfiguration::whereHas('property', function ($q) use ($tenantId) {
                        $q->where('tenant_id', $tenantId);
                    })
                    ->whereHas('utilityService', function ($q) use ($serviceTypes) {
                        $q->whereIn('service_type', $serviceTypes);
                    })
                    ->pluck('id');
                
                $query->where(function ($q) use ($serviceIds, $configIds) {
                    $q->where(function ($subQ) use ($serviceIds) {
                        $subQ->where('auditable_type', UtilityService::class)
                             ->whereIn('auditable_id', $serviceIds);
                    })->orWhere(function ($subQ) use ($configIds) {
                        $subQ->where('auditable_type', ServiceConfiguration::class)
                             ->whereIn('auditable_id', $configIds);
                    });
                });
            }
            
            return $query->get()->map(function (AuditLog $audit) {
                return $this->buildConfigurationChange($audit);
            });
        });
    }

    /**
     * Analyze change patterns and frequency.
     */
    public function analyzeChangePatterns(
        int $tenantId,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
    ): array {
        $startDate ??= now()->subDays(30);
        $endDate ??= now();
        
        $changes = $this->getTenantChanges($tenantId, $startDate, $endDate);
        
        return [
            'total_changes' => $changes->count(),
            'changes_by_type' => $this->groupChangesByType($changes),
            'changes_by_user' => $this->groupChangesByUser($changes),
            'changes_by_day' => $this->groupChangesByDay($changes),
            'changes_by_hour' => $this->groupChangesByHour($changes),
            'most_changed_services' => $this->getMostChangedServices($changes),
            'change_frequency_analysis' => $this->analyzeChangeFrequency($changes),
            'rollback_analysis' => $this->analyzeRollbacks($changes),
        ];
    }

    /**
     * Get configuration rollback capabilities for a specific audit entry.
     */
    public function getConfigurationRollbackData(int $auditLogId): ?array
    {
        $auditLog = AuditLog::find($auditLogId);
        
        if (!$auditLog || !$auditLog->old_values) {
            return null;
        }
        
        $model = $auditLog->auditable_type::find($auditLog->auditable_id);
        
        if (!$model) {
            return null;
        }
        
        return [
            'audit_log_id' => $auditLogId,
            'model_type' => $auditLog->auditable_type,
            'model_id' => $auditLog->auditable_id,
            'current_values' => $this->getCurrentModelValues($model),
            'rollback_values' => $auditLog->old_values,
            'changed_fields' => array_keys($auditLog->old_values),
            'change_summary' => $this->generateChangeSummary($auditLog->old_values, $auditLog->new_values),
            'can_rollback' => $this->canRollback($model, $auditLog),
            'rollback_warnings' => $this->getRollbackWarnings($model, $auditLog),
        ];
    }

    /**
     * Perform configuration rollback.
     */
    public function performConfigurationRollback(
        int $auditLogId,
        int $userId,
        ?string $reason = null,
    ): bool {
        $rollbackData = $this->getConfigurationRollbackData($auditLogId);
        
        if (!$rollbackData || !$rollbackData['can_rollback']) {
            return false;
        }
        
        $auditLog = AuditLog::find($auditLogId);
        $model = $auditLog->auditable_type::find($auditLog->auditable_id);
        
        return DB::transaction(function () use ($model, $auditLog, $userId, $reason) {
            // Update model with old values
            $model->update($auditLog->old_values);
            
            // Create rollback audit entry
            AuditLog::create([
                'auditable_type' => $auditLog->auditable_type,
                'auditable_id' => $auditLog->auditable_id,
                'tenant_id' => $auditLog->tenant_id,
                'user_id' => $userId,
                'event' => 'rollback',
                'old_values' => $auditLog->new_values,
                'new_values' => $auditLog->old_values,
                'notes' => $reason ? "Rollback: {$reason}" : 'Configuration rollback performed',
                'metadata' => [
                    'original_audit_id' => $auditLog->id,
                    'rollback_timestamp' => now()->toISOString(),
                ],
            ]);
            
            return true;
        });
    }

    /**
     * Build configuration change value object from audit log.
     */
    private function buildConfigurationChange(AuditLog $audit): ConfigurationChange
    {
        return new ConfigurationChange(
            auditId: $audit->id,
            modelType: $audit->auditable_type,
            modelId: $audit->auditable_id,
            tenantId: $audit->tenant_id,
            userId: $audit->user_id,
            event: $audit->event,
            oldValues: $audit->old_values ?? [],
            newValues: $audit->new_values ?? [],
            changedAt: $audit->created_at,
            notes: $audit->notes,
            metadata: $audit->metadata ?? [],
        );
    }

    /**
     * Group changes by type.
     */
    private function groupChangesByType(Collection $changes): array
    {
        return $changes->groupBy('event')->map->count()->toArray();
    }

    /**
     * Group changes by user.
     */
    private function groupChangesByUser(Collection $changes): array
    {
        return $changes->groupBy('userId')->map->count()->toArray();
    }

    /**
     * Group changes by day.
     */
    private function groupChangesByDay(Collection $changes): array
    {
        return $changes->groupBy(function (ConfigurationChange $change) {
            return $change->changedAt->format('Y-m-d');
        })->map->count()->toArray();
    }

    /**
     * Group changes by hour.
     */
    private function groupChangesByHour(Collection $changes): array
    {
        return $changes->groupBy(function (ConfigurationChange $change) {
            return $change->changedAt->format('H');
        })->map->count()->toArray();
    }

    /**
     * Get most frequently changed services.
     */
    private function getMostChangedServices(Collection $changes): array
    {
        $serviceCounts = $changes->groupBy('modelId')->map->count();
        
        return $serviceCounts->sortDesc()->take(10)->map(function ($count, $serviceId) {
            $service = UtilityService::find($serviceId) ?? ServiceConfiguration::find($serviceId);
            return [
                'id' => $serviceId,
                'name' => $service?->name ?? 'Unknown Service',
                'type' => $service ? class_basename($service) : 'Unknown',
                'change_count' => $count,
            ];
        })->values()->toArray();
    }

    /**
     * Analyze change frequency patterns.
     */
    private function analyzeChangeFrequency(Collection $changes): array
    {
        if ($changes->isEmpty()) {
            return [
                'average_per_day' => 0,
                'peak_day' => null,
                'quiet_periods' => [],
                'busy_periods' => [],
            ];
        }
        
        $changesByDay = $this->groupChangesByDay($changes);
        $averagePerDay = array_sum($changesByDay) / max(1, count($changesByDay));
        
        $peakDay = array_keys($changesByDay, max($changesByDay))[0] ?? null;
        
        // Identify quiet periods (days with < 50% of average)
        $quietThreshold = $averagePerDay * 0.5;
        $quietPeriods = array_filter($changesByDay, fn($count) => $count < $quietThreshold);
        
        // Identify busy periods (days with > 150% of average)
        $busyThreshold = $averagePerDay * 1.5;
        $busyPeriods = array_filter($changesByDay, fn($count) => $count > $busyThreshold);
        
        return [
            'average_per_day' => round($averagePerDay, 2),
            'peak_day' => $peakDay,
            'peak_count' => $peakDay ? $changesByDay[$peakDay] : 0,
            'quiet_periods' => array_keys($quietPeriods),
            'busy_periods' => array_keys($busyPeriods),
            'total_days_analyzed' => count($changesByDay),
        ];
    }

    /**
     * Analyze rollback patterns.
     */
    private function analyzeRollbacks(Collection $changes): array
    {
        $rollbacks = $changes->filter(fn(ConfigurationChange $change) => $change->event === 'rollback');
        
        if ($rollbacks->isEmpty()) {
            return [
                'total_rollbacks' => 0,
                'rollback_rate' => 0,
                'most_rolled_back_services' => [],
            ];
        }
        
        $rollbacksByService = $rollbacks->groupBy('modelId')->map->count();
        
        return [
            'total_rollbacks' => $rollbacks->count(),
            'rollback_rate' => round(($rollbacks->count() / $changes->count()) * 100, 2),
            'most_rolled_back_services' => $rollbacksByService->sortDesc()->take(5)->toArray(),
            'rollbacks_by_user' => $rollbacks->groupBy('userId')->map->count()->toArray(),
        ];
    }

    /**
     * Get current model values for comparison.
     */
    private function getCurrentModelValues($model): array
    {
        return $model->toArray();
    }

    /**
     * Generate change summary.
     */
    private function generateChangeSummary(?array $oldValues, ?array $newValues): array
    {
        if (!$oldValues || !$newValues) {
            return [];
        }
        
        $summary = [];
        
        foreach ($newValues as $field => $newValue) {
            $oldValue = $oldValues[$field] ?? null;
            
            if ($oldValue !== $newValue) {
                $summary[$field] = [
                    'from' => $oldValue,
                    'to' => $newValue,
                    'type' => $this->getChangeType($oldValue, $newValue),
                ];
            }
        }
        
        return $summary;
    }

    /**
     * Determine if rollback is possible.
     */
    private function canRollback($model, AuditLog $auditLog): bool
    {
        // Check if model still exists
        if (!$model) {
            return false;
        }
        
        // Check if old values exist
        if (!$auditLog->old_values) {
            return false;
        }
        
        // Check if model hasn't been deleted
        if (method_exists($model, 'trashed') && $model->trashed()) {
            return false;
        }
        
        // Check if there are no conflicting changes since this audit
        $laterChanges = AuditLog::where('auditable_type', $auditLog->auditable_type)
            ->where('auditable_id', $auditLog->auditable_id)
            ->where('created_at', '>', $auditLog->created_at)
            ->exists();
        
        return !$laterChanges;
    }

    /**
     * Get rollback warnings.
     */
    private function getRollbackWarnings($model, AuditLog $auditLog): array
    {
        $warnings = [];
        
        // Check for dependent configurations
        if ($model instanceof UtilityService) {
            $activeConfigs = ServiceConfiguration::where('utility_service_id', $model->id)
                ->where('is_active', true)
                ->count();
                
            if ($activeConfigs > 0) {
                $warnings[] = "This service has {$activeConfigs} active configurations that may be affected.";
            }
        }
        
        // Check for recent meter readings
        if ($model instanceof ServiceConfiguration) {
            $recentReadings = $model->meters()
                ->whereHas('readings', function ($q) {
                    $q->where('created_at', '>', now()->subDays(7));
                })
                ->count();
                
            if ($recentReadings > 0) {
                $warnings[] = "This configuration has meters with recent readings that may be affected.";
            }
        }
        
        // Check for billing implications
        $warnings[] = "Rolling back this configuration may affect billing calculations.";
        
        return $warnings;
    }

    /**
     * Get change type for display.
     */
    private function getChangeType($oldValue, $newValue): string
    {
        if ($oldValue === null && $newValue !== null) {
            return 'added';
        }
        
        if ($oldValue !== null && $newValue === null) {
            return 'removed';
        }
        
        if (is_numeric($oldValue) && is_numeric($newValue)) {
            return $newValue > $oldValue ? 'increased' : 'decreased';
        }
        
        return 'modified';
    }
}