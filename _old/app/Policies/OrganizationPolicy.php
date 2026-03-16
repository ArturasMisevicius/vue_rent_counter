<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * OrganizationPolicy handles authorization for superadmin organization management.
 * 
 * This policy enforces superadmin-only access to all organization operations
 * as part of the superadmin dashboard enhancement. All operations are restricted
 * to users with the SUPERADMIN role and include audit logging for security.
 * 
 * Requirements: 2.1, 2.2, 2.5, 11.1
 */
class OrganizationPolicy
{
    /**
     * Determine whether the user can view any organizations.
     * 
     * Requirements: 2.1
     */
    public function viewAny(User $user): bool
    {
        return $user->isSuperadmin();
    }

    /**
     * Determine whether the user can view the organization.
     * 
     * Requirements: 2.1
     */
    public function view(User $user, Organization $organization): bool
    {
        return $user->isSuperadmin();
    }

    /**
     * Determine whether the user can create organizations.
     * 
     * Requirements: 2.1
     */
    public function create(User $user): bool
    {
        return $user->isSuperadmin();
    }

    /**
     * Determine whether the user can update the organization.
     * 
     * Requirements: 2.2
     */
    public function update(User $user, Organization $organization): bool
    {
        return $user->isSuperadmin();
    }

    /**
     * Determine whether the user can delete the organization.
     * 
     * Requirements: 2.5
     */
    public function delete(User $user, Organization $organization): bool
    {
        return $user->isSuperadmin();
    }

    /**
     * Determine whether the user can restore the organization.
     * 
     * Requirements: 2.5
     */
    public function restore(User $user, Organization $organization): bool
    {
        return $user->isSuperadmin();
    }

    /**
     * Determine whether the user can permanently delete the organization.
     * 
     * Requirements: 2.5
     */
    public function forceDelete(User $user, Organization $organization): bool
    {
        return $user->isSuperadmin();
    }

    /**
     * Determine whether the user can suspend the organization.
     * 
     * Requirements: 2.2
     */
    public function suspend(User $user, Organization $organization): bool
    {
        if ($user->isSuperadmin()) {
            $this->logSensitiveOperation('suspend', $user, $organization);
            return true;
        }
        
        return false;
    }

    /**
     * Determine whether the user can reactivate the organization.
     * 
     * Requirements: 2.2
     */
    public function reactivate(User $user, Organization $organization): bool
    {
        if ($user->isSuperadmin()) {
            $this->logSensitiveOperation('reactivate', $user, $organization);
            return true;
        }
        
        return false;
    }

    /**
     * Determine whether the user can impersonate organization users.
     * 
     * Requirements: 11.1
     */
    public function impersonate(User $user, Organization $organization): bool
    {
        if ($user->isSuperadmin()) {
            $this->logSensitiveOperation('impersonate', $user, $organization);
            return true;
        }
        
        return false;
    }

    /**
     * Log sensitive organization management operations for audit compliance.
     * 
     * @param string $operation The operation being performed
     * @param User $user The authenticated superadmin user
     * @param Organization $organization The target organization
     * @return void
     */
    private function logSensitiveOperation(string $operation, User $user, Organization $organization): void
    {
        $request = request();
        
        Log::channel('audit')->info("Organization {$operation} operation", [
            'operation' => $operation,
            'actor_id' => $user->id,
            'actor_email' => $user->email,
            'actor_role' => $user->role->value,
            'target_organization_id' => $organization->id,
            'target_organization_name' => $organization->name,
            'target_organization_slug' => $organization->slug,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
