<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SubscriptionRenewal Model
 * 
 * Tracks the history of subscription renewals including dates, durations, and triggering method.
 * 
 * @property int $id
 * @property int $subscription_id
 * @property int|null $user_id User who performed the renewal (null for automatic)
 * @property string $method manual or automatic
 * @property string $period monthly, quarterly, annually
 * @property \Illuminate\Support\Carbon $old_expires_at Previous expiry date
 * @property \Illuminate\Support\Carbon $new_expires_at New expiry date
 * @property int $duration_days Number of days extended
 * @property string|null $notes Optional notes about the renewal
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Subscription $subscription
 * @property-read User|null $user User who performed the renewal
 */
class SubscriptionRenewal extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'subscription_id',
        'user_id',
        'method',
        'period',
        'old_expires_at',
        'new_expires_at',
        'duration_days',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_expires_at' => 'datetime',
            'new_expires_at' => 'datetime',
            'duration_days' => 'integer',
        ];
    }

    /**
     * Get the subscription that was renewed.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Get the user who performed the renewal.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if this was an automatic renewal.
     */
    public function isAutomatic(): bool
    {
        return $this->method === 'automatic';
    }

    /**
     * Check if this was a manual renewal.
     */
    public function isManual(): bool
    {
        return $this->method === 'manual';
    }

    /**
     * Get the organization name for this renewal.
     */
    public function getOrganizationNameAttribute(): string
    {
        return $this->subscription->user->organization_name ?? $this->subscription->user->name;
    }
}
