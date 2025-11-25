<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * PropertyTenantPivot
 * 
 * Custom pivot model for property-tenant assignments with historical tracking.
 * Enables querying assignment history, calculating tenure, and tracking vacancies.
 * 
 * @property int $id
 * @property int $property_id
 * @property int $tenant_id
 * @property Carbon|null $assigned_at
 * @property Carbon|null $vacated_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Property $property
 * @property-read Tenant $tenant
 * @property-read bool $is_current
 * @property-read int|null $tenure_days
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
    ];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'property_id',
        'tenant_id',
        'assigned_at',
        'vacated_at',
    ];

    /**
     * Get the property for this assignment.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Get the tenant for this assignment.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Check if this is the current assignment (not vacated).
     */
    public function getIsCurrentAttribute(): bool
    {
        return $this->vacated_at === null;
    }

    /**
     * Calculate tenure in days.
     */
    public function getTenureDaysAttribute(): ?int
    {
        if (!$this->assigned_at) {
            return null;
        }

        $endDate = $this->vacated_at ?? now();
        return $this->assigned_at->diffInDays($endDate);
    }

    /**
     * Scope to get only current assignments.
     */
    public function scopeCurrent($query)
    {
        return $query->whereNull('vacated_at');
    }

    /**
     * Scope to get historical assignments.
     */
    public function scopeHistorical($query)
    {
        return $query->whereNotNull('vacated_at');
    }

    /**
     * Scope to get assignments within a date range.
     */
    public function scopeActiveDuring($query, Carbon $start, Carbon $end)
    {
        return $query->where(function ($q) use ($start, $end) {
            $q->where('assigned_at', '<=', $end)
              ->where(function ($q2) use ($start) {
                  $q2->whereNull('vacated_at')
                     ->orWhere('vacated_at', '>=', $start);
              });
        });
    }
}
