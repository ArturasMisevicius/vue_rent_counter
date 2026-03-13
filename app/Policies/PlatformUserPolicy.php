<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * PlatformUserPolicy handles authorization for superadmin cross-organization user management.
 * 
 * This policy enforces superadmin-only access to platform-wide user management operations
 * as part of the superadmin dashboard enhancement. It provides authorization for managing
 * users across all organizations from a single interface.
 * 
 * Requirements: 5.1, 5.3, 5.4, 5.5
 */
class PlatformUserPolicy
{
    /**
     * Determine whether the user can view any platform users.
     * 
     * Requirements: 5.1
     */
    public function viewAny(User $user): bool
    {
        return $user->isSuperadmin();
    }

    /**
     * Determine whether the user can view the platform user.
     * 
     * Requirements: 5.1
     */
    public function view(User $user, User $platformUser): bool
    {
        return $user->isSuperadmin();
    }

    /**
     * Determine whether the user can update the platform user.
     * 
     * Requirements: 5.3
     */
    public function update(User $user, User $platformUser): bool
    {
        return $user->isSuperadmin();
    }

    /**
     * Determine whether the user can reset password for the platform user.
     * 
     * Requirements: 5.4
     */
    public function resetPassword(User $user, User $platformUser): bool
    {
        if ($user->isSuperadmin()) {
            $this->logSensitiveOperation('resetPassword', $user, $platformUser);
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can deactivate the platform user.
     * 
     * Requirements: 5.5
     */
    public function deactivate(User $user, User $platformUser): bool
    {
        // Cannot deactivate yourself
        if ($user->id === $platformUser->id) {
            return false;
        }

        if ($user->isSuperadmin()) {
            $this->logSensitiveOperation('deactivate', $user, $platformUser);
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can reactivate the platform user.
     * 
     * Requirements: 5.5
     */
    public function reactivate(User $user, User $platformUser): bool
    {
        if ($user->isSuperadmin()) {
            $this->logSensitiveOperation('reactivate', $user, $platformUser);
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can impersonate the platform user.
     * 
     * Requirements: 5.3
     */
    public function impersonate(User $user, User $platformUser): bool
    {
        // Cannot impersonate yourself
        if ($user->id === $platformUser->id) {
            return false;
        }

        if ($user->isSuperadmin()) {
            $this->logSensitiveOperation('impersonate', $user, $platformUser);
            return true;
        }

        return false;
    }

    /**
     * Log sensitive platform user management operations for audit compliance.
     * 
     * @param string $operation The operation being performed
     * @param User $user The authenticated superadmin user
     * @param User $platformUser The target platform user
     * @return void
     */
    private function logSensitiveOperation(string $operation, User $user, User $platformUser): void
    {
        $request = request();
        
        Log::channel('audit')->info("Platform user {$operation} operation", [
            'operation' => $operation,
            'actor_id' => $user->id,
            'actor_email' => $user->email,
            'actor_role' => $user->role->value,
            'target_user_id' => $platformUser->id,
            'target_user_email' => $platformUser->email,
            'target_user_role' => $platformUser->role->value,
            'target_user_tenant_id' => $platformUser->tenant_id,
            'target_user_organization' => $platformUser->organization_name,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}