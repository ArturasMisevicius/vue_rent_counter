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

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        // Prevent modification of finalized invoices
        static::updating(function ($invoice) {
            $originalStatus = $invoice->getOriginal('status');
            
            // If the original status was FINALIZED
            if ($originalStatus === InvoiceStatus::FINALIZED->value || $originalStatus === InvoiceStatus::FINALIZED) {
                // Allow only status changes (e.g., from FINALIZED to PAID)
                $dirtyAttributes = array_keys($invoice->getDirty());
                if (count($dirtyAttributes) === 1 && in_array('status', $dirtyAttributes)) {
                    return true;
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
        'billing_period_start',
        'billing_period_end',
        'total_amount',
        'status',
        'finalized_at',
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
            'total_amount' => 'decimal:2',
            'status' => InvoiceStatus::class,
            'finalized_at' => 'datetime',
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
        return $query->where('billing_period_start', '>=', $startDate)
            ->where('billing_period_end', '<=', $endDate);
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
