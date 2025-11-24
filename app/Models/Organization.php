<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Organization model represents a multi-tenant organization (property management company)
 * This is the tenant in the multi-tenancy architecture
 */
class Organization extends Model
{
    use HasFactory;

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
        'max_properties',
        'max_users',
        'trial_ends_at',
        'subscription_ends_at',
        'settings',
        'features',
        'timezone',
        'locale',
        'currency',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'suspended_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'settings' => 'array',
        'features' => 'array',
    ];

    protected $attributes = [
        'is_active' => true,
        'plan' => 'basic',
        'max_properties' => 100,
        'max_users' => 10,
        'timezone' => 'Europe/Vilnius',
        'locale' => 'lt',
        'currency' => 'EUR',
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
                $org->features = [
                    'advanced_reporting' => $org->plan !== 'basic',
                    'api_access' => $org->plan === 'enterprise',
                    'custom_branding' => $org->plan === 'enterprise',
                    'bulk_operations' => true,
                    'export_data' => true,
                    'audit_logs' => $org->plan !== 'basic',
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

        return now()
            ->startOfDay()
            ->diffInDays($this->subscription_ends_at->startOfDay(), false);
    }

    public function upgradePlan(string $newPlan): void
    {
        $limits = [
            'basic' => ['properties' => 100, 'users' => 10],
            'professional' => ['properties' => 500, 'users' => 50],
            'enterprise' => ['properties' => 9999, 'users' => 999],
        ];

        $this->update([
            'plan' => $newPlan,
            'max_properties' => $limits[$newPlan]['properties'],
            'max_users' => $limits[$newPlan]['users'],
        ]);

        // Update features
        $this->features = [
            'advanced_reporting' => $newPlan !== 'basic',
            'api_access' => $newPlan === 'enterprise',
            'custom_branding' => $newPlan === 'enterprise',
            'bulk_operations' => true,
            'export_data' => true,
            'audit_logs' => $newPlan !== 'basic',
        ];
        $this->save();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereNull('suspended_at');
    }

    public function scopeOnPlan($query, string $plan)
    {
        return $query->where('plan', $plan);
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
}
