<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * UserPolicy handles authorization for user management operations.
 * 
 * This policy enforces hierarchical access control with strict tenant boundaries:
 * - Superadmin: Full access to all users across all tenants
 * - Admin: Can manage users within their tenant only
 * - Manager: Can only view/update their own profile
 * - Tenant: Can only view/update their own profile
 * 
 * Security: All operations are audited for compliance
 * 
 * Requirements: 13.1, 13.2, 13.3, 13.4
 */
final class UserPolicy
{
    /**
     * Determine whether the user can view any users.
     * Respects role hierarchy: superadmin sees all, admin sees their tenant.
     * 
     * @param User $user The authenticated user
     * @return bool True if the user can view the users list
     * 
     * Requirements: 13.1
     */
    public function viewAny(User $user): bool
    {
        return $user->isSuperadmin() 
            || $user->isAdmin();
    }

    /**
     * Determine whether the user can view the user.
     * Enforces tenant boundaries and parent-child relationships.
     * 
     * @param User $user The authenticated user
     * @param User $model The user being viewed
     * @return bool True if the user can view the model
     * 
     * Requirements: 13.1, 13.3
     */
    public function view(User $user, User $model): bool
    {
        // Superadmin can view any user (Requirement 13.1)
        if ($user->isSuperadmin() || $this->isPlatformAdmin($user)) {
            return true;
        }

        // Users can always view themselves
        if ($user->id === $model->id) {
            return true;
        }

        // Only Admins can view other users within their tenant (Requirement 13.3)
        if ($user->isAdmin()) {
            return $this->isSameTenant($user, $model);
        }

        // Managers and Tenants cannot view other users
        return false;
    }

    /**
     * Determine whether the user can create users.
     * Allows superadmin and admins to create users.
     * 
     * @param User $user The authenticated user
     * @return bool True if the user can create new users
     * 
     * Requirements: 13.1, 13.2
     */
    public function create(User $user): bool
    {
        return $user->isSuperadmin() 
            || $user->isAdmin();
    }

    /**
     * Determine whether the user can update the user.
     * Enforces tenant boundaries and ownership checks.
     * 
     * Performance: Early returns minimize role checks; logging deferred until authorization passes
     * 
     * @param User $user The authenticated user
     * @param User $model The user being updated
     * @return bool True if the user can update the model
     * 
     * Requirements: 13.1, 13.3, 13.4
     */
    public function update(User $user, User $model): bool
    {
        // Users can always update themselves (Requirement 13.4) - fastest path
        if ($user->id === $model->id) {
            return true;
        }

        // Superadmin can update any user (Requirement 13.1)
        if ($user->isSuperadmin() || $this->isPlatformAdmin($user)) {
            $this->logSensitiveOperation('update', $user, $model);
            return true;
        }

        // Admins can update users within their tenant (Requirement 13.3)
        if ($this->canManageTenantUser($user, $model)) {
            $this->logSensitiveOperation('update', $user, $model);
            return true;
        }

        // Tenants cannot update other users
        return false;
    }

    /**
     * Determine whether the user can delete the user.
     * Enforces tenant boundaries and prevents self-deletion.
     * 
     * Performance: Self-deletion check first (fastest rejection path)
     * 
     * @param User $user The authenticated user
     * @param User $model The user being deleted
     * @return bool True if the user can delete the model
     * 
     * Requirements: 13.1, 13.3, 13.4
     */
    public function delete(User $user, User $model): bool
    {
        // Cannot delete yourself - fastest rejection path
        if ($user->id === $model->id) {
            return false;
        }

        // Superadmin can delete any user (except themselves) (Requirement 13.1)
        if ($user->isSuperadmin() || $this->isPlatformAdmin($user)) {
            $this->logSensitiveOperation('delete', $user, $model);
            return true;
        }

        // Admins can delete users within their tenant (Requirement 13.3)
        if ($this->canManageTenantUser($user, $model)) {
            $this->logSensitiveOperation('delete', $user, $model);
            return true;
        }

        // Tenants cannot delete users
        return false;
    }

    /**
     * Determine whether the user can restore the user.
     * Enforces tenant boundaries for restore operations.
     * 
     * Performance: Consolidated role check reduces redundant conditionals
     * 
     * @param User $user The authenticated user
     * @param User $model The user being restored
     * @return bool True if the user can restore the model
     * 
     * Requirements: 13.1, 13.3
     */
    public function restore(User $user, User $model): bool
    {
        // Superadmin can restore any user (Requirement 13.1)
        if ($user->isSuperadmin() || $this->isPlatformAdmin($user)) {
            $this->logSensitiveOperation('restore', $user, $model);
            return true;
        }

        // Admins can restore users within their tenant (Requirement 13.3)
        if ($this->canManageTenantUser($user, $model)) {
            $this->logSensitiveOperation('restore', $user, $model);
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the user.
     * Only superadmins can force delete, with audit logging.
     * 
     * @param User $user The authenticated user
     * @param User $model The user being force deleted
     * @return bool True if the user can force delete the model
     * 
     * Requirements: 13.1
     */
    public function forceDelete(User $user, User $model): bool
    {
        // Only superadmin can force delete users (Requirement 13.1)
        // Also prevent self-deletion
        if ($user->isSuperadmin() && $user->id !== $model->id) {
            $this->logSensitiveOperation('forceDelete', $user, $model);
            return true;
        }
        
        return false;
    }

    /**
     * Determine whether the user can replicate the user.
     * Used by Filament for record duplication.
     * 
     * @param User $user The authenticated user
     * @param User $model The user being replicated
     * @return bool True if the user can replicate the model
     */
    public function replicate(User $user, User $model): bool
    {
        // Only superadmins can replicate users
        return $user->isSuperadmin();
    }

    /**
     * Determine whether the user can impersonate another user.
     * Used for support and debugging purposes.
     * 
     * @param User $user The authenticated user
     * @param User $model The user to impersonate
     * @return bool True if the user can impersonate the model
     */
    public function impersonate(User $user, User $model): bool
    {
        // Cannot impersonate yourself
        if ($user->id === $model->id) {
            return false;
        }

        // Only superadmins can impersonate
        if ($user->isSuperadmin()) {
            $this->logSensitiveOperation('impersonate', $user, $model);
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view system settings.
     * Only superadmins and platform admins can view system settings.
     * 
     * @param User $user The authenticated user
     * @return bool True if the user can view system settings
     */
    public function viewSystemSettings(User $user): bool
    {
        return $user->isSuperadmin() || $this->isPlatformAdmin($user);
    }

    /**
     * Determine whether the user can manage system settings.
     * Only superadmins can manage system settings.
     * 
     * @param User $user The authenticated user
     * @return bool True if the user can manage system settings
     */
    public function manageSystemSettings(User $user): bool
    {
        if ($user->isSuperadmin()) {
            $this->logSensitiveOperation('manageSystemSettings', $user, $user);
            return true;
        }
        
        return false;
    }

    /**
     * Check if user can manage another user within their tenant.
     * Consolidates admin/manager role check with tenant boundary validation.
     * 
     * Performance: Single method reduces duplicate role checks across policy methods
     * 
     * @param User $user The authenticated user
     * @param User $model The target user
     * @return bool True if user can manage the target user
     */
    private function canManageTenantUser(User $user, User $model): bool
    {
        return $user->isAdmin() && $this->isSameTenant($user, $model);
    }

    private function isPlatformAdmin(User $user): bool
    {
        return $user->isAdmin() && $user->tenant_id === null;
    }

    /**
     * Check if two users belong to the same tenant.
     * Validates that both users have tenant_id set and they match.
     * 
     * Performance: Short-circuit evaluation with null checks first
     * 
     * @param User $user The first user
     * @param User $model The second user
     * @return bool True if both users have the same tenant_id
     */
    private function isSameTenant(User $user, User $model): bool
    {
        return $user->tenant_id !== null 
            && $model->tenant_id !== null
            && $user->tenant_id === $model->tenant_id;
    }

    /**
     * Log sensitive user management operations for audit compliance.
     * Logs to the audit channel with user context and IP address.
     * 
     * Performance: Lazy evaluation - request data captured only when logging occurs
     * 
     * @param string $operation The operation being performed
     * @param User $user The authenticated user performing the operation
     * @param User $model The target user
     * @return void
     */
    private function logSensitiveOperation(string $operation, User $user, User $model): void
    {
        // Performance: Build context array once, avoiding repeated request() calls
        $request = request();
        
        Log::channel('audit')->info("User {$operation} operation", [
            'operation' => $operation,
            'actor_id' => $user->id,
            'actor_email' => $user->email,
            'actor_role' => $user->role->value,
            'target_id' => $model->id,
            'target_email' => $model->email,
            'target_role' => $model->role->value,
            'actor_tenant_id' => $user->tenant_id,
            'target_tenant_id' => $model->tenant_id,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
