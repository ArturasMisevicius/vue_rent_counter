<?php

declare(strict_types=1);

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Models\ServiceConfiguration;
use App\Models\UtilityService;
use App\ValueObjects\Audit\ConfigurationChange;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Configuration Change Auditor
 * 
 * Tracks and analyzes configuration changes for universal services
 * with rollback capabilities and change impact assessment.
 */
final readonly class ConfigurationChangeAuditor
{
    /**
     * Get configuration changes with rollback capabilities.
     */
    public function getChanges(
        ?int $tenantId,
        Carbon $startDate,
        Carbon $endDate,
        array $serviceTypes = [],
    ): Collection {
        $query = AuditLog::query()
            ->with(['user', 'auditable'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('auditable_type', [UtilityService::class, ServiceConfiguration::class])
            ->orderBy('created_at', 'desc');
            
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }
        
        return $query->get()->map(function (AuditLog $auditLog) {
            return new ConfigurationChange(
                id: $auditLog->id,
                modelType: $auditLog->auditable_type,
                modelId: $auditLog->auditable_id,
                event: $auditLog->event,
                changes: $auditLog->getChanges(),
                userId: $auditLog->user_id,
                userName: $auditLog->user?->name ?? 'System',
                timestamp: $auditLog->created_at,
                canRollback: $this->canRollback($auditLog),
                impactAssessment: $this->assessImpact($auditLog),
            );
        });
    }

    /**
     * Rollback a configuration change.
     */
    public function rollback(int $auditLogId, int $userId, string $reason): bool
    {
        $auditLog = AuditLog::findOrFail($auditLogId);
        
        if (!$this->canRollback($auditLog)) {
            throw new \InvalidArgumentException('This change cannot be rolled back');
        }
        
        return DB::transaction(function () use ($auditLog, $userId, $reason) {
            $model = $auditLog->auditable;
            
            if (!$model) {
                throw new \RuntimeException('Original model no longer exists');
            }
            
            // Restore old values
            if ($auditLog->old_values) {
                $model->fill($auditLog->old_values);
                $model->save();
                
                // Create rollback audit entry
                AuditLog::create([
                    'tenant_id' => $auditLog->tenant_id,
                    'user_id' => $userId,
                    'auditable_type' => $auditLog->auditable_type,
                    'auditable_id' => $auditLog->auditable_id,
                    'event' => 'rollback',
                    'old_values' => $auditLog->new_values,
                    'new_values' => $auditLog->old_values,
                    'notes' => "Rollback of change #{$auditLog->id}: {$reason}",
                ]);
                
                return true;
            }
            
            return false;
        });
    }

    /**
     * Check if a change can be rolled back.
     */
    private function canRollback(AuditLog $auditLog): bool
    {
        // Can't rollback if no old values
        if (!$auditLog->old_values) {
            return false;
        }
        
        // Can't rollback creation events
        if ($auditLog->event === 'created') {
            return false;
        }
        
        // Can't rollback if model was deleted
        if (!$auditLog->auditable) {
            return false;
        }
        
        // Can't rollback if there are newer changes
        $newerChanges = AuditLog::where('auditable_type', $auditLog->auditable_type)
            ->where('auditable_id', $auditLog->auditable_id)
            ->where('created_at', '>', $auditLog->created_at)
            ->exists();
            
        if ($newerChanges) {
            return false;
        }
        
        // Can't rollback changes older than 24 hours
        if ($auditLog->created_at->lt(now()->subDay())) {
            return false;
        }
        
        return true;
    }

    /**
     * Assess the impact of a configuration change.
     */
    private function assessImpact(AuditLog $auditLog): array
    {
        $impact = [
            'level' => 'low',
            'affected_areas' => [],
            'risk_factors' => [],
        ];
        
        if (!$auditLog->new_values || !$auditLog->old_values) {
            return $impact;
        }
        
        $changes = $auditLog->getChanges();
        
        // Assess impact based on changed fields
        foreach ($changes as $field => $change) {
            switch ($field) {
                case 'pricing_model':
                case 'rate_schedule':
                    $impact['level'] = 'high';
                    $impact['affected_areas'][] = 'billing_calculations';
                    $impact['risk_factors'][] = 'pricing_changes_affect_all_tenants';
                    break;
                    
                case 'configuration':
                    $impact['level'] = max($impact['level'], 'medium');
                    $impact['affected_areas'][] = 'service_behavior';
                    break;
                    
                case 'name':
                case 'description':
                    $impact['affected_areas'][] = 'user_interface';
                    break;
                    
                case 'is_active':
                    $impact['level'] = 'high';
                    $impact['affected_areas'][] = 'service_availability';
                    $impact['risk_factors'][] = 'service_disruption_possible';
                    break;
            }
        }
        
        // Remove duplicates
        $impact['affected_areas'] = array_unique($impact['affected_areas']);
        $impact['risk_factors'] = array_unique($impact['risk_factors']);
        
        return $impact;
    }

    /**
     * Get configuration change history for a specific model.
     */
    public function getModelHistory(string $modelType, int $modelId, ?int $tenantId = null): Collection
    {
        $query = AuditLog::query()
            ->with(['user'])
            ->where('auditable_type', $modelType)
            ->where('auditable_id', $modelId)
            ->orderBy('created_at', 'desc');
            
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }
        
        return $query->get();
    }

    /**
     * Get recent configuration changes across all services.
     */
    public function getRecentChanges(?int $tenantId = null, int $limit = 50): Collection
    {
        $query = AuditLog::query()
            ->with(['user', 'auditable'])
            ->whereIn('auditable_type', [UtilityService::class, ServiceConfiguration::class])
            ->orderBy('created_at', 'desc')
            ->limit($limit);
            
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }
        
        return $query->get();
    }
}