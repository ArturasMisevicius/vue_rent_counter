<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RollbackValidationRequest;
use App\Http\Requests\Api\PerformRollbackRequest;
use App\Services\Audit\ConfigurationRollbackService;
use App\Services\Audit\UniversalServiceChangeTracker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Configuration Rollback API Controller
 * 
 * Provides REST API endpoints for configuration rollback operations
 * including validation, execution, and history management.
 */
final class ConfigurationRollbackController extends Controller
{
    public function __construct(
        private readonly ConfigurationRollbackService $rollbackService,
        private readonly UniversalServiceChangeTracker $changeTracker,
    ) {}

    /**
     * Validate if a rollback operation is safe to perform.
     */
    public function validateRollback(RollbackValidationRequest $request): JsonResponse
    {
        $auditLogId = (int) $request->validated('audit_log_id');
        
        $validation = $this->rollbackService->validateRollback($auditLogId);
        
        return response()->json([
            'success' => true,
            'data' => $validation,
        ]);
    }

    /**
     * Perform a configuration rollback operation.
     */
    public function performRollback(PerformRollbackRequest $request): JsonResponse
    {
        $data = $request->validated();
        
        $result = $this->rollbackService->performRollback(
            auditLogId: (int) $data['audit_log_id'],
            userId: auth()->id(),
            reason: $data['reason'] ?? null,
            notifyStakeholders: $data['notify_stakeholders'] ?? true,
        );
        
        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => $result['success'] ? [
                'rollback_audit_id' => $result['rollback_audit_id'] ?? null,
                'model' => $result['model'] ?? null,
            ] : null,
            'errors' => $result['errors'] ?? [],
        ], $result['success'] ? 200 : 422);
    }

    /**
     * Get rollback history for a specific model.
     */
    public function getRollbackHistory(Request $request): JsonResponse
    {
        $request->validate([
            'model_type' => 'required|string|in:App\\Models\\UtilityService,App\\Models\\ServiceConfiguration',
            'model_id' => 'required|integer|min:1',
        ]);
        
        $history = $this->rollbackService->getRollbackHistory(
            modelType: $request->input('model_type'),
            modelId: (int) $request->input('model_id'),
        );
        
        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    /**
     * Get configuration rollback data for a specific audit entry.
     */
    public function getRollbackData(Request $request): JsonResponse
    {
        $request->validate([
            'audit_log_id' => 'required|integer|min:1',
        ]);
        
        $auditLogId = (int) $request->input('audit_log_id');
        
        $rollbackData = $this->changeTracker->getConfigurationRollbackData($auditLogId);
        
        if (!$rollbackData) {
            return response()->json([
                'success' => false,
                'message' => __('dashboard.audit.notifications.rollback_data_not_found'),
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $rollbackData,
        ]);
    }

    /**
     * Get change tracking data for a service or configuration.
     */
    public function getChangeHistory(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string|in:service,configuration,tenant',
            'id' => 'required_unless:type,tenant|integer|min:1',
            'tenant_id' => 'required_if:type,tenant|integer|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'service_types' => 'nullable|array',
            'service_types.*' => 'string',
        ]);
        
        $type = $request->input('type');
        $startDate = $request->input('start_date') ? \Carbon\Carbon::parse($request->input('start_date')) : null;
        $endDate = $request->input('end_date') ? \Carbon\Carbon::parse($request->input('end_date')) : null;
        
        $changes = match ($type) {
            'service' => $this->changeTracker->trackServiceChanges(
                serviceId: (int) $request->input('id'),
                tenantId: auth()->user()->currentTeam->id,
                startDate: $startDate,
                endDate: $endDate,
            ),
            'configuration' => $this->changeTracker->trackConfigurationChanges(
                configurationId: (int) $request->input('id'),
                tenantId: auth()->user()->currentTeam->id,
                startDate: $startDate,
                endDate: $endDate,
            ),
            'tenant' => $this->changeTracker->getTenantChanges(
                tenantId: (int) $request->input('tenant_id'),
                startDate: $startDate,
                endDate: $endDate,
                serviceTypes: $request->input('service_types', []),
            ),
        };
        
        return response()->json([
            'success' => true,
            'data' => $changes->toArray(),
        ]);
    }

    /**
     * Analyze change patterns for a tenant.
     */
    public function analyzeChangePatterns(Request $request): JsonResponse
    {
        $request->validate([
            'tenant_id' => 'required|integer|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);
        
        $tenantId = (int) $request->input('tenant_id');
        $startDate = $request->input('start_date') ? \Carbon\Carbon::parse($request->input('start_date')) : null;
        $endDate = $request->input('end_date') ? \Carbon\Carbon::parse($request->input('end_date')) : null;
        
        // Ensure user can access this tenant's data
        if ($tenantId !== auth()->user()->currentTeam->id && !auth()->user()->hasRole('super_admin')) {
            return response()->json([
                'success' => false,
                'message' => __('dashboard.audit.notifications.unauthorized_access'),
            ], 403);
        }
        
        $analysis = $this->changeTracker->analyzeChangePatterns(
            tenantId: $tenantId,
            startDate: $startDate,
            endDate: $endDate,
        );
        
        return response()->json([
            'success' => true,
            'data' => $analysis,
        ]);
    }

    /**
     * Bulk rollback operation for multiple audit entries.
     */
    public function bulkRollback(Request $request): JsonResponse
    {
        $request->validate([
            'audit_log_ids' => 'required|array|min:1|max:10',
            'audit_log_ids.*' => 'integer|min:1',
            'reason' => 'required|string|max:500',
            'notify_stakeholders' => 'boolean',
        ]);
        
        $auditLogIds = $request->input('audit_log_ids');
        $reason = $request->input('reason');
        $notifyStakeholders = $request->input('notify_stakeholders', false);
        $userId = auth()->id();
        
        $results = [];
        $successCount = 0;
        
        foreach ($auditLogIds as $auditLogId) {
            $result = $this->rollbackService->performRollback(
                auditLogId: (int) $auditLogId,
                userId: $userId,
                reason: "Bulk rollback: {$reason}",
                notifyStakeholders: false, // Don't spam notifications for bulk operations
            );
            
            $results[] = [
                'audit_log_id' => $auditLogId,
                'success' => $result['success'],
                'message' => $result['message'],
                'errors' => $result['errors'] ?? [],
            ];
            
            if ($result['success']) {
                $successCount++;
            }
        }
        
        // Send single notification for bulk operation if requested
        if ($notifyStakeholders && $successCount > 0) {
            // This would trigger a bulk rollback notification
            // Implementation depends on notification requirements
        }
        
        return response()->json([
            'success' => $successCount > 0,
            'message' => $successCount === count($auditLogIds) 
                ? __('dashboard.audit.notifications.bulk_rollback_success', ['count' => $successCount])
                : __('dashboard.audit.notifications.bulk_rollback_partial', [
                    'success' => $successCount,
                    'failed' => count($auditLogIds) - $successCount,
                ]),
            'data' => [
                'total_processed' => count($auditLogIds),
                'successful' => $successCount,
                'failed' => count($auditLogIds) - $successCount,
                'results' => $results,
            ],
        ]);
    }
}