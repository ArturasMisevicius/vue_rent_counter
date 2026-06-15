<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TenantKycProfileStatus;
use Database\Factories\TenantKycProfileFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TenantKycProfile extends Model
{
    /** @use HasFactory<TenantKycProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'tenant_id',
        'status',
        'submitted_at',
        'reviewed_by_user_id',
        'reviewed_at',
        'approved_at',
        'rejected_at',
        'rejection_reason',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TenantKycProfileStatus::class,
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(TenantKycDocument::class, 'kyc_profile_id');
    }

    public function activeDocuments(): HasMany
    {
        return $this->documents()->activeForChecklist();
    }

    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForStatus(Builder $query, TenantKycProfileStatus|string|null $status): Builder
    {
        if ($status instanceof TenantKycProfileStatus) {
            return $query->where('status', $status);
        }

        if (blank($status)) {
            return $query;
        }

        return $query->where('status', $status);
    }

    public function scopeNeedsReview(Builder $query): Builder
    {
        return $query->whereIn('status', [
            TenantKycProfileStatus::PENDING_REVIEW,
            TenantKycProfileStatus::REJECTED,
            TenantKycProfileStatus::EXPIRED,
        ]);
    }

    public function isVerified(): bool
    {
        return $this->status === TenantKycProfileStatus::VERIFIED;
    }
}
