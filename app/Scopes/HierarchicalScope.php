<?php

namespace App\Scopes;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class HierarchicalScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     * 
     * Filters data based on user role:
     * - Superadmin: no filtering (sees all data)
     * - Admin: filters by tenant_id
     * - Tenant: filters by tenant_id and property_id
     * 
     * Falls back to session-based tenant_id if no authenticated user.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        // If authenticated user exists, use role-based filtering
        if ($user) {
            // Superadmin sees everything - no filtering
            if ($user->role === UserRole::SUPERADMIN) {
                return;
            }

            // Admin and Manager roles filter by tenant_id
            if ($user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER) {
                if ($user->tenant_id !== null) {
                    $builder->where($model->qualifyColumn('tenant_id'), '=', $user->tenant_id);
                }
                return;
            }

            // Tenant role filters by both tenant_id and property_id
            if ($user->role === UserRole::TENANT) {
                if ($user->tenant_id !== null) {
                    $builder->where($model->qualifyColumn('tenant_id'), '=', $user->tenant_id);
                }
                
                // Additionally filter by property_id if the model has this column
                if ($user->property_id !== null) {
                    if ($this->modelHasPropertyId($model)) {
                        // Model has property_id column (e.g., Meter, Tenant)
                        $builder->where($model->qualifyColumn('property_id'), '=', $user->property_id);
                    } elseif ($model->getTable() === 'properties') {
                        // Special case: Property model - filter by id
                        $builder->where($model->qualifyColumn('id'), '=', $user->property_id);
                    }
                }
                return;
            }
        }

        // Fall back to session-based tenant_id (for backward compatibility and testing)
        $tenantId = $this->getTenantId();
        if ($tenantId !== null) {
            $builder->where($model->qualifyColumn('tenant_id'), '=', $tenantId);
        }
    }

    /**
     * Get the current tenant ID from session.
     */
    protected function getTenantId(): ?int
    {
        return session('tenant_id');
    }

    /**
     * Check if the model has a property_id column.
     */
    protected function modelHasPropertyId(Model $model): bool
    {
        // Check if property_id is in the fillable array
        // This is more reliable than checking attributes which may not be set yet
        return in_array('property_id', $model->getFillable());
    }
}
