<?php

namespace App\Traits;

use App\Models\Organization;
use App\Scopes\HierarchicalScope;
use App\Services\TenantContext;
use Illuminate\Support\Facades\Auth;

/**
 * Applies hierarchical tenant scoping and auto-assigns tenant_id on creation.
 * 
 * The HierarchicalScope provides role-based filtering:
 * - Superadmin: no filtering (sees all data)
 * - Admin/Manager: filters to their tenant_id
 * - Tenant: filters to their tenant_id AND property_id
 * 
 * Requirements: 3.3, 4.3, 8.2, 9.1, 11.1
 */
trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new HierarchicalScope);

        static::creating(function ($model) {
            // Skip if tenant_id is already set
            if (! empty($model->tenant_id)) {
                return;
            }

            if (TenantContext::id() !== null) {
                $model->tenant_id = TenantContext::id();
                return;
            }

            if (Auth::check() && Auth::user()->tenant_id) {
                $model->tenant_id = Auth::user()->tenant_id;
            }
        });
    }

    /**
     * Convenience relation back to the owning organization.
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class, 'tenant_id');
    }
}
