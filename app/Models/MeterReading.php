<?php

namespace App\Models;

use App\Enums\MeterReadingStatus;
use App\Enums\MeterReadingSubmissionMethod;
use App\Enums\MeterReadingType;
use App\Enums\MeterReadingValidationStatus;
use Carbon\CarbonInterface;
use Database\Factories\MeterReadingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MeterReading extends Model
{
    /** @use HasFactory<MeterReadingFactory> */
    use HasFactory;

    private const WORKSPACE_COLUMNS = [
        'id',
        'organization_id',
        'billing_period_id',
        'property_id',
        'tenant_id',
        'meter_id',
        'submitted_by_user_id',
        'reading_value',
        'reading_date',
        'previous_value',
        'current_value',
        'consumption',
        'validation_status',
        'status',
        'submitted_at',
        'approved_by_user_id',
        'approved_at',
        'rejected_by_user_id',
        'rejected_at',
        'rejection_reason',
        'corrected_by_user_id',
        'correction_reason',
        'tenant_comment',
        'voided_at',
        'submission_method',
        'reading_type',
        'property_assignment_id',
        'move_out_process_id',
        'invoice_id',
        'notes',
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'organization_id',
        'billing_period_id',
        'property_id',
        'tenant_id',
        'meter_id',
        'submitted_by_user_id',
        'reading_value',
        'reading_date',
        'previous_value',
        'current_value',
        'consumption',
        'validation_status',
        'status',
        'submitted_at',
        'approved_by_user_id',
        'approved_at',
        'rejected_by_user_id',
        'rejected_at',
        'rejection_reason',
        'corrected_by_user_id',
        'correction_reason',
        'tenant_comment',
        'voided_at',
        'submission_method',
        'reading_type',
        'property_assignment_id',
        'move_out_process_id',
        'invoice_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'reading_value' => 'decimal:3',
            'previous_value' => 'decimal:3',
            'current_value' => 'decimal:3',
            'consumption' => 'decimal:3',
            'reading_date' => 'date',
            'validation_status' => MeterReadingValidationStatus::class,
            'status' => MeterReadingStatus::class,
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'voided_at' => 'datetime',
            'submission_method' => MeterReadingSubmissionMethod::class,
            'reading_type' => MeterReadingType::class,
        ];
    }

    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('meter_readings.organization_id', $organizationId);
    }

    public function scopeForProperty(Builder $query, int $propertyId): Builder
    {
        return $query->where('meter_readings.property_id', $propertyId);
    }

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('meter_readings.tenant_id', $tenantId);
    }

    public function scopeForMeter(Builder $query, int $meterId): Builder
    {
        return $query->where('meter_readings.meter_id', $meterId);
    }

    public function scopeForBillingPeriodId(Builder $query, int $billingPeriodId): Builder
    {
        return $query->where('meter_readings.billing_period_id', $billingPeriodId);
    }

    public function scopeForInvoice(Builder $query, int $invoiceId): Builder
    {
        return $query->where('meter_readings.invoice_id', $invoiceId);
    }

    public function scopeBetweenDates(
        Builder $query,
        CarbonInterface|string $startDate,
        CarbonInterface|string $endDate,
    ): Builder {
        $resolvedStart = $startDate instanceof CarbonInterface ? $startDate->toDateString() : $startDate;
        $resolvedEnd = $endDate instanceof CarbonInterface ? $endDate->toDateString() : $endDate;

        return $query
            ->whereDate('meter_readings.reading_date', '>=', $resolvedStart)
            ->whereDate('meter_readings.reading_date', '<=', $resolvedEnd);
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query
            ->orderByDesc('meter_readings.reading_date')
            ->orderByDesc('meter_readings.id');
    }

    public function scopeValid(Builder $query): Builder
    {
        return $query->where('meter_readings.validation_status', MeterReadingValidationStatus::VALID);
    }

    public function scopeActiveInbox(Builder $query): Builder
    {
        return $query->whereIn('meter_readings.status', MeterReadingStatus::activeValues());
    }

    public function scopeApprovedForInvoiceCalculation(Builder $query): Builder
    {
        return $query
            ->where('meter_readings.validation_status', MeterReadingValidationStatus::VALID)
            ->whereIn('meter_readings.status', MeterReadingStatus::invoiceCalculationValues());
    }

    public function scopeComparable(Builder $query): Builder
    {
        return $query->whereIn('meter_readings.validation_status', MeterReadingValidationStatus::comparableValues());
    }

    public function scopePendingReview(Builder $query): Builder
    {
        return $query->where('meter_readings.validation_status', MeterReadingValidationStatus::PENDING);
    }

    public function scopeSubmittedBy(Builder $query, int $userId): Builder
    {
        return $query->where('meter_readings.submitted_by_user_id', $userId);
    }

    public function scopeBeforeDate(Builder $query, CarbonInterface|string $date): Builder
    {
        $resolvedDate = $date instanceof CarbonInterface ? $date->toDateString() : $date;

        return $query->whereDate('meter_readings.reading_date', '<', $resolvedDate);
    }

    public function scopeBeforeOrOnDate(Builder $query, CarbonInterface|string $date): Builder
    {
        $resolvedDate = $date instanceof CarbonInterface ? $date->toDateString() : $date;

        return $query->whereDate('meter_readings.reading_date', '<=', $resolvedDate);
    }

    public function scopeWithWorkspaceRelations(Builder $query): Builder
    {
        return $query->with([
            'meter:id,organization_id,property_id,name,identifier,unit',
            'meter.property:id,organization_id,building_id,name',
            'property:id,organization_id,building_id,name',
            'property.building:id,organization_id,name',
            'submittedBy:id,name',
        ]);
    }

    public function scopeWithIndexRelations(Builder $query, bool $includeOrganization = false): Builder
    {
        $query->with([
            'meter:id,organization_id,property_id,name,identifier,unit',
            'meter.property:id,organization_id,building_id,name',
            'property:id,organization_id,building_id,name',
            'property.building:id,organization_id,name',
            'submittedBy:id,name',
        ]);

        if (! $includeOrganization) {
            return $query;
        }

        return $query->with([
            'organization:id,name',
        ]);
    }

    public function scopeForAdminWorkspace(Builder $query, int $organizationId): Builder
    {
        return $query
            ->select(self::WORKSPACE_COLUMNS)
            ->forOrganization($organizationId)
            ->withWorkspaceRelations()
            ->latestFirst();
    }

    public function scopeForWorkspaceIndex(Builder $query, bool $isSuperadmin, ?int $organizationId): Builder
    {
        $query = $query
            ->select(self::WORKSPACE_COLUMNS)
            ->withIndexRelations($isSuperadmin)
            ->latestFirst();

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

        return $query->where('meter_readings.organization_id', $organizationId);
    }

    public function scopeForValidationStatusValue(Builder $query, int|string|null $validationStatus): Builder
    {
        if (blank($validationStatus)) {
            return $query;
        }

        return $query->where('meter_readings.validation_status', $validationStatus);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function meter(): BelongsTo
    {
        return $this->belongsTo(Meter::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function billingPeriod(): BelongsTo
    {
        return $this->belongsTo(BillingPeriod::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by_user_id');
    }

    public function correctedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'corrected_by_user_id');
    }

    public function propertyAssignment(): BelongsTo
    {
        return $this->belongsTo(PropertyAssignment::class);
    }

    public function moveOutProcess(): BelongsTo
    {
        return $this->belongsTo(MoveOutProcess::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function audits(): HasMany
    {
        return $this->hasMany(MeterReadingAudit::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(MeterReadingVersion::class);
    }

    public function isTenantEditable(CarbonInterface|string|null $asOf = null): bool
    {
        $status = $this->status instanceof MeterReadingStatus
            ? $this->status
            : MeterReadingStatus::tryFrom((string) $this->status);

        if ($this->approved_at !== null || in_array($status, [MeterReadingStatus::APPROVED, MeterReadingStatus::CORRECTED, MeterReadingStatus::VOIDED], true)) {
            return false;
        }

        $invoice = $this->invoice;

        if (! $invoice instanceof Invoice) {
            return true;
        }

        $deadline = $invoice->approval_metadata['reading_submission_deadline'] ?? null;
        $deadlineDate = filled($deadline)
            ? (string) $deadline
            : ($invoice->due_date?->toDateString() ?? $invoice->billing_period_end?->toDateString());

        if ($deadlineDate === null) {
            return true;
        }

        $date = $asOf instanceof CarbonInterface
            ? $asOf->toDateString()
            : (string) ($asOf ?: now()->toDateString());

        return $date <= $deadlineDate;
    }

    public function recordVersion(string $event, ?User $actor = null, ?string $reason = null): MeterReadingVersion
    {
        $nextVersion = ((int) $this->versions()->max('version')) + 1;

        return $this->versions()->create([
            'organization_id' => $this->organization_id,
            'invoice_id' => $this->invoice_id,
            'billing_period_id' => $this->billing_period_id,
            'changed_by_user_id' => $actor?->id,
            'version' => $nextVersion,
            'event' => $event,
            'previous_value' => $this->previous_value,
            'current_value' => $this->current_value,
            'consumption' => $this->consumption,
            'status' => $this->status,
            'reading_date' => $this->reading_date,
            'reason' => $reason,
            'snapshot' => [
                'tenant_id' => $this->tenant_id,
                'property_id' => $this->property_id,
                'meter_id' => $this->meter_id,
                'invoice_id' => $this->invoice_id,
                'billing_period_id' => $this->billing_period_id,
                'reading_value' => $this->reading_value,
                'validation_status' => $this->validation_status?->value,
                'status' => $this->status?->value,
                'submitted_by_user_id' => $this->submitted_by_user_id,
                'submitted_at' => $this->submitted_at?->toISOString(),
                'approved_by_user_id' => $this->approved_by_user_id,
                'approved_at' => $this->approved_at?->toISOString(),
                'rejected_by_user_id' => $this->rejected_by_user_id,
                'rejected_at' => $this->rejected_at?->toISOString(),
                'rejection_reason' => $this->rejection_reason,
                'corrected_by_user_id' => $this->corrected_by_user_id,
                'correction_reason' => $this->correction_reason,
                'tenant_comment' => $this->tenant_comment,
                'voided_at' => $this->voided_at?->toISOString(),
            ],
        ]);
    }
}
