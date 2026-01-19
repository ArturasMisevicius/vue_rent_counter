<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Enums\ApprovalStatus;
use App\Enums\AutomationLevel;
use App\Traits\Auditable;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Support\Carbon;

class Invoice extends Model
{
    use HasFactory, BelongsToTenant, Auditable;

    protected $dateFormat = 'Y-m-d H:i:s.u';

    /**
     * Attributes to exclude from audit logging.
     *
     * @var array<int, string>
     */
    protected array $auditExclude = [
        'snapshot_data',
        'approval_metadata',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice): void {
            if (empty($invoice->tenant_renter_id) && !empty($invoice->tenant_id)) {
                $invoice->tenant_renter_id = $invoice->tenant_id;
            }
        });

        // Prevent modification of finalized or paid invoices
        static::updating(function ($invoice) {
            $originalStatus = $invoice->getOriginal('status');
            
            // If the original status was FINALIZED or PAID
            $isImmutable = $originalStatus === InvoiceStatus::FINALIZED->value 
                || $originalStatus === InvoiceStatus::FINALIZED
                || $originalStatus === InvoiceStatus::PAID->value
                || $originalStatus === InvoiceStatus::PAID;
            
            if ($isImmutable) {
                // Allow only status changes and payment metadata updates.
                $dirtyAttributes = array_keys($invoice->getDirty());
                $allowedMutableAttributes = [
                    'status',
                    'paid_at',
                    'payment_reference',
                    'paid_amount',
                    'overdue_notified_at',
                ];
                
                // If only allowed attributes are changing, allow it.
                if (empty(array_diff($dirtyAttributes, $allowedMutableAttributes))) {
                    return;
                }
                
                // If status is changing along with other fields, allow only the allowed mutable fields.
                if (in_array('status', $dirtyAttributes, true)) {
                    foreach ($dirtyAttributes as $attr) {
                        if (!in_array($attr, $allowedMutableAttributes, true)) {
                            $invoice->$attr = $invoice->getOriginal($attr);
                        }
                    }

                    return;
                }
                
                // Prevent all other modifications by throwing exception
                throw new \App\Exceptions\InvoiceAlreadyFinalizedException($invoice->id);
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'tenant_renter_id',
        'billing_record_id',
        'invoice_number',
        'billing_period_start',
        'billing_period_end',
        'due_date',
        'total_amount',
        'status',
        'finalized_at',
        'paid_at',
        'payment_reference',
        'paid_amount',
        'overdue_notified_at',
        'generated_at',
        'generated_by',
        'items',
        'snapshot_data',
        'snapshot_created_at',
        'approval_status',
        'automation_level',
        'approval_deadline',
        'approval_metadata',
        'approved_by',
        'approved_at',
        'currency_id',
        'original_currency_id',
        'exchange_rate',
        'conversion_date',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'billing_period_start' => 'datetime',
            'billing_period_end' => 'datetime',
            'due_date' => 'date',
            'total_amount' => 'decimal:2',
            'status' => InvoiceStatus::class,
            'finalized_at' => 'datetime',
            'paid_at' => 'datetime',
            'paid_amount' => 'decimal:2',
            'overdue_notified_at' => 'datetime',
            'generated_at' => 'datetime',
            'items' => 'array',
            'snapshot_data' => 'array',
            'snapshot_created_at' => 'datetime',
            'approval_status' => ApprovalStatus::class,
            'automation_level' => AutomationLevel::class,
            'approval_deadline' => 'datetime',
            'approval_metadata' => 'array',
            'approved_at' => 'datetime',
            'exchange_rate' => 'decimal:6',
            'conversion_date' => 'date',
        ];
    }

    public function setBillingPeriodStartAttribute(mixed $value): void
    {
        $this->attributes['billing_period_start'] = $value === null ? null : Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function setBillingPeriodEndAttribute(mixed $value): void
    {
        $this->attributes['billing_period_end'] = $value === null ? null : Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function setDueDateAttribute(mixed $value): void
    {
        $this->attributes['due_date'] = $value === null ? null : Carbon::parse($value)->toDateString();
    }

    /**
     * Get the tenant (renter) this invoice belongs to.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_renter_id');
    }

    /**
     * Alias for tenant relationship (for clarity in PDFs and views).
     */
    public function tenantRenter(): BelongsTo
    {
        return $this->tenant();
    }

    /**
     * Get the property through the tenant.
     */
    public function property(): HasOneThrough
    {
        return $this->hasOneThrough(
            Property::class,
            Tenant::class,
            'id',              // Foreign key on tenants table
            'id',              // Foreign key on properties table
            'tenant_renter_id', // Local key on invoices table
            'property_id'      // Local key on tenants table
        );
    }

    /**
     * Get the items for this invoice.
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Get the currency for this invoice.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the original currency for this invoice (before conversion).
     */
    public function originalCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'original_currency_id');
    }

    /**
     * Get the user who approved this invoice.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Finalize the invoice, making it immutable.
     */
    public function finalize(): void
    {
        $this->status = InvoiceStatus::FINALIZED;
        $this->finalized_at = now();
        $this->save();
    }

    /**
     * Check if the invoice is finalized.
     */
    public function isFinalized(): bool
    {
        return $this->status === InvoiceStatus::FINALIZED;
    }

    /**
     * Check if the invoice is a draft.
     */
    public function isDraft(): bool
    {
        return $this->status === InvoiceStatus::DRAFT;
    }

    /**
     * Check if the invoice is paid.
     */
    public function isPaid(): bool
    {
        return $this->status === InvoiceStatus::PAID;
    }

    /**
    * Check if the invoice is overdue (due_date in past and not paid).
    */
    public function isOverdue(): bool
    {
        return $this->due_date !== null
            && !$this->isPaid()
            && $this->due_date->isPast();
    }

    /**
     * Scope a query to draft invoices.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDraft($query)
    {
        return $query->where('status', InvoiceStatus::DRAFT);
    }

    /**
     * Scope a query to finalized invoices.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFinalized($query)
    {
        return $query->where('status', InvoiceStatus::FINALIZED);
    }

    /**
     * Scope a query to paid invoices.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePaid($query)
    {
        return $query->where('status', InvoiceStatus::PAID);
    }

    /**
     * Scope a query to invoices for a specific billing period.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $startDate
     * @param string $endDate
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForPeriod($query, string $startDate, string $endDate)
    {
        return $query->whereDate('billing_period_start', '>=', $startDate)
            ->whereDate('billing_period_end', '<=', $endDate);
    }

    /**
     * Scope a query to invoices for a specific tenant.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $tenantId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_renter_id', $tenantId);
    }

    /**
     * Check if the invoice has a snapshot.
     */
    public function hasSnapshot(): bool
    {
        return !empty($this->snapshot_data);
    }

    /**
     * Get the snapshot summary.
     */
    public function getSnapshotSummary(): array
    {
        if (!$this->hasSnapshot()) {
            return ['has_snapshot' => false];
        }

        $snapshot = $this->snapshot_data;
        
        return [
            'has_snapshot' => true,
            'created_at' => $this->snapshot_created_at,
            'tariff_count' => count($snapshot['tariff_snapshots'] ?? []),
            'service_configuration_count' => count($snapshot['service_configuration_snapshots'] ?? []),
            'utility_service_count' => count($snapshot['utility_service_snapshots'] ?? []),
            'calculation_complexity_score' => $snapshot['calculation_metadata']['calculation_complexity_score'] ?? 0,
            'requires_approval' => $snapshot['calculation_metadata']['approval_workflow_required'] ?? false,
            'has_seasonal_adjustments' => $snapshot['calculation_metadata']['seasonal_adjustments_applied'] ?? false,
            'has_heating_calculations' => $snapshot['calculation_metadata']['heating_calculations_included'] ?? false,
            'has_shared_services' => $snapshot['calculation_metadata']['shared_service_distributions_applied'] ?? false,
        ];
    }

    /**
     * Check if the invoice can be recalculated from its snapshot.
     */
    public function canRecalculateFromSnapshot(): bool
    {
        if (!$this->hasSnapshot()) {
            return false;
        }

        $snapshot = $this->snapshot_data;
        
        // Check that all required snapshot components exist
        $requiredComponents = [
            'tariff_snapshots',
            'service_configuration_snapshots',
            'utility_service_snapshots',
            'billing_period',
            'calculation_metadata',
        ];

        foreach ($requiredComponents as $component) {
            if (!isset($snapshot[$component])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if the invoice is approved.
     */
    public function isApproved(): bool
    {
        return $this->approval_status?->isApproved() ?? false;
    }

    /**
     * Check if the invoice is pending approval.
     */
    public function isPendingApproval(): bool
    {
        return $this->approval_status?->isPending() ?? false;
    }

    /**
     * Check if the invoice was rejected.
     */
    public function isRejected(): bool
    {
        return $this->approval_status?->isRejected() ?? false;
    }

    /**
     * Check if the invoice requires approval.
     */
    public function requiresApproval(): bool
    {
        return $this->approval_status === ApprovalStatus::PENDING ||
               $this->approval_status === ApprovalStatus::REQUIRES_REVIEW;
    }

    /**
     * Check if the approval deadline has passed.
     */
    public function isApprovalOverdue(): bool
    {
        return $this->approval_deadline !== null &&
               $this->approval_deadline->isPast() &&
               $this->isPendingApproval();
    }

    /**
     * Approve the invoice.
     */
    public function approve(?User $approver = null): void
    {
        $this->approval_status = ApprovalStatus::APPROVED;
        $this->approved_by = $approver?->id ?? auth()->id();
        $this->approved_at = now();
        $this->save();
    }

    /**
     * Reject the invoice.
     */
    public function reject(?User $rejector = null, ?string $reason = null): void
    {
        $this->approval_status = ApprovalStatus::REJECTED;
        $this->approved_by = $rejector?->id ?? auth()->id();
        $this->approved_at = now();
        
        if ($reason) {
            $metadata = $this->approval_metadata ?? [];
            $metadata['rejection_reason'] = $reason;
            $metadata['rejected_at'] = now()->toISOString();
            $this->approval_metadata = $metadata;
        }
        
        $this->save();
    }

    /**
     * Auto-approve the invoice.
     */
    public function autoApprove(): void
    {
        $this->approval_status = ApprovalStatus::AUTO_APPROVED;
        $this->approved_at = now();
        $this->save();
    }

    /**
     * Mark the invoice as requiring review.
     */
    public function markForReview(?string $reason = null): void
    {
        $this->approval_status = ApprovalStatus::REQUIRES_REVIEW;
        
        if ($reason) {
            $metadata = $this->approval_metadata ?? [];
            $metadata['review_reason'] = $reason;
            $metadata['marked_for_review_at'] = now()->toISOString();
            $this->approval_metadata = $metadata;
        }
        
        $this->save();
    }

    /**
     * Get the automation level description.
     */
    public function getAutomationDescription(): string
    {
        return $this->automation_level?->getDescription() ?? 'Manual processing';
    }

    /**
     * Check if the invoice was fully automated.
     */
    public function isFullyAutomated(): bool
    {
        return $this->automation_level === AutomationLevel::FULLY_AUTOMATED;
    }

    /**
     * Check if the invoice was semi-automated.
     */
    public function isSemiAutomated(): bool
    {
        return $this->automation_level === AutomationLevel::SEMI_AUTOMATED;
    }

    /**
     * Check if the invoice was processed manually.
     */
    public function isManual(): bool
    {
        return $this->automation_level === AutomationLevel::MANUAL;
    }

    /**
     * Scope a query to approved invoices.
     */
    public function scopeApproved($query)
    {
        return $query->whereIn('approval_status', [
            ApprovalStatus::APPROVED->value,
            ApprovalStatus::AUTO_APPROVED->value,
        ]);
    }

    /**
     * Scope a query to pending approval invoices.
     */
    public function scopePendingApproval($query)
    {
        return $query->whereIn('approval_status', [
            ApprovalStatus::PENDING->value,
            ApprovalStatus::REQUIRES_REVIEW->value,
        ]);
    }

    /**
     * Scope a query to rejected invoices.
     */
    public function scopeRejected($query)
    {
        return $query->where('approval_status', ApprovalStatus::REJECTED->value);
    }

    /**
     * Scope a query to overdue approval invoices.
     */
    public function scopeApprovalOverdue($query)
    {
        return $query->whereIn('approval_status', [
                ApprovalStatus::PENDING->value,
                ApprovalStatus::REQUIRES_REVIEW->value,
            ])
            ->where('approval_deadline', '<', now());
    }

    /**
     * Scope a query to invoices by automation level.
     */
    public function scopeByAutomationLevel($query, AutomationLevel $level)
    {
        return $query->where('automation_level', $level->value);
    }
}
