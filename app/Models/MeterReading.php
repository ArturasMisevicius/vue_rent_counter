<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MeterReading extends Model
{
    use HasFactory, BelongsToTenant;

    /**
     * Temporary attribute to store change reason for audit trail.
     * This is not stored in the database but used by the observer.
     *
     * @var string|null
     */
    public ?string $change_reason = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'meter_id',
        'reading_date',
        'value',
        'zone',
        'entered_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reading_date' => 'datetime',
            'value' => 'decimal:2',
        ];
    }

    /**
     * Get the meter this reading belongs to.
     */
    public function meter(): BelongsTo
    {
        return $this->belongsTo(Meter::class);
    }

    /**
     * Get the user who entered this reading.
     */
    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entered_by');
    }

    /**
     * Get the audit trail for this reading.
     */
    public function auditTrail(): HasMany
    {
        return $this->hasMany(MeterReadingAudit::class);
    }

    /**
     * Get the consumption since the previous reading.
     */
    public function getConsumption(): ?float
    {
        $service = app(\App\Services\MeterReadingService::class);
        $previous = $service->getPreviousReading($this->meter, $this->zone, $this->reading_date->toDateString());

        return $previous ? $this->value - $previous->value : null;
    }

    /**
     * Scope a query to readings within a date range.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $startDate
     * @param string $endDate
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForPeriod($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('reading_date', [$startDate, $endDate]);
    }

    /**
     * Scope a query to readings for a specific zone.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|null $zone
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForZone($query, ?string $zone)
    {
        return $zone ? $query->where('zone', $zone) : $query->whereNull('zone');
    }

    /**
     * Scope a query to readings ordered by date descending.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('reading_date', 'desc');
    }
}
