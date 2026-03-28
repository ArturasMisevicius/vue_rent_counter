<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\OrganizationFeatureOverrideFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class OrganizationFeatureOverride extends Model
{
    /** @use HasFactory<OrganizationFeatureOverrideFactory> */
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'feature',
        'enabled',
        'reason',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
        ];
    }

    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeForFeature(Builder $query, string $feature): Builder
    {
        return $query->where('feature', $feature);
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
