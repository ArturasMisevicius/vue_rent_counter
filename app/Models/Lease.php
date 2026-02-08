<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Lease Model - Represents rental agreements between tenants and properties
 * 
 * @property int $id
 * @property int $property_id
 * @property int $tenant_id (renter record ID)
 * @property int $tenant_id (organization scope)
 * @property \Carbon\Carbon $start_date
 * @property \Carbon\Carbon $end_date
 * @property float $monthly_rent
 * @property float $deposit
 * @property bool $is_active
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * 
 * @property-read Property $property
 * @property-read Tenant $tenant
 */
class Lease extends Model
{
    use HasFactory, BelongsToTenant;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'property_id',
        'renter_id',
        'start_date',
        'end_date',
        'monthly_rent',
        'deposit',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'monthly_rent' => 'decimal:2',
            'deposit' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the property for this lease.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Get the tenant (renter) for this lease.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'renter_id');
    }

    /**
     * Check if the lease is currently active.
     */
    public function isCurrentlyActive(): bool
    {
        return $this->is_active && 
               $this->start_date <= now() && 
               $this->end_date >= now();
    }

    /**
     * Scope: Filter only active leases.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Filter current leases (within date range).
     */
    public function scopeCurrent($query)
    {
        return $query->where('start_date', '<=', now())
                    ->where('end_date', '>=', now());
    }
}