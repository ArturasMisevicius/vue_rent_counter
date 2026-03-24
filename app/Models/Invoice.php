<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;

    private const ADMIN_WORKSPACE_COLUMNS = [
        'id',
        'organization_id',
        'property_id',
        'tenant_user_id',
        'invoice_number',
        'billing_period_start',
        'billing_period_end',
        'status',
        'currency',
        'total_amount',
        'amount_paid',
        'paid_amount',
        'due_date',
        'finalized_at',
        'paid_at',
        'last_reminder_sent_at',
        'payment_reference',
        'items',
        'snapshot_data',
        'notes',
        'document_path',
        'created_at',
        'updated_at',
    ];

    private const TENANT_WORKSPACE_COLUMNS = [
        'id',
        'organization_id',
        'property_id',
        'tenant_user_id',
        'invoice_number',
        'billing_period_start',
        'billing_period_end',
        'status',
        'currency',
        'total_amount',
        'amount_paid',
        'paid_amount',
        'due_date',
        'paid_at',
        'last_reminder_sent_at',
        'items',
        'snapshot_data',
        'document_path',
    ];

    protected $fillable = [
        'organization_id',
        'property_id',
        'tenant_user_id',
        'invoice_number',
        'billing_period_start',
        'billing_period_end',
        'status',
        'currency',
        'total_amount',
        'amount_paid',
        'paid_amount',
        'due_date',
        'finalized_at',
        'paid_at',
        'last_reminder_sent_at',
        'payment_reference',
        'snapshot_data',
        'snapshot_created_at',
        'items',
        'generated_at',
        'generated_by',
        'approval_status',
        'automation_level',
        'approval_deadline',
        'approval_metadata',
        'approved_by',
        'approved_at',
        'document_path',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'billing_period_start' => 'date',
            'billing_period_end' => 'date',
            'status' => InvoiceStatus::class,
            'total_amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'due_date' => 'date',
            'finalized_at' => 'datetime',
            'paid_at' => 'datetime',
            'last_reminder_sent_at' => 'datetime',
            'snapshot_data' => 'array',
            'snapshot_created_at' => 'datetime',
            'generated_at' => 'datetime',
            'approval_deadline' => 'datetime',
            'approval_metadata' => 'array',
            'approved_at' => 'datetime',
        ];
    }

    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_user_id', $tenantId);
    }

    public function scopeForProperty(Builder $query, int $propertyId): Builder
    {
        return $query->where('property_id', $propertyId);
    }

    public function scopeForBillingPeriod(
        Builder $query,
        CarbonInterface|string $periodStart,
        CarbonInterface|string $periodEnd,
    ): Builder {
        $resolvedStart = $periodStart instanceof CarbonInterface ? $periodStart->toDateString() : $periodStart;
        $resolvedEnd = $periodEnd instanceof CarbonInterface ? $periodEnd->toDateString() : $periodEnd;

        return $query
            ->whereDate('billing_period_start', $resolvedStart)
            ->whereDate('billing_period_end', $resolvedEnd);
    }

    public function scopeOutstanding(Builder $query): Builder
    {
        return $query->whereIn('status', InvoiceStatus::outstandingValues());
    }

    public function scopePendingAttention(Builder $query): Builder
    {
        return $query->whereIn('status', InvoiceStatus::pendingAttentionValues());
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query
            ->whereOverdueAsOf()
            ->orderBy('due_date')
            ->orderBy('billing_period_end')
            ->orderBy('id');
    }

    public function scopeWhereOverdueAsOf(Builder $query, CarbonInterface|string|null $asOf = null): Builder
    {
        $today = $asOf instanceof CarbonInterface
            ? $asOf->copy()->startOfDay()->toDateString()
            : Carbon::parse((string) ($asOf ?: now()->toDateString()))->startOfDay()->toDateString();

        return $query
            ->whereIn('status', InvoiceStatus::outstandingValues())
            ->where(function (Builder $overdueQuery) use ($today): void {
                $overdueQuery
                    ->where(function (Builder $dueDateQuery) use ($today): void {
                        $dueDateQuery
                            ->whereNotNull('due_date')
                            ->whereDate('due_date', '<', $today);
                    })
                    ->orWhere(function (Builder $fallbackQuery) use ($today): void {
                        $fallbackQuery
                            ->whereNull('due_date')
                            ->whereDate('billing_period_end', '<', $today);
                    });
            });
    }

    public function scopeWhereNotOverdueAsOf(Builder $query, CarbonInterface|string|null $asOf = null): Builder
    {
        $today = $asOf instanceof CarbonInterface
            ? $asOf->copy()->startOfDay()->toDateString()
            : Carbon::parse((string) ($asOf ?: now()->toDateString()))->startOfDay()->toDateString();

        return $query->where(function (Builder $notOverdueQuery) use ($today): void {
            $notOverdueQuery
                ->where(function (Builder $dueDateQuery) use ($today): void {
                    $dueDateQuery
                        ->whereNotNull('due_date')
                        ->whereDate('due_date', '>=', $today);
                })
                ->orWhere(function (Builder $fallbackQuery) use ($today): void {
                    $fallbackQuery
                        ->whereNull('due_date')
                        ->whereDate('billing_period_end', '>=', $today);
                });
        });
    }

    public function scopeAwaitingPayment(Builder $query, CarbonInterface|string|null $asOf = null): Builder
    {
        return $query
            ->where('status', InvoiceStatus::FINALIZED)
            ->whereNotOverdueAsOf($asOf);
    }

    public function scopePaidBetween(
        Builder $query,
        CarbonInterface $periodStart,
        CarbonInterface $periodEnd,
    ): Builder {
        return $query
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$periodStart, $periodEnd]);
    }

    public function scopeLatestBillingFirst(Builder $query): Builder
    {
        return $query
            ->orderByDesc('billing_period_start')
            ->orderByDesc('id');
    }

    public function scopeOrderedByDueDate(Builder $query): Builder
    {
        return $query
            ->orderBy('due_date')
            ->orderBy('id');
    }

    public function scopeWithAdminWorkspaceRelations(Builder $query): Builder
    {
        return $query->with([
            'property:id,organization_id,building_id,name,unit_number',
            'property.building:id,organization_id,name',
            'tenant:id,organization_id,name,email',
        ]);
    }

    public function scopeWithIndexRelations(Builder $query, bool $includeOrganization = false): Builder
    {
        $query->withAdminWorkspaceRelations();

        if (! $includeOrganization) {
            return $query;
        }

        return $query->with([
            'organization:id,name',
        ]);
    }

    public function scopeWithTenantWorkspaceRelations(Builder $query): Builder
    {
        return $query->with([
            'tenant:id,organization_id,name,email',
            'property:id,organization_id,building_id,name,unit_number,type,floor_area_sqm',
            'property.building:id,organization_id,name,address_line_1,address_line_2,city,postal_code,country_code',
            'payments:id,invoice_id,organization_id,amount,method,reference,paid_at,notes',
        ]);
    }

    public function scopeForAdminWorkspace(Builder $query, int $organizationId): Builder
    {
        return $query
            ->select(self::ADMIN_WORKSPACE_COLUMNS)
            ->forOrganization($organizationId)
            ->withAdminWorkspaceRelations()
            ->latestBillingFirst();
    }

    public function scopeForTenantWorkspace(
        Builder $query,
        int $organizationId,
        int $tenantId,
        ?int $propertyId = null,
    ): Builder {
        return $query
            ->select(self::TENANT_WORKSPACE_COLUMNS)
            ->forOrganization($organizationId)
            ->forTenant($tenantId)
            ->when(
                $propertyId !== null,
                fn (Builder $tenantWorkspaceQuery): Builder => $tenantWorkspaceQuery->forProperty($propertyId),
            )
            ->withTenantWorkspaceRelations()
            ->latestBillingFirst();
    }

    public function scopeForWorkspaceIndex(Builder $query, bool $isSuperadmin, ?int $organizationId): Builder
    {
        $query = $query
            ->select(self::ADMIN_WORKSPACE_COLUMNS)
            ->withIndexRelations($isSuperadmin)
            ->latestBillingFirst();

        if ($isSuperadmin) {
            return $query;
        }

        if ($organizationId === null) {
            return $query->whereKey(-1);
        }

        return $query->forOrganization($organizationId);
    }

    public function scopeForOrganizationValue(Builder $query, int|string|null $organizationId): Builder
    {
        if (blank($organizationId)) {
            return $query;
        }

        return $query->where('organization_id', $organizationId);
    }

    public function scopeForPropertyValue(Builder $query, int|string|null $propertyId): Builder
    {
        if (blank($propertyId)) {
            return $query;
        }

        return $query->where('property_id', $propertyId);
    }

    public function scopeForIdValues(Builder $query, array|string|null $invoiceIds): Builder
    {
        $resolvedIds = collect(is_array($invoiceIds) ? $invoiceIds : explode(',', (string) $invoiceIds))
            ->filter(fn (mixed $value): bool => is_numeric($value))
            ->map(fn (mixed $value): int => (int) $value)
            ->unique()
            ->values()
            ->all();

        if ($resolvedIds === []) {
            return $query;
        }

        return $query->whereIn('id', $resolvedIds);
    }

    public function scopeSearchTenantName(Builder $query, ?string $search): Builder
    {
        $term = trim((string) $search);

        if ($term === '') {
            return $query;
        }

        return $query->whereHas(
            'tenant',
            fn (Builder $tenantQuery): Builder => $tenantQuery->where('name', 'like', "%{$term}%"),
        );
    }

    public function scopeForBillingPeriodRange(Builder $query, ?string $from, ?string $to): Builder
    {
        $query
            ->when(
                filled($from),
                fn (Builder $rangeQuery): Builder => $rangeQuery->whereDate('billing_period_start', '>=', (string) $from),
            )
            ->when(
                filled($to),
                fn (Builder $rangeQuery): Builder => $rangeQuery->whereDate('billing_period_end', '<=', (string) $to),
            );

        return $query;
    }

    public function scopeForEffectiveStatusValues(
        Builder $query,
        array|string|null $statuses,
        CarbonInterface|string|null $asOf = null,
    ): Builder {
        $resolvedStatuses = collect(is_array($statuses) ? $statuses : [$statuses])
            ->filter(fn (mixed $value): bool => filled($value))
            ->map(fn (mixed $value): string => (string) $value)
            ->unique()
            ->values()
            ->all();

        if ($resolvedStatuses === []) {
            return $query;
        }

        return $query->where(function (Builder $statusQuery) use ($resolvedStatuses, $asOf): void {
            foreach ($resolvedStatuses as $statusValue) {
                $status = InvoiceStatus::tryFrom($statusValue);

                if (! $status instanceof InvoiceStatus) {
                    continue;
                }

                $statusQuery->orWhere(function (Builder $candidateQuery) use ($status, $asOf): void {
                    match ($status) {
                        InvoiceStatus::DRAFT => $candidateQuery->where('status', InvoiceStatus::DRAFT),
                        InvoiceStatus::FINALIZED => $candidateQuery
                            ->where('status', InvoiceStatus::FINALIZED)
                            ->whereNotOverdueAsOf($asOf),
                        InvoiceStatus::PARTIALLY_PAID => $candidateQuery
                            ->where('status', InvoiceStatus::PARTIALLY_PAID)
                            ->whereNotOverdueAsOf($asOf),
                        InvoiceStatus::PAID => $candidateQuery->where('status', InvoiceStatus::PAID),
                        InvoiceStatus::OVERDUE => $candidateQuery->whereOverdueAsOf($asOf),
                        InvoiceStatus::VOID => $candidateQuery->where('status', InvoiceStatus::VOID),
                    };
                });
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_user_id');
    }

    public function currencyDefinition(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency', 'code');
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function generationAudits(): HasMany
    {
        return $this->hasMany(InvoiceGenerationAudit::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class);
    }

    public function emailLogs(): HasMany
    {
        return $this->hasMany(InvoiceEmailLog::class);
    }

    public function reminderLogs(): HasMany
    {
        return $this->hasMany(InvoiceReminderLog::class);
    }

    public function billingRecords(): HasMany
    {
        return $this->hasMany(BillingRecord::class);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getItemsAttribute(mixed $value): array
    {
        $items = match (true) {
            is_string($value) => json_decode($value, true) ?: [],
            is_array($value) => $value,
            default => [],
        };

        return $this->normalizeItems($items);
    }

    public function setItemsAttribute(mixed $value): void
    {
        $items = is_array($value) ? $value : [];

        $this->attributes['items'] = json_encode($this->normalizeItems($items));
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getNormalizedPaidAmountAttribute(): float
    {
        return max((float) $this->amount_paid, (float) $this->paid_amount);
    }

    public function getOutstandingBalanceAttribute(): float
    {
        return max(0, (float) $this->total_amount - $this->normalized_paid_amount);
    }

    public function overdueReferenceDate(): ?CarbonInterface
    {
        if ($this->due_date instanceof CarbonInterface) {
            return $this->due_date->copy()->startOfDay();
        }

        if ($this->billing_period_end instanceof CarbonInterface) {
            return $this->billing_period_end->copy()->startOfDay();
        }

        if (filled($this->due_date)) {
            return Carbon::parse((string) $this->due_date)->startOfDay();
        }

        if (filled($this->billing_period_end)) {
            return Carbon::parse((string) $this->billing_period_end)->startOfDay();
        }

        return null;
    }

    public function isOverdue(CarbonInterface|string|null $asOf = null): bool
    {
        $status = $this->status instanceof InvoiceStatus
            ? $this->status
            : InvoiceStatus::tryFrom((string) $this->status);

        if ($status === null || ! in_array($status->value, InvoiceStatus::outstandingValues(), true)) {
            return false;
        }

        if ($this->outstanding_balance <= 0) {
            return false;
        }

        $referenceDate = $this->overdueReferenceDate();

        if ($referenceDate === null) {
            return false;
        }

        $comparisonDate = $asOf instanceof CarbonInterface
            ? $asOf->copy()->startOfDay()
            : Carbon::parse((string) ($asOf ?: now()->toDateString()))->startOfDay();

        return $referenceDate->lt($comparisonDate);
    }

    public function overdueDays(CarbonInterface|string|null $asOf = null): int
    {
        if (! $this->isOverdue($asOf)) {
            return 0;
        }

        $referenceDate = $this->overdueReferenceDate();

        if ($referenceDate === null) {
            return 0;
        }

        $comparisonDate = $asOf instanceof CarbonInterface
            ? $asOf->copy()->startOfDay()
            : Carbon::parse((string) ($asOf ?: now()->toDateString()))->startOfDay();

        return (int) $referenceDate->diffInDays($comparisonDate);
    }

    public function effectiveStatus(CarbonInterface|string|null $asOf = null): InvoiceStatus
    {
        $status = $this->status instanceof InvoiceStatus
            ? $this->status
            : InvoiceStatus::tryFrom((string) $this->status)
            ?? InvoiceStatus::DRAFT;

        if ($this->isOverdue($asOf)) {
            return InvoiceStatus::OVERDUE;
        }

        if ($status === InvoiceStatus::OVERDUE) {
            return $this->normalized_paid_amount > 0
                ? InvoiceStatus::PARTIALLY_PAID
                : InvoiceStatus::FINALIZED;
        }

        return $status;
    }

    public function canViewFromAdminWorkspace(): bool
    {
        return $this->effectiveStatus() !== InvoiceStatus::DRAFT;
    }

    public function canEditFromAdminWorkspace(): bool
    {
        return $this->effectiveStatus() === InvoiceStatus::DRAFT;
    }

    public function canFinalizeFromAdminWorkspace(): bool
    {
        return $this->effectiveStatus() === InvoiceStatus::DRAFT;
    }

    public function canBeDeletedFromAdminWorkspace(): bool
    {
        return $this->effectiveStatus() === InvoiceStatus::DRAFT;
    }

    public function canProcessPaymentFromAdminWorkspace(): bool
    {
        return in_array($this->effectiveStatus(), [
            InvoiceStatus::FINALIZED,
            InvoiceStatus::PARTIALLY_PAID,
            InvoiceStatus::OVERDUE,
        ], true);
    }

    public function canSendEmailFromAdminWorkspace(): bool
    {
        return in_array($this->effectiveStatus(), [
            InvoiceStatus::FINALIZED,
            InvoiceStatus::PARTIALLY_PAID,
            InvoiceStatus::PAID,
            InvoiceStatus::OVERDUE,
        ], true);
    }

    public function canSendReminderFromAdminWorkspace(): bool
    {
        return $this->effectiveStatus() === InvoiceStatus::OVERDUE;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function normalizeItems(array $items): array
    {
        return array_map(function (array $item): array {
            if (array_key_exists('amount', $item)) {
                $item['amount'] = round((float) $item['amount'], 2);
            }

            return $item;
        }, $items);
    }
}
