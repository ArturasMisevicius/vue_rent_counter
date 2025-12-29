<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * BasePolicy provides common authorization patterns and utilities.
 * 
 * This abstract class centralizes common policy logic to reduce code duplication
 * and ensure consistent authorization patterns across all policies.
 * 
 * Features:
 * - Role checking utilities
 * - Tenant boundary validation
 * - Audit logging for sensitive operations
 * - Performance optimizations
 * 
 * @package App\Policies
 */
abstract class BasePolicy
{
    /**
     * Role groups for common authorization patterns.
     */
    protected const ADMIN_ROLES = [UserRole::SUPERADMIN, UserRole::ADMIN];
    protected const MANAGER_ROLES = [UserRole::SUPERADMIN, UserRole::ADMIN, UserRole::MANAGER];
    protected const ALL_ROLES = [UserRole::SUPERADMIN, UserRole::ADMIN, UserRole::MANAGER, UserRole::TENANT];

    /**
     * Check if user has admin-level permissions.
     * 
     * @param User $user The authenticated user
     * @return bool True if user is admin or superadmin
     */
    protected function isAdmin(User $user): bool
    {
        return in_array($user->role, self::ADMIN_ROLES, true);
    }

    /**
     * Check if user has manager-level permissions or higher.
     * 
     * @param User $user The authenticated user
     * @return bool True if user is manager, admin, or superadmin
     */
    protected function isManagerOrHigher(User $user): bool
    {
        return in_array($user->role, self::MANAGER_ROLES, true);
    }

    /**
     * Check if user has any of the specified roles.
     * 
     * @param User $user The user to check
     * @param array $roles Array of UserRole enums
     * @return bool True if user has any of the roles
     */
    protected function hasAnyRole(User $user, array $roles): bool
    {
        return in_array($user->role, $roles, true);
    }

    /**
     * Check if user is superadmin.
     * 
     * @param User $user The authenticated user
     * @return bool True if user is superadmin
     */
    protected function isSuperadmin(User $user): bool
    {
        return $user->role === UserRole::SUPERADMIN;
    }

    /**
     * Check if two users belong to the same tenant.
     * 
     * @param User $user The first user
     * @param User $targetUser The second user
     * @return bool True if both users have the same tenant_id
     */
    protected function isSameTenant(User $user, User $targetUser): bool
    {
        return $user->tenant_id !== null 
            && $targetUser->tenant_id !== null
            && $user->tenant_id === $targetUser->tenant_id;
    }

    /**
     * Check if a model belongs to the user's tenant.
     * 
     * @param User $user The authenticated user
     * @param mixed $model The model to check (must have tenant_id property)
     * @return bool True if model belongs to user's tenant
     */
    protected function belongsToUserTenant(User $user, mixed $model): bool
    {
        return $user->tenant_id !== null 
            && isset($model->tenant_id)
            && $user->tenant_id === $model->tenant_id;
    }

    /**
     * Log sensitive operations for audit compliance.
     * 
     * @param string $operation The operation being performed
     * @param User $user The authenticated user performing the operation
     * @param mixed $model The target model (optional)
     * @param array $additionalContext Additional context data
     * @return void
     */
    protected function logSensitiveOperation(
        string $operation, 
        User $user, 
        mixed $model = null, 
        array $additionalContext = []
    ): void {
        $request = request();
        
        $context = [
            'operation' => $operation,
            'actor_id' => $user->id,
            'actor_email' => $user->email,
            'actor_role' => $user->role->value,
            'actor_tenant_id' => $user->tenant_id,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ];

        if ($model !== null) {
            $context['target_id'] = $model->id ?? null;
            $context['target_type'] = get_class($model);
            
            if (isset($model->tenant_id)) {
                $context['target_tenant_id'] = $model->tenant_id;
            }
        }

        $context = array_merge($context, $additionalContext);

        Log::channel('audit')->info("Policy {$operation} operation", $context);
    }

    /**
     * Check if user can perform operations within a specific tenant scope.
     * 
     * @param User $user The authenticated user
     * @param int|null $tenantId The target tenant ID
     * @return bool True if user can operate within the tenant scope
     */
    protected function canOperateInTenant(User $user, ?int $tenantId): bool
    {
        // Superadmin can operate in any tenant
        if ($this->isSuperadmin($user)) {
            return true;
        }

        // Other roles must match tenant scope
        return $user->tenant_id === $tenantId;
    }

    /**
     * Get the policy name for logging purposes.
     * 
     * @return string The policy class name without namespace
     */
    protected function getPolicyName(): string
    {
        return class_basename(static::class);
    }
}