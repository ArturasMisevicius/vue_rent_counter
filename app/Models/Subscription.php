<?php

namespace App\Models;

use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Support\Superadmin\Usage\OrganizationUsageReader;
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

    protected $fillable = [
        'organization_id',
        'plan',
        'plan_name_snapshot',
        'limits_snapshot',
        'status',
        'starts_at',
        'expires_at',
        'is_trial',
    ];

    protected function casts(): array
    {
        return [
            'plan' => SubscriptionPlan::class,
            'status' => SubscriptionStatus::class,
            'limits_snapshot' => 'array',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_trial' => 'boolean',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->whereIn('status', [
            SubscriptionStatus::ACTIVE,
            SubscriptionStatus::TRIALING,
            SubscriptionStatus::SUSPENDED,
        ]);
    }

    public function scopeForSuperadminResource(Builder $query): Builder
    {
        return $query
            ->select([
                'id',
                'organization_id',
                'plan',
                'plan_name_snapshot',
                'limits_snapshot',
                'status',
                'starts_at',
                'expires_at',
                'is_trial',
                'created_at',
            ])
            ->with([
                'organization' => fn ($organizationQuery) => $organizationQuery
                    ->select([
                        'id',
                        'name',
                        'slug',
                        'status',
                        'owner_user_id',
                        'created_at',
                    ])
                    ->withCount([
                        'users as tenant_users_count' => fn (Builder $userQuery): Builder => $userQuery
                            ->where('role', UserRole::TENANT),
                    ]),
            ]);
    }

    public function scopeExpiringWithinDays(Builder $query, int $days): Builder
    {
        return $query->whereBetween('expires_at', [
            now(),
            now()->copy()->addDays($days),
        ]);
    }

    public function propertiesUsed(OrganizationUsageReader $usageReader): int
    {
        if (! $this->organization instanceof Organization) {
            return 0;
        }

        return $usageReader->forOrganization($this->organization)->properties;
    }

    public function tenantsUsed(): int
    {
        return (int) data_get($this->organization, 'tenant_users_count', 0);
    }
}
