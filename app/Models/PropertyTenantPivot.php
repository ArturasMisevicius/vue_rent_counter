<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * PropertyTenantPivot - Custom pivot model for property-tenant relationship
 * 
 * Extends the basic pivot with additional data and business logic
 * 
 * @property int $id
 * @property int $property_id
 * @property int $tenant_id
 * @property \Carbon\Carbon $assigned_at
 * @property \Carbon\Carbon|null $vacated_at
 * @property float|null $monthly_rent
 * @property float|null $deposit_amount
 * @property string $lease_type
 * @property string|null $notes
 * @property int|null $assigned_by
 */
class PropertyTenantPivot extends Pivot
{
    /**
     * The table associated with the pivot model.
     */
    protected $table = 'property_tenant';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = true;

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'assigned_at' => 'datetime',
        'vacated_at' => 'datetime',
        'monthly_rent' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
    ];

    /**
     * Get the property
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Get the tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the user who assigned the tenant
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Check if the assignment is currently active
     */
    public function isActive(): bool
    {
        return $this->vacated_at === null;
    }

    /**
     * Check if the assignment has ended
     */
    public function hasEnded(): bool
    {
        return $this->vacated_at !== null;
    }

    /**
     * Get the duration of the assignment in days
     */
    public function getDurationInDays(): int
    {
        $endDate = $this->vacated_at ?? now();
        return $this->assigned_at->diffInDays($endDate);
    }

    /**
     * Get the duration of the assignment in months
     */
    public function getDurationInMonths(): int
    {
        $endDate = $this->vacated_at ?? now();
        return $this->assigned_at->diffInMonths($endDate);
    }

    /**
     * Calculate total rent paid (estimated)
     */
    public function getTotalRentPaid(): float
    {
        if (!$this->monthly_rent) {
            return 0.0;
        }

        $months = $this->getDurationInMonths();
        return $this->monthly_rent * $months;
    }

    /**
     * Mark the assignment as ended
     */
    public function markAsVacated(): void
    {
        $this->vacated_at = now();
        $this->save();
    }

    /**
     * Scope: Only active assignments
     */
    public function scopeActive($query)
    {
        return $query->whereNull('vacated_at');
    }

    /**
     * Scope: Only ended assignments
     */
    public function scopeEnded($query)
    {
        return $query->whereNotNull('vacated_at');
    }

    /**
     * Scope: Assignments for a specific lease type
     */
    public function scopeOfLeaseType($query, string $leaseType)
    {
        return $query->where('lease_type', $leaseType);
    }
}
