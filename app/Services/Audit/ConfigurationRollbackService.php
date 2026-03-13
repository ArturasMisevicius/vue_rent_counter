<?php

declare(strict_types=1);

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Models\ServiceConfiguration;
use App\Models\UtilityService;
use App\Notifications\ConfigurationRollbackNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Configuration Rollback Service
 * 
 * Provides safe rollback capabilities for universal service configurations
 * with validation, impact analysis, and audit trail maintenance.
 */
final readonly class ConfigurationRollbackService
{
    public function __construct(
        private UniversalServiceChangeTracker $changeTracker,
    ) {}

    /**
     * Validate if a rollback is safe to perform.
     */
    public function validateRollback(int $auditLogId): array
    {
        $rollbackData = $this->changeTracker->getConfigurationRollbackData($auditLogId);
        
        if (!$rollbackData) {
            return [
                'valid' => false,
                'errors' => ['Audit log not found or invalid for rollback'],
                'warnings' => [],
            ];
        }
        
        $errors = [];
        $warnings = [];
        
        // Check if model still exists
        if (!$rollbackData['can_rollback']) {
            $errors[] = 'Configuration cannot be rolled back due to subsequent changes';
        }
        
        // Validate rollback values
        $validationResult = $this->validateRollbackValues(
            $rollbackData['model_type'],
            $rollbackData['rollback_values']
        );
        
        if (!$validationResult['valid']) {
            $errors = array_merge($errors, $validationResult['errors']);
        }
        
        // Check for impact on dependent systems
        $impactAnalysis = $this->analyzeRollbackImpact($rollbackData);
        $warnings = array_merge($warnings, $impactAnalysis['warnings']);
        
        if ($impactAnalysis['has_critical_impact']) {
            $errors[] = 'Rollback would have critical impact on dependent systems';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => array_merge($warnings, $rollbackData['rollback_warnings']),
            'impact_analysis' => $impactAnalysis,
        ];
    }

    /**
     * Perform a safe configuration rollback with full audit trail.
     */
    public function performRollback(
        int $auditLogId,
        int $userId,
        ?string $reason = null,
        bool $notifyStakeholders = true,
    ): array {
        // Validate rollback first
        $validation = $this->validateRollback($auditLogId);
        
        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => 'Rollback validation failed',
                'errors' => $validation['errors'],
            ];
        }
        
        $auditLog = AuditLog::findOrFail($auditLogId);
        $model = $auditLog->auditable_type::findOrFail($auditLog->auditable_id);
        
        try {
            return DB::transaction(function () use ($auditLog, $model, $userId, $reason, $notifyStakeholders) {
                // Store current state before rollback
                $preRollbackState = $model->toArray();
                
                // Perform the rollback
                $model->update($auditLog->old_values);
                
                // Create comprehensive rollback audit entry
                $rollbackAudit = AuditLog::create([
                    'auditable_type' => $auditLog->auditable_type,
                    'auditable_id' => $auditLog->auditable_id,
                    'tenant_id' => $auditLog->tenant_id,
                    'user_id' => $userId,
                    'event' => 'rollback',
                    'old_values' => $preRollbackState,
                    'new_values' => $auditLog->old_values,
                    'notes' => $this->buildRollbackNotes($reason, $auditLog),
                    'metadata' => [
                        'original_audit_id' => $auditLog->id,
                        'rollback_timestamp' => now()->toISOString(),
                        'rollback_reason' => $reason,
                        'rollback_user_id' => $userId,
                        'impact_analysis' => $this->analyzeRollbackImpact(
                            $this->changeTracker->getConfigurationRollbackData($auditLog->id)
                        ),
                    ],
                ]);
                
                // Log the rollback operation
                Log::info('Configuration rollback performed', [
                    'audit_log_id' => $auditLog->id,
                    'rollback_audit_id' => $rollbackAudit->id,
                    'model_type' => $auditLog->auditable_type,
                    'model_id' => $auditLog->auditable_id,
                    'user_id' => $userId,
                    'reason' => $reason,
                ]);
                
                // Notify stakeholders if requested
                if ($notifyStakeholders) {
                    $this->notifyStakeholders($rollbackAudit, $model);
                }
                
                return [
                    'success' => true,
                    'message' => 'Configuration successfully rolled back',
                    'rollback_audit_id' => $rollbackAudit->id,
                    'model' => $model->fresh(),
                ];
            });
        } catch (\Exception $e) {
            Log::error('Configuration rollback failed', [
                'audit_log_id' => $auditLogId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Rollback failed: ' . $e->getMessage(),
                'errors' => [$e->getMessage()],
            ];
        }
    }

    /**
     * Get rollback history for a specific model.
     */
    public function getRollbackHistory(string $modelType, int $modelId): array
    {
        $rollbacks = AuditLog::where('auditable_type', $modelType)
            ->where('auditable_id', $modelId)
            ->where('event', 'rollback')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return $rollbacks->map(function (AuditLog $rollback) {
            $originalAuditId = $rollback->metadata['original_audit_id'] ?? null;
            $originalAudit = $originalAuditId ? AuditLog::find($originalAuditId) : null;
            
            return [
                'rollback_id' => $rollback->id,
                'performed_at' => $rollback->created_at,
                'performed_by' => $rollback->user_id,
                'reason' => $rollback->metadata['rollback_reason'] ?? 'No reason provided',
                'original_change' => $originalAudit ? [
                    'id' => $originalAudit->id,
                    'event' => $originalAudit->event,
                    'changed_at' => $originalAudit->created_at,
                    'changed_by' => $originalAudit->user_id,
                ] : null,
                'fields_rolled_back' => array_keys($rollback->new_values ?? []),
            ];
        })->toArray();
    }

    /**
     * Analyze potential impact of a rollback operation.
     */
    private function analyzeRollbackImpact(array $rollbackData): array
    {
        $warnings = [];
        $hasCriticalImpact = false;
        
        $modelType = $rollbackData['model_type'];
        $modelId = $rollbackData['model_id'];
        
        if ($modelType === UtilityService::class) {
            $impact = $this->analyzeUtilityServiceRollbackImpact($modelId, $rollbackData);
        } elseif ($modelType === ServiceConfiguration::class) {
            $impact = $this->analyzeServiceConfigurationRollbackImpact($modelId, $rollbackData);
        } else {
            $impact = ['warnings' => [], 'has_critical_impact' => false];
        }
        
        return [
            'warnings' => $impact['warnings'],
            'has_critical_impact' => $impact['has_critical_impact'],
            'affected_systems' => $impact['affected_systems'] ?? [],
            'mitigation_steps' => $impact['mitigation_steps'] ?? [],
        ];
    }

    /**
     * Analyze impact of rolling back a utility service.
     */
    private function analyzeUtilityServiceRollbackImpact(int $serviceId, array $rollbackData): array
    {
        $warnings = [];
        $hasCriticalImpact = false;
        $affectedSystems = [];
        
        // Check active configurations using this service
        $activeConfigs = ServiceConfiguration::where('utility_service_id', $serviceId)
            ->where('is_active', true)
            ->count();
        
        if ($activeConfigs > 0) {
            $warnings[] = "Rollback will affect {$activeConfigs} active service configurations";
            $affectedSystems[] = 'Service Configurations';
        }
        
        // Check if pricing model is changing
        $oldValues = $rollbackData['rollback_values'];
        $currentValues = $rollbackData['current_values'];
        
        if (isset($oldValues['pricing_model']) && 
            $oldValues['pricing_model'] !== $currentValues['pricing_model']) {
            $warnings[] = 'Pricing model will be reverted, affecting billing calculations';
            $affectedSystems[] = 'Billing System';
            $hasCriticalImpact = true;
        }
        
        // Check if calculation formula is changing
        if (isset($oldValues['calculation_formula']) && 
            $oldValues['calculation_formula'] !== $currentValues['calculation_formula']) {
            $warnings[] = 'Calculation formula will be reverted, affecting all future calculations';
            $affectedSystems[] = 'Calculation Engine';
            $hasCriticalImpact = true;
        }
        
        return [
            'warnings' => $warnings,
            'has_critical_impact' => $hasCriticalImpact,
            'affected_systems' => $affectedSystems,
            'mitigation_steps' => [
                'Review all active configurations after rollback',
                'Recalculate any pending invoices',
                'Notify affected tenants of changes',
            ],
        ];
    }

    /**
     * Analyze impact of rolling back a service configuration.
     */
    private function analyzeServiceConfigurationRollbackImpact(int $configId, array $rollbackData): array
    {
        $warnings = [];
        $hasCriticalImpact = false;
        $affectedSystems = [];
        
        $config = ServiceConfiguration::find($configId);
        
        if (!$config) {
            return ['warnings' => [], 'has_critical_impact' => false];
        }
        
        // Check for recent meter readings
        $recentReadings = $config->meters()
            ->whereHas('readings', function ($q) {
                $q->where('created_at', '>', now()->subDays(7));
            })
            ->count();
        
        if ($recentReadings > 0) {
            $warnings[] = "Configuration has {$recentReadings} meters with recent readings";
            $affectedSystems[] = 'Meter Reading System';
        }
        
        // Check if rate schedule is changing
        $oldValues = $rollbackData['rollback_values'];
        $currentValues = $rollbackData['current_values'];
        
        if (isset($oldValues['rate_schedule']) && 
            $oldValues['rate_schedule'] !== $currentValues['rate_schedule']) {
            $warnings[] = 'Rate schedule will be reverted, affecting billing calculations';
            $affectedSystems[] = 'Billing System';
            $hasCriticalImpact = true;
        }
        
        return [
            'warnings' => $warnings,
            'has_critical_impact' => $hasCriticalImpact,
            'affected_systems' => $affectedSystems,
            'mitigation_steps' => [
                'Verify meter readings after rollback',
                'Recalculate affected invoices',
                'Update tenant notifications',
            ],
        ];
    }

    /**
     * Validate rollback values against current model constraints.
     */
    private function validateRollbackValues(string $modelType, array $rollbackValues): array
    {
        $errors = [];
        
        // Create a temporary model instance to validate against
        $tempModel = new $modelType();
        
        try {
            // Validate using model's validation rules if available
            if (method_exists($tempModel, 'getRules')) {
                $rules = $tempModel->getRules();
                $validator = validator($rollbackValues, $rules);
                
                if ($validator->fails()) {
                    $errors = array_merge($errors, $validator->errors()->all());
                }
            }
            
            // Additional custom validations
            if ($modelType === UtilityService::class) {
                $errors = array_merge($errors, $this->validateUtilityServiceRollback($rollbackValues));
            } elseif ($modelType === ServiceConfiguration::class) {
                $errors = array_merge($errors, $this->validateServiceConfigurationRollback($rollbackValues));
            }
        } catch (\Exception $e) {
            $errors[] = 'Validation failed: ' . $e->getMessage();
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Validate utility service rollback values.
     */
    private function validateUtilityServiceRollback(array $values): array
    {
        $errors = [];
        
        // Validate pricing model
        if (isset($values['pricing_model'])) {
            $validModels = ['fixed', 'consumption_based', 'tiered', 'hybrid', 'custom_formula'];
            if (!in_array($values['pricing_model'], $validModels)) {
                $errors[] = 'Invalid pricing model in rollback values';
            }
        }
        
        // Validate calculation formula if present
        if (isset($values['calculation_formula']) && !empty($values['calculation_formula'])) {
            // Basic formula validation - could be enhanced with actual formula parsing
            if (!is_string($values['calculation_formula'])) {
                $errors[] = 'Calculation formula must be a string';
            }
        }
        
        return $errors;
    }

    /**
     * Validate service configuration rollback values.
     */
    private function validateServiceConfigurationRollback(array $values): array
    {
        $errors = [];
        
        // Validate rate schedule format
        if (isset($values['rate_schedule'])) {
            if (!is_array($values['rate_schedule']) && !is_null($values['rate_schedule'])) {
                $errors[] = 'Rate schedule must be an array or null';
            }
        }
        
        // Validate effective dates
        if (isset($values['effective_from'])) {
            try {
                \Carbon\Carbon::parse($values['effective_from']);
            } catch (\Exception $e) {
                $errors[] = 'Invalid effective_from date format';
            }
        }
        
        return $errors;
    }

    /**
     * Build comprehensive rollback notes.
     */
    private function buildRollbackNotes(?string $reason, AuditLog $originalAudit): string
    {
        $notes = "Configuration rollback performed\n";
        $notes .= "Original change ID: {$originalAudit->id}\n";
        $notes .= "Original change date: {$originalAudit->created_at}\n";
        $notes .= "Original change by: " . ($originalAudit->user_id ?? 'System') . "\n";
        
        if ($reason) {
            $notes .= "Rollback reason: {$reason}\n";
        }
        
        return $notes;
    }

    /**
     * Notify stakeholders about the rollback.
     */
    private function notifyStakeholders(AuditLog $rollbackAudit, $model): void
    {
        try {
            // Notify administrators and managers
            $users = \App\Models\User::whereHas('roles', function ($q) {
                $q->whereIn('name', ['admin', 'manager']);
            })->get();
            
            Notification::send($users, new ConfigurationRollbackNotification(
                $rollbackAudit,
                $model
            ));
        } catch (\Exception $e) {
            Log::warning('Failed to send rollback notifications', [
                'rollback_audit_id' => $rollbackAudit->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}