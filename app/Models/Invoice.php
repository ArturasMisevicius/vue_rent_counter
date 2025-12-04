<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Invoice extends Model
{
    use HasFactory, BelongsToTenant;

    protected $dateFormat = 'Y-m-d H:i:s.u';

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
                // Allow only status changes (e.g., from FINALIZED to PAID)
                $dirtyAttributes = array_keys($invoice->getDirty());
                
                // If only status is changing, allow it
                if (count($dirtyAttributes) === 1 && in_array('status', $dirtyAttributes)) {
                    return; // Continue with save
                }
                
                // If status is changing along with other fields, allow only status
                if (in_array('status', $dirtyAttributes)) {
                    // Reset all non-status changes
                    foreach ($dirtyAttributes as $attr) {
                        if ($attr !== 'status') {
                            $invoice->$attr = $invoice->getOriginal($attr);
                        }
                    }
                    return; // Continue with save (status only)
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
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'billing_period_start' => 'date',
            'billing_period_end' => 'date',
            'due_date' => 'date',
            'total_amount' => 'decimal:2',
            'status' => InvoiceStatus::class,
            'finalized_at' => 'datetime',
            'paid_at' => 'datetime',
            'paid_amount' => 'decimal:2',
            'overdue_notified_at' => 'datetime',
        ];
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
}
