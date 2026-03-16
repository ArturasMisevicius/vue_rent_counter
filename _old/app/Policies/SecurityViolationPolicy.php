<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\SecurityViolation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Security Violation Policy
 * 
 * Defines authorization rules for security violation access and management.
 * Implements least privilege principle with role-based access control.
 */
final class SecurityViolationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view any security violations.
     */
    public function viewAny(User $user): bool
    {
        // Only superadmin, admin, and security analysts can view violations
        return $user->hasAnyRole([
            UserRole::SUPERADMIN,
            UserRole::ADMIN,
        ]) || $user->hasPermission('view-security-analytics');
    }

    /**
     * Determine if the user can view the security violation.
     */
    public function view(User $user, SecurityViolation $violation): bool
    {
        // Superadmin can view all violations
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Users can only view violations from their tenant
        if ($user->tenant_id !== $violation->tenant_id) {
            return false;
        }

        // Admin and security analysts can view tenant violations
        return $user->hasAnyRole([UserRole::ADMIN]) || 
               $user->hasPermission('view-security-analytics');
    }

    /**
     * Determine if the user can create security violations.
     */
    public function create(User $user): bool
    {
        // Only system can create violations (via CSP reports)
        // Users cannot manually create violations
        return false;
    }

    /**
     * Determine if the user can update the security violation.
     */
    public function update(User $user, SecurityViolation $violation): bool
    {
        // Superadmin can update all violations
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Users can only update violations from their tenant
        if ($user->tenant_id !== $violation->tenant_id) {
            return false;
        }

        // Only admin can update violations (mark as resolved, add notes)
        return $user->hasRole(UserRole::ADMIN) || 
               $user->hasPermission('manage-security-violations');
    }

    /**
     * Determine if the user can delete the security violation.
     */
    public function delete(User $user, SecurityViolation $violation): bool
    {
        // Only superadmin can delete violations
        // Violations should be retained for audit purposes
        return $user->isSuperAdmin();
    }

    /**
     * Determine if the user can restore the security violation.
     */
    public function restore(User $user, SecurityViolation $violation): bool
    {
        // Only superadmin can restore violations
        return $user->isSuperAdmin();
    }

    /**
     * Determine if the user can permanently delete the security violation.
     */
    public function forceDelete(User $user, SecurityViolation $violation): bool
    {
        // Violations should never be permanently deleted for audit compliance
        return false;
    }

    /**
     * Determine if the user can resolve security violations.
     */
    public function resolve(User $user, SecurityViolation $violation): bool
    {
        // Superadmin can resolve all violations
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Users can only resolve violations from their tenant
        if ($user->tenant_id !== $violation->tenant_id) {
            return false;
        }

        // Admin and security analysts can resolve violations
        return $user->hasRole(UserRole::ADMIN) || 
               $user->hasPermission('resolve-security-violations');
    }

    /**
     * Determine if the user can export security violations.
     */
    public function export(User $user): bool
    {
        // Superadmin and admin can export violations
        return $user->hasAnyRole([
            UserRole::SUPERADMIN,
            UserRole::ADMIN,
        ]) || $user->hasPermission('export-security-data');
    }

    /**
     * Determine if the user can view security analytics.
     */
    public function viewAnalytics(User $user): bool
    {
        // Superadmin, admin, and security analysts can view analytics
        return $user->hasAnyRole([
            UserRole::SUPERADMIN,
            UserRole::ADMIN,
        ]) || $user->hasPermission('view-security-analytics');
    }

    /**
     * Determine if the user can generate security reports.
     */
    public function generateReports(User $user): bool
    {
        // Superadmin and admin can generate reports
        return $user->hasAnyRole([
            UserRole::SUPERADMIN,
            UserRole::ADMIN,
        ]) || $user->hasPermission('generate-security-reports');
    }
}
