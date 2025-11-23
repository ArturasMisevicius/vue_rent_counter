<?php

namespace App\Traits;

use App\Models\Organization;
use App\Scopes\TenantScope;
use App\Services\TenantContext;
use Illuminate\Support\Facades\Auth;

/**
 * Applies tenant scoping and auto-assigns tenant_id on creation.
 */
trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

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
