<?php

namespace App\Models;

use Database\Factories\SubscriptionRenewalFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionRenewal extends Model
{
    /** @use HasFactory<SubscriptionRenewalFactory> */
    use HasFactory;

    private const SUMMARY_COLUMNS = [
        'id',
        'subscription_id',
        'user_id',
        'method',
        'period',
        'old_expires_at',
        'new_expires_at',
        'duration_days',
        'notes',
        'created_at',
        'updated_at',
    ];

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

    protected function casts(): array
    {
        return [
            'old_expires_at' => 'datetime',
            'new_expires_at' => 'datetime',
            'duration_days' => 'integer',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isAutomatic(): bool
    {
        return $this->method === 'automatic';
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query
            ->orderByDesc('new_expires_at')
            ->orderByDesc('id');
    }

    public function scopeWithIndexRelations(Builder $query): Builder
    {
        return $query->with([
            'subscription:id,organization_id,plan,status',
            'subscription.organization:id,name',
            'user:id,name,email',
        ]);
    }

    public function scopeForSuperadminIndex(Builder $query): Builder
    {
        return $query
            ->select(self::SUMMARY_COLUMNS)
            ->withIndexRelations()
            ->latestFirst();
    }

    public function scopeForOrganizationValue(Builder $query, int|string|null $organizationId): Builder
    {
        if (blank($organizationId)) {
            return $query;
        }

        return $query->whereRelation('subscription', 'organization_id', (int) $organizationId);
    }
}
