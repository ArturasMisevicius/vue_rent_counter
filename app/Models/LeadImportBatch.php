<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LeadImportBatchStatus;
use Database\Factories\LeadImportBatchFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeadImportBatch extends Model
{
    /** @use HasFactory<LeadImportBatchFactory> */
    use HasFactory;

    private const WORKSPACE_COLUMNS = [
        'id',
        'organization_id',
        'lead_source_id',
        'filename',
        'uploaded_by_user_id',
        'rows_total',
        'rows_imported',
        'rows_skipped',
        'rows_duplicates',
        'rows_failed',
        'status',
        'mapping_config',
        'error_summary',
        'created_at',
        'updated_at',
        'finished_at',
    ];

    protected $fillable = [
        'organization_id',
        'lead_source_id',
        'filename',
        'uploaded_by_user_id',
        'rows_total',
        'rows_imported',
        'rows_skipped',
        'rows_duplicates',
        'rows_failed',
        'status',
        'mapping_config',
        'error_summary',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => LeadImportBatchStatus::class,
            'mapping_config' => 'array',
            'error_summary' => 'array',
            'finished_at' => 'datetime',
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
                'source:id,organization_id,name,type',
                'uploader:id,name,email',
            ])
            ->latestFirst();
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

    public function source(): BelongsTo
    {
        return $this->belongsTo(LeadSource::class, 'lead_source_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function listingLeads(): HasMany
    {
        return $this->hasMany(ListingLead::class, 'import_batch_id');
    }
}
