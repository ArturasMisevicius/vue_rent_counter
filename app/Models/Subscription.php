<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'max_properties' => 'integer',
            'max_tenants' => 'integer',
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
     * Check if the subscription is currently active.
     */
    public function isActive(): bool
    {
        return $this->status === SubscriptionStatus::ACTIVE->value 
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
        if (!$this->isActive()) {
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
        if (!$this->isActive()) {
            return false;
        }

        $currentTenantCount = $this->user->childUsers()->where('role', 'tenant')->count();
        
        return $currentTenantCount < $this->max_tenants;
    }

    /**
     * Check if the subscription is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->status === SubscriptionStatus::SUSPENDED->value;
    }

    /**
     * Renew the subscription with a new expiry date.
     */
    public function renew(Carbon $newExpiryDate): void
    {
        $this->update([
            'expires_at' => $newExpiryDate,
            'status' => SubscriptionStatus::ACTIVE->value,
        ]);
    }

    /**
     * Suspend the subscription.
     */
    public function suspend(): void
    {
        $this->update([
            'status' => SubscriptionStatus::SUSPENDED->value,
        ]);
    }

    /**
     * Activate the subscription.
     */
    public function activate(): void
    {
        $this->update([
            'status' => SubscriptionStatus::ACTIVE->value,
        ]);
    }
}
