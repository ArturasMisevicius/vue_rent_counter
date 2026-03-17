<?php

namespace App\Models;

use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use Database\Factories\SubscriptionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    /** @use HasFactory<SubscriptionFactory> */
    use HasFactory;

    private const CONTROL_PLANE_COLUMNS = [
        'id',
        'organization_id',
        'plan',
        'status',
        'starts_at',
        'expires_at',
        'is_trial',
        'property_limit_snapshot',
        'tenant_limit_snapshot',
        'meter_limit_snapshot',
        'invoice_limit_snapshot',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'organization_id',
        'plan',
        'status',
        'starts_at',
        'expires_at',
        'is_trial',
        'property_limit_snapshot',
        'tenant_limit_snapshot',
        'meter_limit_snapshot',
        'invoice_limit_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'plan' => SubscriptionPlan::class,
            'status' => SubscriptionStatus::class,
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_trial' => 'boolean',
            'property_limit_snapshot' => 'integer',
            'tenant_limit_snapshot' => 'integer',
            'meter_limit_snapshot' => 'integer',
            'invoice_limit_snapshot' => 'integer',
        ];
    }

    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query
            ->orderByDesc('expires_at')
            ->orderByDesc('starts_at')
            ->orderByDesc('id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', SubscriptionStatus::ACTIVE);
    }

    public function scopeActiveLike(Builder $query): Builder
    {
        return $query->whereIn('status', SubscriptionStatus::activeLikeValues());
    }

    public function scopeExpiringWithin(Builder $query, int $days): Builder
    {
        return $query
            ->activeLike()
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays($days)]);
    }

    public function scopeWithOrganizationSummary(Builder $query): Builder
    {
        return $query->with([
            'organization:id,name',
        ]);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function renewals(): HasMany
    {
        return $this->hasMany(SubscriptionRenewal::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function scopeForSuperadminControlPlane(Builder $query): Builder
    {
        return $query
            ->select(self::CONTROL_PLANE_COLUMNS)
            ->withOrganizationSummary()
            ->latestFirst();
    }

    public function isActiveLike(): bool
    {
        return in_array($this->status, [
            SubscriptionStatus::ACTIVE,
            SubscriptionStatus::TRIALING,
        ], true);
    }

    public function applyPlanSnapshots(SubscriptionPlan $plan): void
    {
        $limits = $plan->limits();

        $this->forceFill([
            'plan' => $plan,
            'property_limit_snapshot' => $limits['properties'],
            'tenant_limit_snapshot' => $limits['tenants'],
            'meter_limit_snapshot' => $limits['meters'],
            'invoice_limit_snapshot' => $limits['invoices'],
        ]);
    }
}
