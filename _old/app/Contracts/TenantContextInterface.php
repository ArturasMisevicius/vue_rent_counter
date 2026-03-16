<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\User;

/**
 * Interface for managing tenant context in multi-tenant applications.
 * 
 * Provides session-based tenant context management with support for
 * superadmin tenant switching and security validation.
 */
interface TenantContextInterface
{
    /**
     * Set the current tenant context.
     * 
     * @param int $tenantId The tenant ID to set as current context
     * @throws \InvalidArgumentException If tenant ID is invalid
     */
    public function set(int $tenantId): void;

    /**
     * Get the current tenant context.
     * 
     * @return int|null The current tenant ID or null if no context is set
     */
    public function get(): ?int;

    /**
     * Switch tenant context for authorized users (typically superadmins).
     * 
     * This method includes audit logging and authorization checks.
     * 
     * @param int $tenantId The tenant ID to switch to
     * @param User $user The user performing the switch
     * @throws \App\Exceptions\UnauthorizedTenantSwitchException If user cannot switch to tenant
     * @throws \InvalidArgumentException If tenant ID is invalid
     */
    public function switch(int $tenantId, User $user): void;

    /**
     * Validate that the current user can access the current tenant context.
     * 
     * @param User $user The user to validate
     * @return bool True if user can access current tenant context
     */
    public function validate(User $user): bool;

    /**
     * Clear the current tenant context.
     * 
     * This removes the tenant context from the session.
     */
    public function clear(): void;

    /**
     * Get the user's default tenant ID.
     * 
     * For non-superadmin users, this is their organization's tenant ID.
     * For superadmins, this may be null or a configured default.
     * 
     * @param User $user The user to get default tenant for
     * @return int|null The default tenant ID or null if none available
     */
    public function getDefaultTenant(User $user): ?int;

    /**
     * Initialize tenant context for a user.
     * 
     * Sets appropriate tenant context based on user role and permissions.
     * Falls back to user's default tenant if no context is set.
     * 
     * @param User $user The user to initialize context for
     */
    public function initialize(User $user): void;

    /**
     * Check if the current user can switch to a specific tenant.
     * 
     * @param int $tenantId The tenant ID to check access for
     * @param User $user The user to check permissions for
     * @return bool True if user can switch to the specified tenant
     */
    public function canSwitchTo(int $tenantId, User $user): bool;
}