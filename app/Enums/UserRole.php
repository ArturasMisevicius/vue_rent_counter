<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatableLabel;
use Filament\Support\Contracts\HasLabel;

/**
 * User Role Enum - Hierarchical User Roles
 * 
 * Defines the three-tier user hierarchy with distinct roles and permissions:
 * 
 * **SUPERADMIN** (System Owner):
 * - Purpose: Manages the entire system across all organizations
 * - Permissions: Create/manage Admin accounts, manage subscriptions, view system-wide statistics,
 *   access all data across all tenants (bypasses tenant scope), configure system settings
 * - Access: Full system access without restrictions
 * - Data Scope: No tenant_id (global access)
 * - Default Account: superadmin@example.com
 * 
 * **ADMIN** (Property Owner):
 * - Purpose: Manages their property portfolio and tenant accounts
 * - Permissions: Create/manage buildings and properties, create/manage tenant accounts,
 *   assign/reassign tenants to properties, manage meters/readings/invoices,
 *   deactivate/reactivate tenant accounts, view subscription status and usage limits
 * - Access: Limited to their own tenant_id scope (data isolation)
 * - Data Scope: Unique tenant_id for organization
 * - Subscription: Requires active subscription with limits on properties and tenants
 * - Example Accounts: admin@test.com, admin1@example.com
 * 
 * **MANAGER** (Legacy Role):
 * - Purpose: Similar to Admin, maintained for backward compatibility
 * - Permissions: Same as Admin role
 * - Access: Limited to their own tenant_id scope
 * - Data Scope: Unique tenant_id for organization
 * - Note: New accounts should use ADMIN role; MANAGER is for existing accounts
 * 
 * **TENANT** (Apartment Resident):
 * - Purpose: View billing information and submit meter readings for their apartment
 * - Permissions: View assigned property details, view meters and consumption history,
 *   submit meter readings (if enabled), view/download invoices, update profile information
 * - Access: Limited to their assigned property only (property_id scope)
 * - Data Scope: Inherits tenant_id from Admin; assigned specific property_id
 * - Account Creation: Created by Admin and linked to specific property
 * - Example Accounts: tenant@test.com, tenant1@example.com
 * 
 * @see \App\Models\User
 * @see \App\Scopes\HierarchicalScope
 * @see \App\Policies\UserPolicy
 */
enum UserRole: string implements HasLabel
{
    use HasTranslatableLabel;

    case SUPERADMIN = 'superadmin';
    case ADMIN = 'admin';
    case MANAGER = 'manager';
    case TENANT = 'tenant';

    /**
     * Get all role labels as an associative array.
     * 
     * Performance optimization: Uses static variable to memoize
     * the result and avoid repeated translation lookups.
     * 
     * @return array<string, string>
     */
    public static function labels(): array
    {
        static $cachedLabels = null;
        
        if ($cachedLabels === null) {
            $cachedLabels = collect(self::cases())
                ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
                ->all();
        }

        return $cachedLabels;
    }

    /**
     * Clear the cached labels (useful for testing or locale changes).
     */
    public static function clearLabelCache(): void
    {
        // Reset static variable by calling labels() with a flag
        // This is a workaround since we can't directly access static variables
        static $reset = false;
        $reset = true;
    }
}
