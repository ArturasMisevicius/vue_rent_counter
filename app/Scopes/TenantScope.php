<?php

namespace App\Scopes;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (! $this->hasTenantColumn($model)) {
            return;
        }

        $user = Auth::user();

        // Superadmins see everything
        if ($user instanceof User && $user->isSuperadmin()) {
            return;
        }

        $tenantId = app(\App\Services\TenantContext::class)->get() ?? ($user?->tenant_id);

        if ($tenantId === null) {
            return;
        }

        $builder->where($model->qualifyColumn('tenant_id'), '=', $tenantId);

        // Tenants should be limited to their property when applicable
        if ($user instanceof User
            && $user->role === UserRole::TENANT
            && $user->property_id !== null
            && $model->getTable() !== 'users') {
            if ($model->getTable() === 'properties') {
                $builder->where($model->qualifyColumn('id'), '=', $user->property_id);
            } elseif ($this->hasPropertyColumn($model)) {
                $builder->where($model->qualifyColumn('property_id'), '=', $user->property_id);
            }
        }
    }

    /**
     * Register builder macros for bypassing or overriding the scope.
     */
    public function extend(Builder $builder): void
    {
        $builder->macro('withoutTenantScope', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });

        $builder->macro('forTenant', function (Builder $builder, int $tenantId) {
            return $builder->withoutGlobalScope($this)
                ->where($builder->getModel()->qualifyColumn('tenant_id'), $tenantId);
        });
    }

    protected function hasTenantColumn(Model $model): bool
    {
        return in_array('tenant_id', $model->getFillable(), true)
            || Schema::hasColumn($model->getTable(), 'tenant_id');
    }

    protected function hasPropertyColumn(Model $model): bool
    {
        return in_array('property_id', $model->getFillable(), true)
            || Schema::hasColumn($model->getTable(), 'property_id');
    }
}
