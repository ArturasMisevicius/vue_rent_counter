<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\BillingGenerationLogFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingGenerationLog extends Model
{
    /** @use HasFactory<BillingGenerationLogFactory> */
    use HasFactory;

    private const WORKSPACE_COLUMNS = [
        'id',
        'organization_id',
        'billing_period_id',
        'initiated_by_user_id',
        'source',
        'status',
        'dry_run',
        'billing_period_start',
        'billing_period_end',
        'invoice_generation_date',
        'reading_submission_deadline',
        'payment_due_date',
        'eligible_count',
        'created_count',
        'skipped_count',
        'warning_count',
        'error_count',
        'notified_tenants_count',
        'summary',
        'started_at',
        'finished_at',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'organization_id',
        'billing_period_id',
        'initiated_by_user_id',
        'source',
        'status',
        'dry_run',
        'billing_period_start',
        'billing_period_end',
        'invoice_generation_date',
        'reading_submission_deadline',
        'payment_due_date',
        'eligible_count',
        'created_count',
        'skipped_count',
        'warning_count',
        'error_count',
        'notified_tenants_count',
        'summary',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'dry_run' => 'boolean',
            'billing_period_start' => 'date',
            'billing_period_end' => 'date',
            'invoice_generation_date' => 'date',
            'reading_submission_deadline' => 'date',
            'payment_due_date' => 'date',
            'eligible_count' => 'integer',
            'created_count' => 'integer',
            'skipped_count' => 'integer',
            'warning_count' => 'integer',
            'error_count' => 'integer',
            'notified_tenants_count' => 'integer',
            'summary' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    public function scopeForWorkspaceIndex(Builder $query, bool $isSuperadmin, ?int $organizationId): Builder
    {
        $query = $query
            ->select(self::WORKSPACE_COLUMNS)
            ->with([
                'billingPeriod:id,organization_id,name,starts_at,ends_at',
                'initiatedBy:id,organization_id,name,email',
            ])
            ->withCount('items')
            ->latestFirst();

        if ($isSuperadmin) {
            return $query->with(['organization:id,name']);
        }

        if ($organizationId === null) {
            return $query->whereKey(-1);
        }

        return $query->forOrganization($organizationId);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function billingPeriod(): BelongsTo
    {
        return $this->belongsTo(BillingPeriod::class);
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BillingGenerationLogItem::class);
    }
}
