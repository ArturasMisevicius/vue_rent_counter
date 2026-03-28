<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\OrganizationLimitOverrideFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class OrganizationLimitOverride extends Model
{
    /** @use HasFactory<OrganizationLimitOverrideFactory> */
    use HasFactory;

    public const SUPPORTED_DIMENSIONS = [
        'properties',
        'tenants',
        'meters',
        'invoices',
    ];

    protected $fillable = [
        'organization_id',
        'dimension',
        'value',
        'reason',
        'expires_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'integer',
            'expires_at' => 'datetime',
        ];
    }

    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeForDimension(Builder $query, string $dimension): Builder
    {
        return $query->where('dimension', $dimension);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where(function (Builder $builder): void {
            $builder
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
