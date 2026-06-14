<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LeadSourceType;
use Database\Factories\LeadSourceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeadSource extends Model
{
    /** @use HasFactory<LeadSourceFactory> */
    use HasFactory;

    private const WORKSPACE_COLUMNS = [
        'id',
        'organization_id',
        'name',
        'type',
        'description',
        'source_url',
        'privacy_note',
        'retention_days',
        'created_by_user_id',
        'imported_at',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'organization_id',
        'name',
        'type',
        'description',
        'source_url',
        'privacy_note',
        'retention_days',
        'created_by_user_id',
        'imported_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => LeadSourceType::class,
            'retention_days' => 'integer',
            'imported_at' => 'datetime',
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
            ->with([
                'organization:id,name',
                'creator:id,name,email',
            ])
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function importBatches(): HasMany
    {
        return $this->hasMany(LeadImportBatch::class);
    }

    public function listingLeads(): HasMany
    {
        return $this->hasMany(ListingLead::class);
    }
}
