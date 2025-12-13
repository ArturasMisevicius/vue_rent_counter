<?php

namespace App\Models;

use App\Enums\InputMethod;
use App\Enums\ValidationStatus;
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
        'reading_values',
        'input_method',
        'validation_status',
        'photo_path',
        'validated_by',
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
            'reading_values' => 'array',
            'input_method' => InputMethod::class,
            'validation_status' => ValidationStatus::class,
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
     * Get the user who validated this reading.
     */
    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
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
     * 
     * PERFORMANCE OPTIMIZATION: Accepts optional previous reading to avoid N+1 queries.
     * When used in batch operations, pass the preloaded previous reading.
     */
    public function getConsumption(?MeterReading $previousReading = null): ?float
    {
        // Use provided previous reading if available (batch optimization)
        if ($previousReading !== null) {
            return $this->getEffectiveValue() - $previousReading->getEffectiveValue();
        }

        // Fallback to service lookup for individual calls
        $service = app(\App\Services\MeterReadingService::class);
        $previous = $service->getPreviousReading($this->meter, $this->zone, $this->reading_date->toDateString());

        return $previous ? $this->getEffectiveValue() - $previous->getEffectiveValue() : null;
    }

    /**
     * OPTIMIZED: Get consumption with caching for repeated calls.
     * Useful when the same reading consumption is accessed multiple times.
     */
    public function getCachedConsumption(?MeterReading $previousReading = null): ?float
    {
        // Use model attribute caching to avoid recalculation
        $cacheKey = 'consumption_' . ($previousReading?->id ?? 'auto');
        
        if (!isset($this->attributes[$cacheKey])) {
            $this->attributes[$cacheKey] = $this->getConsumption($previousReading);
        }
        
        return $this->attributes[$cacheKey];
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

    /**
     * Scope a query to readings by input method.
     */
    public function scopeByInputMethod($query, InputMethod $inputMethod)
    {
        return $query->where('input_method', $inputMethod);
    }

    /**
     * Scope a query to readings by validation status.
     */
    public function scopeByValidationStatus($query, ValidationStatus $status)
    {
        return $query->where('validation_status', $status);
    }

    /**
     * Scope a query to validated readings.
     */
    public function scopeValidated($query)
    {
        return $query->where('validation_status', ValidationStatus::VALIDATED);
    }

    /**
     * Scope a query to pending validation readings.
     */
    public function scopePendingValidation($query)
    {
        return $query->whereIn('validation_status', [
            ValidationStatus::PENDING,
            ValidationStatus::REQUIRES_REVIEW,
        ]);
    }

    /**
     * Scope a query to automated readings.
     */
    public function scopeAutomated($query)
    {
        return $query->whereIn('input_method', [
            InputMethod::CSV_IMPORT,
            InputMethod::API_INTEGRATION,
            InputMethod::ESTIMATED,
        ]);
    }

    /**
     * Scope a query to manual readings.
     */
    public function scopeManual($query)
    {
        return $query->whereIn('input_method', [
            InputMethod::MANUAL,
            InputMethod::PHOTO_OCR,
        ]);
    }

    /**
     * Check if this reading uses multi-value structure.
     */
    public function isMultiValue(): bool
    {
        return !empty($this->reading_values);
    }

    /**
     * Get the effective reading value (backward compatibility).
     */
    public function getEffectiveValue(): float
    {
        // For backward compatibility, return the single value if available
        if (!is_null($this->value)) {
            return (float) $this->value;
        }

        // For multi-value readings, return the primary value or sum
        if ($this->isMultiValue()) {
            $values = $this->reading_values;
            
            // If there's a 'primary' or 'total' field, use that
            if (isset($values['primary'])) {
                return (float) $values['primary'];
            }
            
            if (isset($values['total'])) {
                return (float) $values['total'];
            }
            
            // Otherwise, sum all numeric values
            return array_sum(array_filter($values, 'is_numeric'));
        }

        return 0.0;
    }

    /**
     * Get a specific reading value by field name.
     */
    public function getReadingValue(string $fieldName): ?float
    {
        if (!$this->isMultiValue()) {
            return $fieldName === 'value' ? $this->getEffectiveValue() : null;
        }

        $values = $this->reading_values ?? [];
        return isset($values[$fieldName]) ? (float) $values[$fieldName] : null;
    }

    /**
     * Set reading values (handles both single and multi-value).
     */
    public function setReadingValues(array $values): void
    {
        // If meter supports multi-value readings, store in reading_values
        if ($this->meter->supportsMultiValueReadings()) {
            $this->reading_values = $values;
            
            // Also set the primary value for backward compatibility
            $this->value = $this->getEffectiveValue();
        } else {
            // Legacy meter - store single value
            $primaryValue = reset($values);
            $this->value = is_numeric($primaryValue) ? (float) $primaryValue : 0.0;
            $this->reading_values = null;
        }
    }

    /**
     * Validate the reading values against meter structure.
     */
    public function validateReadingValues(): array
    {
        if (!$this->isMultiValue()) {
            return []; // Legacy readings don't need structure validation
        }

        return $this->meter->validateReadingStructure($this->reading_values ?? []);
    }

    /**
     * Check if this reading requires validation.
     */
    public function requiresValidation(): bool
    {
        return $this->input_method->requiresValidation();
    }

    /**
     * Check if this reading is approved for billing.
     */
    public function isApprovedForBilling(): bool
    {
        return $this->validation_status->isApproved();
    }

    /**
     * Mark reading as validated.
     */
    public function markAsValidated(int $validatedByUserId): void
    {
        $this->validation_status = ValidationStatus::VALIDATED;
        $this->validated_by = $validatedByUserId;
        $this->save();
    }

    /**
     * Mark reading as rejected.
     */
    public function markAsRejected(int $validatedByUserId): void
    {
        $this->validation_status = ValidationStatus::REJECTED;
        $this->validated_by = $validatedByUserId;
        $this->save();
    }

    /**
     * Check if this reading has a photo attachment.
     */
    public function hasPhoto(): bool
    {
        return !empty($this->photo_path);
    }

    /**
     * Get the photo URL if available.
     */
    public function getPhotoUrl(): ?string
    {
        return $this->hasPhoto() ? asset('storage/' . $this->photo_path) : null;
    }
}
