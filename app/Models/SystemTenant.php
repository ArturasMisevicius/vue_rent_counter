<?php

namespace App\Models;

use App\Models\Concerns\HasGeneratedSlug;
use Database\Factories\SystemTenantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SystemTenant extends Model
{
    /** @use HasFactory<SystemTenantFactory> */
    use HasFactory;

    use HasGeneratedSlug;

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'status',
        'subscription_plan',
        'settings',
        'resource_quotas',
        'billing_info',
        'primary_contact_email',
        'created_by_admin_id',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'resource_quotas' => 'array',
            'billing_info' => 'array',
        ];
    }

    protected function slugSourceColumn(): string
    {
        return 'name';
    }

    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_admin_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function organizations(): HasMany
    {
        return $this->hasMany(Organization::class);
    }

    public function superAdminAuditLogs(): HasMany
    {
        return $this->hasMany(SuperAdminAuditLog::class);
    }
}
