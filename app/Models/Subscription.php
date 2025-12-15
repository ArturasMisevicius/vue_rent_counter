<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Subscription Model - Subscription-Based Access Control
 *
 * Manages subscription plans for Admin users with configurable limits on properties and tenants.
 *
 * **Subscription Plans**:
 * - Basic: 10 properties, 50 tenants - Core billing features
 * - Professional: 50 properties, 200 tenants - Advanced reporting, bulk operations
 * - Enterprise: Unlimited properties/tenants - Custom features, priority support
 *
 * **Subscription Features**:
 * - Grace Period: 7 days after expiry with read-only access (configurable)
 * - Expiry Warning: 14 days before expiry shows renewal reminders (configurable)
 * - Read-Only Mode: Expired subscriptions allow viewing but not editing
 * - Automatic Limits: System enforces property and tenant limits based on plan
 * - Renewal: Admins can renew subscriptions through their profile
 *
 * **Subscription Status**:
 * - Active: Full access to all features within plan limits
 * - Expired: Read-only access, cannot create new resources
 * - Suspended: Temporary suspension by Superadmin
 * - Cancelled: Subscription terminated, account deactivated
 *
 * @property int $id
 * @property int $user_id Admin user who owns this subscription
 * @property string $plan_type Subscription plan (basic, professional, enterprise)
 * @property string $status Subscription status (active, expired, suspended, cancelled)
 * @property \Illuminate\Support\Carbon $starts_at Subscription start date
 * @property \Illuminate\Support\Carbon $expires_at Subscription expiry date
 * @property int $max_properties Maximum properties allowed for this plan
 * @property int $max_tenants Maximum tenants allowed for this plan
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User $user Admin user who owns this subscription
 *
 * @see \App\Enums\SubscriptionStatus
 * @see \App\Enums\SubscriptionPlanType
 * @see \App\Services\SubscriptionService
 * @see \App\Models\User
 */
class Subscription extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'plan_type',
        'status',
        'starts_at',
        'expires_at',
        'max_properties',
        'max_tenants',
        'auto_renew',
        'renewal_period',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'max_properties' => 'integer',
            'max_tenants' => 'integer',
            'auto_renew' => 'boolean',
        ];
    }

    /**
     * Get the user that owns the subscription.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the renewal history for this subscription.
     */
    public function renewals(): HasMany
    {
        return $this->hasMany(SubscriptionRenewal::class);
    }

    /**
     * Check if the subscription is currently active.
     *
     * Performance: Direct enum comparison (no string conversion needed).
     */
    public function isActive(): bool
    {
        return $this->status === SubscriptionStatus::ACTIVE
            && $this->expires_at->isFuture();
    }

    /**
     * Check if the subscription has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Get the number of days until the subscription expires.
     * Returns negative number if already expired.
     */
    public function daysUntilExpiry(): int
    {
        return now()
            ->startOfDay()
            ->diffInDays($this->expires_at->startOfDay(), false);
    }

    /**
     * Check if the user can add another property within subscription limits.
     */
    public function canAddProperty(): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        $currentPropertyCount = $this->user->properties()->count();

        return $currentPropertyCount < $this->max_properties;
    }

    /**
     * Check if the user can add another tenant within subscription limits.
     */
    public function canAddTenant(): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        $currentTenantCount = $this->user->childUsers()->where('role', 'tenant')->count();

        return $currentTenantCount < $this->max_tenants;
    }

    /**
     * Check if the subscription is suspended.
     *
     * Performance: Direct enum comparison (no string conversion needed).
     */
    public function isSuspended(): bool
    {
        return $this->status === SubscriptionStatus::SUSPENDED;
    }

    /**
     * Renew the subscription with a new expiry date.
     *
     * Performance: Enum is automatically converted to string by Laravel's casting.
     */
    public function renew(Carbon $newExpiryDate): void
    {
        $this->update([
            'expires_at' => $newExpiryDate,
            'status' => SubscriptionStatus::ACTIVE,
        ]);
    }

    /**
     * Suspend the subscription.
     *
     * Performance: Enum is automatically converted to string by Laravel's casting.
     */
    public function suspend(): void
    {
        $this->update([
            'status' => SubscriptionStatus::SUSPENDED,
        ]);
    }

    /**
     * Activate the subscription.
     *
     * Performance: Enum is automatically converted to string by Laravel's casting.
     */
    public function activate(): void
    {
        $this->update([
            'status' => SubscriptionStatus::ACTIVE,
        ]);
    }

}
