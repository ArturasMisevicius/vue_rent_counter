<?php

declare(strict_types=1);

namespace App\Policies;

use App\Contracts\WorkflowStrategyInterface;
use App\Enums\UserRole;
use App\Enums\ValidationStatus;
use App\Models\MeterReading;
use App\Models\User;
use App\Services\TenantBoundaryService;
use App\Services\Workflows\PermissiveWorkflowStrategy;
use App\ValueObjects\AuthorizationContext;
use App\ValueObjects\PolicyResult;

/**
 * MeterReadingPolicy
 * 
 * Authorization policy for meter reading operations with configurable workflow support.
 * 
 * Features:
 * - Configurable workflow strategies (Permissive, Truth-but-Verify)
 * - Comprehensive tenant boundary validation
 * - Structured authorization results
 * - Performance optimized with caching
 * - Comprehensive audit logging
 * 
 * Requirements:
 * - 11.1: Verify user's role using Laravel Policies
 * - 11.3: Manager can create and update meter readings
 * - 11.4: Tenant can only view their own meter readings
 * - 7.3: Cross-tenant access prevention
 * - Gold Master v7.0: Configurable workflow support
 * 
 * @package App\Policies
 */
final class MeterReadingPolicy extends BasePolicy
{
    private readonly WorkflowStrategyInterface $workflowStrategy;

    public function __construct(
        private readonly TenantBoundaryService $tenantBoundaryService,
        ?WorkflowStrategyInterface $workflowStrategy = null
    ) {
        // Default to Permissive workflow if none provided
        $this->workflowStrategy = $workflowStrategy ?? new PermissiveWorkflowStrategy();
    }

    /**
     * Role groups specific to meter reading operations.
     */
    private const READING_CREATORS = [UserRole::SUPERADMIN, UserRole::ADMIN, UserRole::MANAGER, UserRole::TENANT];
    private const READING_MANAGERS = [UserRole::SUPERADMIN, UserRole::ADMIN, UserRole::MANAGER];

    /**
     * Determine whether the user can view any meter readings.
     * 
     * All authenticated roles can view meter readings (filtered by tenant scope).
     * 
     * Requirements: 11.1, 11.4
     * 
     * @param User $user The authenticated user
     * @return bool True if authorized
     */
    public function viewAny(User $user): bool
    {
        return $this->hasAnyRole($user, self::ALL_ROLES);
    }

    /**
     * Determine whether the user can view the meter reading.
     * 
     * Tenants can only view meter readings for their properties.
     * Managers can view readings within their tenant.
     * 
     * Requirements: 11.1, 11.4, 7.3
     * 
     * @param User $user The authenticated user
     * @param MeterReading $meterReading The meter reading to view
     * @return bool True if authorized
     */
    public function view(User $user, MeterReading $meterReading): bool
    {
        $context = AuthorizationContext::forResource(
            $user, 
            'view', 
            'MeterReading', 
            $meterReading->id
        );

        $result = $this->authorizeViewAccess($user, $meterReading);
        
        if ($result->isAuthorized()) {
            $this->logAuthorizationSuccess($context, $result);
        }

        return $result->toBool();
    }

    /**
     * Determine whether the user can create meter readings.
     * 
     * Admins, Managers, and Tenants can create meter readings.
     * Tenants can submit readings for manager approval (configurable workflow).
     * 
     * Requirements: 11.1, 11.3, Gold Master v7.0
     * 
     * @param User $user The authenticated user
     * @return bool True if authorized
     */
    public function create(User $user): bool
    {
        $context = AuthorizationContext::forOperation($user, 'create', [
            'workflow' => $this->workflowStrategy->getWorkflowName(),
        ]);

        $canCreate = $this->hasAnyRole($user, self::READING_CREATORS);
        
        if ($canCreate && $user->role === UserRole::TENANT) {
            $this->logSensitiveOperation('create_attempt', $user, null, [
                'workflow' => $this->workflowStrategy->getWorkflowName(),
                'requires_approval' => true,
            ]);
        }
        
        return $canCreate;
    }

    /**
     * Determine whether the user can create a meter reading for a specific meter.
     * 
     * This method provides additional validation for tenant submissions.
     * 
     * @param User $user The authenticated user
     * @param int $meterId The meter ID
     * @return bool True if authorized
     */
    public function createForMeter(User $user, int $meterId): bool
    {
        if (!$this->create($user)) {
            return false;
        }

        // Additional validation for tenants
        if ($user->role === UserRole::TENANT) {
            return $this->tenantBoundaryService->canTenantSubmitReadingForMeter($user, $meterId);
        }

        return true;
    }

    /**
     * Determine whether the user can update the meter reading.
     * 
     * Admins and Managers can update meter readings.
     * Managers are restricted to their tenant scope.
     * Tenants can update their OWN readings ONLY IF status is 'pending' (Permissive workflow).
     * 
     * Requirements: 11.1, 11.3, 7.3, Gold Master v7.0
     * 
     * @param User $user The authenticated user
     * @param MeterReading $meterReading The meter reading to update
     * @return bool True if authorized
     */
    public function update(User $user, MeterReading $meterReading): bool
    {
        $context = AuthorizationContext::forResource(
            $user, 
            'update', 
            'MeterReading', 
            $meterReading->id,
            ['workflow' => $this->workflowStrategy->getWorkflowName()]
        );

        $result = $this->authorizeUpdateAccess($user, $meterReading);
        
        if ($result->isAuthorized()) {
            $this->logAuthorizationSuccess($context, $result);
        }

        return $result->toBool();
    }

    /**
     * Determine whether the user can approve/validate the meter reading.
     * 
     * Only managers and above can approve tenant-submitted readings.
     * 
     * Requirements: Gold Master v7.0 workflow support
     * 
     * @param User $user The authenticated user
     * @param MeterReading $meterReading The meter reading to approve
     * @return bool True if authorized
     */
    public function approve(User $user, MeterReading $meterReading): bool
    {
        $result = $this->authorizeApprovalAccess($user, $meterReading);
        
        if ($result->isAuthorized()) {
            $this->logSensitiveOperation('approve', $user, $meterReading, [
                'validation_status' => $meterReading->validation_status->value,
                'input_method' => $meterReading->input_method?->value,
                'workflow' => $this->workflowStrategy->getWorkflowName(),
            ]);
        }

        return $result->toBool();
    }

    /**
     * Determine whether the user can reject the meter reading.
     * 
     * Only managers and above can reject tenant-submitted readings.
     * 
     * Requirements: Gold Master v7.0 workflow support
     * 
     * @param User $user The authenticated user
     * @param MeterReading $meterReading The meter reading to reject
     * @return bool True if authorized
     */
    public function reject(User $user, MeterReading $meterReading): bool
    {
        // Same logic as approve for now
        return $this->approve($user, $meterReading);
    }

    /**
     * Determine whether the user can delete the meter reading.
     * 
     * Uses configurable workflow strategy to determine tenant permissions.
     * Admins can delete meter readings within their tenant scope.
     * Tenants can delete their OWN readings ONLY IF status is 'pending' (Permissive workflow).
     * 
     * Requirements: 11.1, Gold Master v7.0
     * 
     * @param User $user The authenticated user
     * @param MeterReading $meterReading The meter reading to delete
     * @return bool True if authorized
     */
    public function delete(User $user, MeterReading $meterReading): bool
    {
        $context = AuthorizationContext::forResource(
            $user, 
            'delete', 
            'MeterReading', 
            $meterReading->id,
            ['workflow' => $this->workflowStrategy->getWorkflowName()]
        );

        $result = $this->authorizeDeleteAccess($user, $meterReading);
        
        if ($result->isAuthorized()) {
            $this->logAuthorizationSuccess($context, $result);
        }

        return $result->toBool();
    }

    /**
     * Determine whether the user can restore the meter reading.
     * 
     * Only admins and superadmins can restore meter readings.
     * 
     * Requirements: 11.1
     * 
     * @param User $user The authenticated user
     * @param MeterReading $meterReading The meter reading to restore
     * @return bool True if authorized
     */
    public function restore(User $user, MeterReading $meterReading): bool
    {
        $result = $this->authorizeAdminAccess($user, $meterReading);
        
        if ($result->isAuthorized()) {
            $this->logSensitiveOperation('restore', $user, $meterReading);
        }

        return $result->toBool();
    }

    /**
     * Determine whether the user can permanently delete the meter reading.
     * 
     * Only superadmins can force delete meter readings.
     * 
     * Requirements: 11.1
     * 
     * @param User $user The authenticated user
     * @param MeterReading $meterReading The meter reading to force delete
     * @return bool True if authorized
     */
    public function forceDelete(User $user, MeterReading $meterReading): bool
    {
        $canForceDelete = $this->isSuperadmin($user);
        
        if ($canForceDelete) {
            $this->logSensitiveOperation('forceDelete', $user, $meterReading, [
                'validation_status' => $meterReading->validation_status->value,
                'input_method' => $meterReading->input_method?->value,
                'entered_by' => $meterReading->entered_by,
            ]);
        }
        
        return $canForceDelete;
    }

    /**
     * Determine whether the user can replicate the meter reading.
     * 
     * Used by Filament for record duplication.
     * Only managers and above can replicate readings.
     * 
     * @param User $user The authenticated user
     * @param MeterReading $meterReading The meter reading to replicate
     * @return bool True if authorized
     */
    public function replicate(User $user, MeterReading $meterReading): bool
    {
        return $this->isManagerOrHigher($user) && 
               ($this->isSuperadmin($user) || $this->belongsToUserTenant($user, $meterReading));
    }

    /**
     * Determine whether the user can export meter readings.
     * 
     * All roles can export readings within their scope.
     * 
     * @param User $user The authenticated user
     * @return bool True if authorized
     */
    public function export(User $user): bool
    {
        return $this->hasAnyRole($user, self::ALL_ROLES);
    }

    /**
     * Determine whether the user can import meter readings.
     * 
     * Only managers and above can import readings.
     * 
     * @param User $user The authenticated user
     * @return bool True if authorized
     */
    public function import(User $user): bool
    {
        return $this->isManagerOrHigher($user);
    }

    // ========================================
    // Private Helper Methods
    // ========================================

    /**
     * Authorize view access for a meter reading.
     * 
     * @param User $user The authenticated user
     * @param MeterReading $meterReading The meter reading
     * @return PolicyResult The authorization result
     */
    private function authorizeViewAccess(User $user, MeterReading $meterReading): PolicyResult
    {
        // Superadmin can view any meter reading
        if ($this->isSuperadmin($user)) {
            return PolicyResult::allow('Superadmin access');
        }

        // Admins and managers can view readings within their tenant
        if ($this->isAdmin($user) || $user->role === UserRole::MANAGER) {
            if ($this->belongsToUserTenant($user, $meterReading)) {
                return PolicyResult::allow('Same tenant access');
            }
            return PolicyResult::deny('Different tenant');
        }

        // Tenants can view meter readings for their properties
        if ($user->role === UserRole::TENANT) {
            if ($this->tenantBoundaryService->canTenantAccessMeterReading($user, $meterReading)) {
                return PolicyResult::allow('Tenant property access');
            }
            return PolicyResult::deny('Not tenant property');
        }

        return PolicyResult::deny('Insufficient role');
    }

    /**
     * Authorize update access for a meter reading.
     * 
     * @param User $user The authenticated user
     * @param MeterReading $meterReading The meter reading
     * @return PolicyResult The authorization result
     */
    private function authorizeUpdateAccess(User $user, MeterReading $meterReading): PolicyResult
    {
        // Superadmin can update any meter reading
        if ($this->isSuperadmin($user)) {
            return PolicyResult::allow('Superadmin access');
        }

        // Admins and managers can update readings within their tenant
        if ($this->isAdmin($user) || $user->role === UserRole::MANAGER) {
            if ($this->belongsToUserTenant($user, $meterReading)) {
                return PolicyResult::allow('Same tenant access');
            }
            return PolicyResult::deny('Different tenant');
        }

        // Tenants: use workflow strategy
        if ($user->role === UserRole::TENANT) {
            if ($this->workflowStrategy->canTenantUpdate($user, $meterReading)) {
                return PolicyResult::allow('Workflow allows tenant update', [
                    'workflow' => $this->workflowStrategy->getWorkflowName()
                ]);
            }
            return PolicyResult::deny('Workflow denies tenant update', [
                'workflow' => $this->workflowStrategy->getWorkflowName()
            ]);
        }

        return PolicyResult::deny('Insufficient role');
    }

    /**
     * Authorize delete access for a meter reading.
     * 
     * @param User $user The authenticated user
     * @param MeterReading $meterReading The meter reading
     * @return PolicyResult The authorization result
     */
    private function authorizeDeleteAccess(User $user, MeterReading $meterReading): PolicyResult
    {
        // Superadmin can delete any meter reading
        if ($this->isSuperadmin($user)) {
            return PolicyResult::allow('Superadmin access');
        }

        // Admins can delete meter readings within their tenant
        if ($this->isAdmin($user)) {
            if ($this->belongsToUserTenant($user, $meterReading)) {
                return PolicyResult::allow('Admin same tenant access');
            }
            return PolicyResult::deny('Different tenant');
        }

        // Tenants: use workflow strategy
        if ($user->role === UserRole::TENANT) {
            if ($this->workflowStrategy->canTenantDelete($user, $meterReading)) {
                return PolicyResult::allow('Workflow allows tenant delete', [
                    'workflow' => $this->workflowStrategy->getWorkflowName()
                ]);
            }
            return PolicyResult::deny('Workflow denies tenant delete', [
                'workflow' => $this->workflowStrategy->getWorkflowName()
            ]);
        }

        return PolicyResult::deny('Insufficient role');
    }

    /**
     * Authorize admin-level access for a meter reading.
     * 
     * @param User $user The authenticated user
     * @param MeterReading $meterReading The meter reading
     * @return PolicyResult The authorization result
     */
    private function authorizeAdminAccess(User $user, MeterReading $meterReading): PolicyResult
    {
        // Superadmin can access any meter reading
        if ($this->isSuperadmin($user)) {
            return PolicyResult::allow('Superadmin access');
        }

        // Admins can access meter readings within their tenant
        if ($this->isAdmin($user)) {
            if ($this->belongsToUserTenant($user, $meterReading)) {
                return PolicyResult::allow('Admin same tenant access');
            }
            return PolicyResult::deny('Different tenant');
        }

        return PolicyResult::deny('Insufficient role');
    }

    /**
     * Authorize approval access for a meter reading.
     * 
     * @param User $user The authenticated user
     * @param MeterReading $meterReading The meter reading
     * @return PolicyResult The authorization result
     */
    private function authorizeApprovalAccess(User $user, MeterReading $meterReading): PolicyResult
    {
        // Only managers and above can approve readings
        if (!$this->hasAnyRole($user, self::READING_MANAGERS)) {
            return PolicyResult::deny('Insufficient role for approval');
        }

        // Must be within tenant scope for managers
        if ($user->role === UserRole::MANAGER && !$this->belongsToUserTenant($user, $meterReading)) {
            return PolicyResult::deny('Different tenant');
        }

        // Can only approve readings that require validation
        if (!method_exists($meterReading, 'requiresValidation') || !$meterReading->requiresValidation()) {
            return PolicyResult::deny('Reading does not require validation');
        }

        // Can only approve pending readings
        if ($meterReading->validation_status !== ValidationStatus::PENDING) {
            return PolicyResult::deny('Reading is not pending', [
                'current_status' => $meterReading->validation_status->value
            ]);
        }

        return PolicyResult::allow('Approval authorized');
    }

    /**
     * Log successful authorization with context.
     * 
     * @param AuthorizationContext $context The authorization context
     * @param PolicyResult $result The authorization result
     * @return void
     */
    private function logAuthorizationSuccess(AuthorizationContext $context, PolicyResult $result): void
    {
        if ($result->isAuthorized()) {
            $this->logSensitiveOperation(
                $context->operation, 
                $context->user, 
                null, 
                array_merge($context->additionalData, $result->context)
            );
        }
    }
}