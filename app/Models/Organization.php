<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AuditAction;
use App\Enums\SubscriptionPlan;
use App\Enums\TenantStatus;
use App\ValueObjects\TenantMetrics;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Organization model represents a multi-tenant organization (property management company)
 * This is the tenant in the multi-tenancy architecture
 */
class Organization extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'email',
        'phone',
        'is_active',
        'suspended_at',
        'suspension_reason',
        'plan',
        'subscription_plan',
        'max_properties',
        'max_users',
        'max_storage_gb',
        'max_api_calls_per_month',
        'current_users',
        'current_storage_gb',
        'current_api_calls',
        'trial_ends_at',
        'subscription_ends_at',
        'settings',
        'features',
        'timezone',
        'locale',
        'currency',
        'created_by',
        'resource_quotas',
        'billing_info',
        'primary_contact_email',
        'billing_email',
        'billing_name',
        'billing_address',
        'monthly_price',
        'setup_fee',
        'billing_cycle',
        'next_billing_date',
        'auto_billing',
        'enforce_quotas',
        'quota_notifications',
        'allow_registration',
        'require_email_verification',
        'maintenance_mode',
        'api_access_enabled',
        'created_by_admin_id',
        'last_activity_at',
        'storage_used_mb',
        'api_calls_today',
        'api_calls_quota',
        'average_response_time',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'suspended_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'next_billing_date' => 'date',
        'settings' => 'array',
        'features' => 'array',
        'resource_quotas' => 'array',
        'billing_info' => 'array',
        'plan' => SubscriptionPlan::class,
        'subscription_plan' => SubscriptionPlan::class,
        'storage_used_mb' => 'float',
        'current_storage_gb' => 'decimal:2',
        'monthly_price' => 'decimal:2',
        'setup_fee' => 'decimal:2',
        'api_calls_today' => 'integer',
        'api_calls_quota' => 'integer',
        'current_api_calls' => 'integer',
        'max_storage_gb' => 'integer',
        'max_api_calls_per_month' => 'integer',
        'current_users' => 'integer',
        'average_response_time' => 'float',
        'auto_billing' => 'boolean',
        'enforce_quotas' => 'boolean',
        'quota_notifications' => 'boolean',
        'allow_registration' => 'boolean',
        'require_email_verification' => 'boolean',
        'maintenance_mode' => 'boolean',
        'api_access_enabled' => 'boolean',
    ];

    protected $attributes = [
        'is_active' => true,
        'max_properties' => 100,
        'max_users' => 10,
        'timezone' => 'Europe/Vilnius',
        'locale' => 'lt',
        'currency' => 'EUR',
        'storage_used_mb' => 0,
        'api_calls_today' => 0,
        'api_calls_quota' => 10000,
        'average_response_time' => 0,
    ];

    protected static function booted(): void
    {
        static::creating(function (Organization $org) {
            if (empty($org->slug)) {
                $org->slug = Str::slug($org->name);
                
                // Ensure unique slug
                $originalSlug = $org->slug;
                $count = 1;
                while (static::where('slug', $org->slug)->exists()) {
                    $org->slug = $originalSlug . '-' . $count++;
                }
            }
        });

        static::created(function (Organization $org) {
            // Initialize default settings
            if (empty($org->settings)) {
                $org->settings = [
                    'invoice_prefix' => 'INV',
                    'invoice_number_start' => 1000,
                    'email_from_name' => $org->name,
                    'email_from_address' => $org->email,
                    'enable_notifications' => true,
                    'auto_finalize_invoices' => false,
                    'date_format' => 'Y-m-d',
                    'time_format' => 'H:i',
                ];
                $org->saveQuietly();
            }

            // Initialize default features based on plan
            if (empty($org->features)) {
                $plan = $org->plan instanceof SubscriptionPlan ? $org->plan : SubscriptionPlan::from($org->plan);

                $org->features = [
                    'advanced_reporting' => $plan !== SubscriptionPlan::BASIC,
                    'api_access' => $plan === SubscriptionPlan::ENTERPRISE,
                    'custom_branding' => $plan === SubscriptionPlan::ENTERPRISE,
                    'bulk_operations' => true,
                    'export_data' => true,
                    'audit_logs' => $plan !== SubscriptionPlan::BASIC,
                ];
                $org->saveQuietly();
            }
        });
    }

    // Relationships
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'tenant_id');
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'tenant_id');
    }

    public function buildings(): HasMany
    {
        return $this->hasMany(Building::class, 'tenant_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'tenant_id');
    }

    public function meters(): HasMany
    {
        return $this->hasMany(Meter::class, 'tenant_id');
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class, 'tenant_id');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(OrganizationActivityLog::class, 'organization_id');
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(OrganizationInvitation::class, 'organization_id');
    }

    public function subscriptions(): HasManyThrough
    {
        return $this->hasManyThrough(
            Subscription::class,
            User::class,
            'tenant_id',
            'user_id',
            'id',
            'id',
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Status checks
    public function isActive(): bool
    {
        return $this->is_active && !$this->isSuspended();
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    public function isOnTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function hasActiveSubscription(): bool
    {
        if ($this->isOnTrial()) {
            return true;
        }

        return $this->subscription_ends_at && $this->subscription_ends_at->isFuture();
    }

    public function isSubscriptionExpired(): bool
    {
        return !$this->hasActiveSubscription();
    }

    // Limit checks
    public function canAddProperty(): bool
    {
        return $this->properties()->count() < $this->max_properties;
    }

    public function canAddUser(): bool
    {
        return $this->users()->count() < $this->max_users;
    }

    public function hasFeature(string $feature): bool
    {
        return $this->features[$feature] ?? false;
    }

    public function getRemainingProperties(): int
    {
        return max(0, $this->max_properties - $this->properties()->count());
    }

    public function getRemainingUsers(): int
    {
        return max(0, $this->max_users - $this->users()->count());
    }

    // Settings management
    public function getSetting(string $key, mixed $default = null): mixed
    {
        return $this->settings[$key] ?? $default;
    }

    public function setSetting(string $key, mixed $value): void
    {
        $settings = $this->settings ?? [];
        $settings[$key] = $value;
        $this->settings = $settings;
        $this->save();
    }

    public function updateSettings(array $settings): void
    {
        $this->settings = array_merge($this->settings ?? [], $settings);
        $this->save();
    }

    // Actions
    public function suspend(string $reason): void
    {
        $this->update([
            'is_active' => false,
            'suspended_at' => now(),
            'suspension_reason' => $reason,
        ]);
    }

    public function reactivate(): void
    {
        $this->update([
            'is_active' => true,
            'suspended_at' => null,
            'suspension_reason' => null,
        ]);
    }

    public function recordActivity(): void
    {
        $this->update(['last_activity_at' => now()]);
    }

    public function daysUntilExpiry(): int
    {
        if (!$this->subscription_ends_at) {
            return 0;
        }

        return (int) now()
            ->startOfDay()
            ->diffInDays($this->subscription_ends_at->startOfDay(), false);
    }

    public function upgradePlan(string|SubscriptionPlan $newPlan): void
    {
        $planEnum = $newPlan instanceof SubscriptionPlan ? $newPlan : SubscriptionPlan::from($newPlan);
        $planValue = $planEnum->value;

        $limits = [
            SubscriptionPlan::BASIC->value => ['properties' => 100, 'users' => 10],
            SubscriptionPlan::PROFESSIONAL->value => ['properties' => 500, 'users' => 50],
            SubscriptionPlan::ENTERPRISE->value => ['properties' => 9999, 'users' => 999],
        ];

        $this->update([
            'plan' => $planEnum,
            'max_properties' => $limits[$planValue]['properties'],
            'max_users' => $limits[$planValue]['users'],
        ]);

        // Update features
        $this->features = [
            'advanced_reporting' => $planEnum !== SubscriptionPlan::BASIC,
            'api_access' => $planEnum === SubscriptionPlan::ENTERPRISE,
            'custom_branding' => $planEnum === SubscriptionPlan::ENTERPRISE,
            'bulk_operations' => true,
            'export_data' => true,
            'audit_logs' => $planEnum !== SubscriptionPlan::BASIC,
        ];
        $this->save();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereNull('suspended_at');
    }

    public function scopeOnPlan($query, string|SubscriptionPlan $plan)
    {
        $planValue = $plan instanceof SubscriptionPlan ? $plan->value : $plan;

        return $query->where('plan', $planValue);
    }

    public function scopeWithExpiredSubscription($query)
    {
        return $query->where('subscription_ends_at', '<', now())
            ->orWhereNull('subscription_ends_at');
    }

    public function scopeOnTrial($query)
    {
        return $query->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '>', now());
    }

    // Super Admin Methods
    public function getTenantStatus(): TenantStatus
    {
        if ($this->isSuspended()) {
            return TenantStatus::SUSPENDED;
        }
        
        if (!$this->is_active) {
            return TenantStatus::CANCELLED;
        }
        
        if ($this->isOnTrial() || !$this->hasActiveSubscription()) {
            return TenantStatus::PENDING;
        }
        
        return TenantStatus::ACTIVE;
    }

    public function getMetrics(): TenantMetrics
    {
        return new TenantMetrics(
            totalUsers: $this->users()->count(),
            activeUsers: $this->users()->where('last_login_at', '>', now()->subDays(30))->count(),
            storageUsedMB: $this->storage_used_mb,
            storageQuotaMB: $this->getResourceQuota('storage_mb', 1000),
            apiCallsToday: $this->api_calls_today,
            apiCallsQuota: $this->api_calls_quota,
            monthlyRevenue: $this->calculateMonthlyRevenue(),
            lastActivity: $this->last_activity_at ?? $this->updated_at,
            healthStatus: $this->calculateHealthStatus(),
        );
    }

    public function getResourceQuota(string $resource, int $default = 0): int
    {
        return $this->resource_quotas[$resource] ?? $default;
    }

    public function setResourceQuota(string $resource, int $value): void
    {
        $quotas = $this->resource_quotas ?? [];
        $quotas[$resource] = $value;
        $this->resource_quotas = $quotas;
        $this->save();
    }

    public function isOverQuota(string $resource): bool
    {
        return match ($resource) {
            'storage_mb' => $this->storage_used_mb > $this->getResourceQuota('storage_mb', 1000),
            'api_calls' => $this->api_calls_today > $this->api_calls_quota,
            'users' => $this->users()->count() > $this->max_users,
            'properties' => $this->properties()->count() > $this->max_properties,
            default => false,
        };
    }

    public function calculateMonthlyRevenue(): float
    {
        $planPricing = [
            'basic' => 29.99,
            'professional' => 99.99,
            'enterprise' => 299.99,
        ];

        return $planPricing[$this->plan->value] ?? 0;
    }

    private function calculateHealthStatus(): string
    {
        $issues = 0;
        
        if ($this->isOverQuota('storage_mb')) $issues++;
        if ($this->isOverQuota('api_calls')) $issues++;
        if ($this->average_response_time > 2000) $issues++; // > 2 seconds
        if (!$this->hasActiveSubscription()) $issues++;
        
        return match (true) {
            $issues === 0 => 'healthy',
            $issues <= 2 => 'warning',
            default => 'critical',
        };
    }

    public function suspendByAdmin(string $reason, int $adminId): void
    {
        $this->update([
            'is_active' => false,
            'suspended_at' => now(),
            'suspension_reason' => $reason,
        ]);

        // Log the action
        SuperAdminAuditLog::create([
            'admin_id' => $adminId,
            'action' => AuditAction::TENANT_SUSPENDED,
            'target_type' => static::class,
            'target_id' => $this->id,
            'tenant_id' => $this->id,
            'changes' => [
                'reason' => $reason,
                'suspended_at' => now()->toISOString(),
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function reactivateByAdmin(int $adminId): void
    {
        $this->update([
            'is_active' => true,
            'suspended_at' => null,
            'suspension_reason' => null,
        ]);

        // Log the action
        SuperAdminAuditLog::create([
            'admin_id' => $adminId,
            'action' => AuditAction::TENANT_UPDATED,
            'target_type' => static::class,
            'target_id' => $this->id,
            'tenant_id' => $this->id,
            'changes' => [
                'action' => 'reactivated',
                'reactivated_at' => now()->toISOString(),
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function updateResourceUsage(array $usage): void
    {
        $this->update([
            'storage_used_mb' => $usage['storage_mb'] ?? $this->storage_used_mb,
            'api_calls_today' => $usage['api_calls'] ?? $this->api_calls_today,
            'average_response_time' => $usage['response_time'] ?? $this->average_response_time,
            'last_activity_at' => now(),
        ]);
    }

    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_admin_id');
    }

    /**
     * Users that belong to this organization with roles
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(OrganizationUser::class)
            ->withPivot(['role', 'permissions', 'joined_at', 'left_at', 'is_active', 'invited_by'])
            ->withTimestamps()
            ->wherePivot('is_active', true);
    }

    /**
     * All user memberships including inactive
     */
    public function allMemberships(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(OrganizationUser::class)
            ->withPivot(['role', 'permissions', 'joined_at', 'left_at', 'is_active', 'invited_by'])
            ->withTimestamps();
    }

    /**
     * Get projects for this organization (polymorphic)
     */
    public function projects(): MorphMany
    {
        return $this->morphMany(Project::class, 'projectable');
    }

    /**
     * Get organization-wide projects
     */
    public function organizationProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'tenant_id');
    }
}
