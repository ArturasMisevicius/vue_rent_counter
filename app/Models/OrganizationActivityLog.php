<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\OrganizationActivityLogFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationActivityLog extends Model
{
    /** @use HasFactory<OrganizationActivityLogFactory> */
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'user_id',
        'action',
        'resource_type',
        'resource_id',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeForResource(
        Builder $query,
        object $resource,
        int|string|null $resourceId = null,
    ): Builder {
        $resourceType = is_string($resource) ? $resource : $resource::class;
        $resolvedResourceId = $resourceId !== null
            ? (int) $resourceId
            : (is_string($resource) ? null : (int) $resource->getKey());

        $query = $query->where('resource_type', $resourceType);

        return $resolvedResourceId !== null
            ? $query->where('resource_id', $resolvedResourceId)
            : $query;
    }

    public function scopeWithActorSummary(Builder $query): Builder
    {
        return $query->with([
            'user:id,organization_id,name,email,role',
        ]);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
