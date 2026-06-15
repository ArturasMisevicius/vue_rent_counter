<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\BillingPeriodFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingPeriod extends Model
{
    /** @use HasFactory<BillingPeriodFactory> */
    use HasFactory;

    private const WORKSPACE_COLUMNS = [
        'id',
        'organization_id',
        'name',
        'starts_at',
        'ends_at',
        'reading_submission_deadline',
        'invoice_generation_date',
        'payment_due_date',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'organization_id',
        'name',
        'starts_at',
        'ends_at',
        'reading_submission_deadline',
        'invoice_generation_date',
        'payment_due_date',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'reading_submission_deadline' => 'date',
            'invoice_generation_date' => 'date',
            'payment_due_date' => 'date',
        ];
    }

    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeForDateRange(Builder $query, string $startsAt, string $endsAt): Builder
    {
        return $query
            ->whereDate('starts_at', $startsAt)
            ->whereDate('ends_at', $endsAt);
    }

    public function scopeForInvoiceGenerationDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('invoice_generation_date', $date);
    }

    public function scopeDueForReadingSubmission(Builder $query, string $date): Builder
    {
        return $query->whereDate('reading_submission_deadline', $date);
    }

    public function scopeOrderedForWorkspace(Builder $query): Builder
    {
        return $query
            ->orderByDesc('starts_at')
            ->orderByDesc('id');
    }

    public function scopeWithWorkspaceCounts(Builder $query): Builder
    {
        return $query->withCount([
            'invoices',
            'invoices as reading_request_invoices_count' => fn (Builder $invoiceQuery): Builder => $invoiceQuery
                ->where('automation_level', 'reading_request'),
        ]);
    }

    public function scopeWithWorkspaceRelations(Builder $query, bool $includeOrganization = false): Builder
    {
        $query->withWorkspaceCounts();

        if (! $includeOrganization) {
            return $query;
        }

        return $query->with([
            'organization:id,name',
        ]);
    }

    public function scopeForWorkspaceIndex(Builder $query, bool $isSuperadmin, ?int $organizationId): Builder
    {
        $query = $query
            ->select(self::WORKSPACE_COLUMNS)
            ->withWorkspaceRelations($isSuperadmin)
            ->orderedForWorkspace();

        if ($isSuperadmin) {
            return $query;
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

    public function extraCharges(): HasMany
    {
        return $this->hasMany(ExtraCharge::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function generationLogs(): HasMany
    {
        return $this->hasMany(BillingGenerationLog::class);
    }
}
