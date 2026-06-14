<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LeadOutreachChannel;
use Database\Factories\LeadOutreachTemplateFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadOutreachTemplate extends Model
{
    /** @use HasFactory<LeadOutreachTemplateFactory> */
    use HasFactory;

    private const WORKSPACE_COLUMNS = [
        'id',
        'organization_id',
        'name',
        'channel',
        'subject',
        'body',
        'locale',
        'is_active',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'organization_id',
        'name',
        'channel',
        'subject',
        'body',
        'locale',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'channel' => LeadOutreachChannel::class,
            'is_active' => 'boolean',
        ];
    }

    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeForWorkspaceIndex(Builder $query, bool $isSuperadmin, ?int $organizationId): Builder
    {
        return $query
            ->select(self::WORKSPACE_COLUMNS)
            ->when(
                ! $isSuperadmin,
                fn (Builder $workspaceQuery): Builder => $organizationId === null
                    ? $workspaceQuery->whereKey(-1)
                    : $workspaceQuery->forOrganization($organizationId),
            )
            ->with('organization:id,name')
            ->ordered();
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy('name')
            ->orderBy('id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
