<?php

namespace App\Scopes;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class HierarchicalScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        if (!$user instanceof User) {
            return;
        }

        // Apply role-based filtering
        switch ($user->role) {
            case UserRole::SUPERADMIN:
                // Superadmin: no filtering - can see all data
                break;

            case UserRole::ADMIN:
            case UserRole::MANAGER:
                // Admin/Manager: filter by tenant_id
                if ($user->tenant_id !== null) {
                    $builder->where($model->qualifyColumn('tenant_id'), '=', $user->tenant_id);
                }
                break;

            case UserRole::TENANT:
                // Tenant: filter by tenant_id and property_id
                if ($user->tenant_id !== null) {
                    $builder->where($model->qualifyColumn('tenant_id'), '=', $user->tenant_id);
                }
                
                // Additional property_id filtering for tenant role
                if ($user->property_id !== null && $model->getTable() !== 'users') {
                    // Check if the model has a property_id column
                    if (in_array('property_id', $model->getFillable()) || 
                        $model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), 'property_id')) {
                        $builder->where($model->qualifyColumn('property_id'), '=', $user->property_id);
                    }
                }
                break;
        }
    }

    /**
     * Determine if the scope should be applied to the given model.
     */
    public function shouldApplyScope(User $user): bool
    {
        return $user->role !== UserRole::SUPERADMIN;
    }

    /**
     * Get the filtering criteria for the given user.
     */
    public function getFilterCriteria(User $user): array
    {
        $criteria = [];

        switch ($user->role) {
            case UserRole::SUPERADMIN:
                // No filtering criteria
                break;

            case UserRole::ADMIN:
            case UserRole::MANAGER:
                if ($user->tenant_id !== null) {
                    $criteria['tenant_id'] = $user->tenant_id;
                }
                break;

            case UserRole::TENANT:
                if ($user->tenant_id !== null) {
                    $criteria['tenant_id'] = $user->tenant_id;
                }
                if ($user->property_id !== null) {
                    $criteria['property_id'] = $user->property_id;
                }
                break;
        }

        return $criteria;
    }
}