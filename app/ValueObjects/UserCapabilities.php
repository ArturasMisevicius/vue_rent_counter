<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Enums\UserRole;
use App\Models\User;

/**
 * User Capabilities Value Object
 * 
 * Encapsulates what a user can do based on their role and status.
 * Provides a clean interface for capability checking without
 * complex conditional logic scattered throughout the codebase.
 */
readonly class UserCapabilities
{
    public function __construct(
        private User $user,
        private bool $canManageProperties = false,
        private bool $canManageTenants = false,
        private bool $canManageBuildings = false,
        private bool $canManageInvoices = false,
        private bool $canSubmitReadings = false,
        private bool $canViewReports = false,
        private bool $canAccessAdmin = false,
        private bool $canAccessSuperadmin = false,
        private bool $canManageSystem = false,
        private bool $canImpersonateUsers = false,
    ) {}

    /**
     * Create capabilities based on user role.
     */
    public static function fromUser(User $user): self
    {
        return match ($user->role) {
            UserRole::SUPERADMIN => new self(
                user: $user,
                canManageProperties: true,
                canManageTenants: true,
                canManageBuildings: true,
                canManageInvoices: true,
                canSubmitReadings: true,
                canViewReports: true,
                canAccessAdmin: true,
                canAccessSuperadmin: true,
                canManageSystem: true,
                canImpersonateUsers: true,
            ),
            UserRole::ADMIN => new self(
                user: $user,
                canManageProperties: true,
                canManageTenants: true,
                canManageBuildings: true,
                canManageInvoices: true,
                canSubmitReadings: true,
                canViewReports: true,
                canAccessAdmin: true,
                canAccessSuperadmin: false,
                canManageSystem: false,
                canImpersonateUsers: false,
            ),
            UserRole::MANAGER => new self(
                user: $user,
                canManageProperties: true,
                canManageTenants: true,
                canManageBuildings: true,
                canManageInvoices: true,
                canSubmitReadings: true,
                canViewReports: true,
                canAccessAdmin: true,
                canAccessSuperadmin: false,
                canManageSystem: false,
                canImpersonateUsers: false,
            ),
            UserRole::TENANT => new self(
                user: $user,
                canManageProperties: false,
                canManageTenants: false,
                canManageBuildings: false,
                canManageInvoices: false,
                canSubmitReadings: true,
                canViewReports: false,
                canAccessAdmin: false,
                canAccessSuperadmin: false,
                canManageSystem: false,
                canImpersonateUsers: false,
            ),
        };
    }

    /**
     * Check if user can manage properties.
     */
    public function canManageProperties(): bool
    {
        return $this->canManageProperties && $this->user->is_active;
    }

    /**
     * Check if user can manage tenants.
     */
    public function canManageTenants(): bool
    {
        return $this->canManageTenants && $this->user->is_active;
    }

    /**
     * Check if user can manage buildings.
     */
    public function canManageBuildings(): bool
    {
        return $this->canManageBuildings && $this->user->is_active;
    }

    /**
     * Check if user can manage invoices.
     */
    public function canManageInvoices(): bool
    {
        return $this->canManageInvoices && $this->user->is_active;
    }

    /**
     * Check if user can submit meter readings.
     */
    public function canSubmitReadings(): bool
    {
        return $this->canSubmitReadings && $this->user->is_active;
    }

    /**
     * Check if user can view reports.
     */
    public function canViewReports(): bool
    {
        return $this->canViewReports && $this->user->is_active;
    }

    /**
     * Check if user can access admin panel.
     */
    public function canAccessAdmin(): bool
    {
        return $this->canAccessAdmin && $this->user->is_active;
    }

    /**
     * Check if user can access superadmin features.
     */
    public function canAccessSuperadmin(): bool
    {
        return $this->canAccessSuperadmin && $this->user->is_active;
    }

    /**
     * Check if user can manage system settings.
     */
    public function canManageSystem(): bool
    {
        return $this->canManageSystem && $this->user->is_active;
    }

    /**
     * Check if user can impersonate other users.
     */
    public function canImpersonateUsers(): bool
    {
        return $this->canImpersonateUsers && $this->user->is_active;
    }

    /**
     * Get all capabilities as an array.
     */
    public function toArray(): array
    {
        return [
            'can_manage_properties' => $this->canManageProperties(),
            'can_manage_tenants' => $this->canManageTenants(),
            'can_manage_buildings' => $this->canManageBuildings(),
            'can_manage_invoices' => $this->canManageInvoices(),
            'can_submit_readings' => $this->canSubmitReadings(),
            'can_view_reports' => $this->canViewReports(),
            'can_access_admin' => $this->canAccessAdmin(),
            'can_access_superadmin' => $this->canAccessSuperadmin(),
            'can_manage_system' => $this->canManageSystem(),
            'can_impersonate_users' => $this->canImpersonateUsers(),
        ];
    }
}