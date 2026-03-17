<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
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
        'payment_reference',
        'items',
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
        'due_date',
        'paid_at',
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
            ->where('status', InvoiceStatus::OVERDUE)
            ->orderBy('due_date');
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

    public function scopeWithTenantWorkspaceRelations(Builder $query): Builder
    {
        return $query->with([
            'property:id,organization_id,building_id,name,unit_number,type,floor_area_sqm',
            'property.building:id,organization_id,name,address_line_1,address_line_2,city,postal_code,country_code',
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

    public function scopeForTenantWorkspace(Builder $query, int $organizationId, int $tenantId): Builder
    {
        return $query
            ->select(self::TENANT_WORKSPACE_COLUMNS)
            ->forOrganization($organizationId)
            ->forTenant($tenantId)
            ->withTenantWorkspaceRelations()
            ->latestBillingFirst();
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
