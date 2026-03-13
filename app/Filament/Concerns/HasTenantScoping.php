<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Provides tenant scoping functionality for Filament resources.
 *
 * This trait centralizes tenant-aware query scoping logic that is
 * commonly used across multiple Filament resources to ensure data
 * isolation between tenants.
 */
trait HasTenantScoping
{
    /**
     * Scope query to authenticated user's tenant.
     *
     * Applies tenant_id filtering for users with a tenant assignment.
     * Superadmin users bypass this scope to access all records.
     *
     * @param Builder $query The query builder instance
     * @return Builder The scoped query builder
     */
    protected static function scopeToUserTenant(Builder $query): Builder
    {
        $user = auth()->user();

        if ($user instanceof User && $user->tenant_id) {
            $table = $query->getModel()->getTable();
            $query->where("{$table}.tenant_id", $user->tenant_id);
        }

        return $query;
    }

    /**
     * Get the navigation badge count for the resource.
     *
     * Returns the count of records accessible to the current user,
     * respecting tenant scope for non-superadmin users.
     *
     * @return string|null The badge count or null if no records
     */
    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return null;
        }

        $query = static::getModel()::query();

        // Apply tenant scope for non-superadmin users
        if ($user->role !== UserRole::SUPERADMIN && $user->tenant_id) {
            $query->where('tenant_id', $user->tenant_id);
        }

        $count = $query->count();

        return $count > 0 ? (string) $count : null;
    }

    /**
     * Get the navigation badge color.
     *
     * @return string The badge color
     */
    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }
}
