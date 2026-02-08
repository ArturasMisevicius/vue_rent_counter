<?php

namespace App\Models;

use App\Enums\MeterType;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Meter extends Model
{
    use HasFactory, BelongsToTenant;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'serial_number',
        'type',
        'property_id',
        'installation_date',
        'supports_zones',
        'reading_structure',
        'service_configuration_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => MeterType::class,
            'installation_date' => 'date',
            'supports_zones' => 'boolean',
            'reading_structure' => 'array',
        ];
    }

    /**
     * Get the property this meter belongs to.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Get the readings for this meter.
     */
    public function readings(): HasMany
    {
        return $this->hasMany(MeterReading::class);
    }

    /**
     * Get the service configuration for this meter.
     */
    public function serviceConfiguration(): BelongsTo
    {
        return $this->belongsTo(ServiceConfiguration::class);
    }

    /**
     * Scope a query to meters of a specific type.
     */
    public function scopeOfType($query, MeterType $type): void
    {
        $query->where('type', $type);
    }

    /**
     * Scope a query to meters that support zones.
     */
    public function scopeSupportsZones($query): void
    {
        $query->where('supports_zones', true);
    }

    /**
     * Scope a query to meters with their latest reading.
     */
    public function scopeWithLatestReading($query): void
    {
        $query->with(['readings' => function ($q) {
            $q->latest('reading_date')->limit(1);
        }]);
    }

    /**
     * Scope a query to meters with service configurations.
     */
    public function scopeWithServiceConfiguration($query): void
    {
        $query->with('serviceConfiguration.utilityService');
    }

    /**
     * Scope a query to meters linked to universal services.
     */
    public function scopeUniversalServices($query): void
    {
        $query->whereNotNull('service_configuration_id');
    }

    /**
     * Scope a query to legacy meters (not linked to universal services).
     */
    public function scopeLegacyMeters($query): void
    {
        $query->whereNull('service_configuration_id');
    }

    /**
     * Check if this meter supports multi-value readings.
     */
    public function supportsMultiValueReadings(): bool
    {
        return !empty($this->reading_structure);
    }

    /**
     * Get the expected reading structure for this meter.
     */
    public function getReadingStructure(): array
    {
        return $this->reading_structure ?? [];
    }

    /**
     * Validate a reading value against the meter's structure.
     */
    public function validateReadingStructure(array $readingValues): array
    {
        $structure = $this->getReadingStructure();
        $errors = [];

        if (empty($structure)) {
            // Legacy meter - only validate single value
            if (count($readingValues) > 1) {
                $errors[] = 'Legacy meter only supports single reading value';
            }
            return $errors;
        }

        // Validate against defined structure
        foreach ($structure['fields'] ?? [] as $field) {
            $fieldName = $field['name'];
            $isRequired = $field['required'] ?? false;
            $dataType = $field['type'] ?? 'number';

            if ($isRequired && !isset($readingValues[$fieldName])) {
                $errors[] = "Required field '{$fieldName}' is missing";
                continue;
            }

            if (isset($readingValues[$fieldName])) {
                $value = $readingValues[$fieldName];
                
                // Type validation
                if ($dataType === 'number' && !is_numeric($value)) {
                    $errors[] = "Field '{$fieldName}' must be numeric";
                }
                
                // Range validation
                if (isset($field['min']) && $value < $field['min']) {
                    $errors[] = "Field '{$fieldName}' must be at least {$field['min']}";
                }
                
                if (isset($field['max']) && $value > $field['max']) {
                    $errors[] = "Field '{$fieldName}' must not exceed {$field['max']}";
                }
            }
        }

        return $errors;
    }

    /**
     * Check if this meter is linked to a universal service.
     */
    public function isUniversalService(): bool
    {
        return !is_null($this->service_configuration_id);
    }

    /**
     * Get the utility service for this meter (if linked).
     */
    public function getUtilityService(): ?UtilityService
    {
        return $this->serviceConfiguration?->utilityService;
    }

    public function getServiceDisplayName(): string
    {
        $name = $this->serviceConfiguration?->utilityService?->name;

        if (is_string($name) && $name !== '') {
            return $name;
        }

        $meterType = $this->type instanceof MeterType
            ? $this->type
            : MeterType::tryFrom((string) $this->type);

        return $meterType?->label() ?? ucfirst((string) $this->type);
    }

    public function getUnitOfMeasurement(): string
    {
        $unit = $this->serviceConfiguration?->utilityService?->unit_of_measurement;

        if (is_string($unit) && $unit !== '') {
            return $unit;
        }

        $meterType = $this->type instanceof MeterType
            ? $this->type
            : MeterType::tryFrom((string) $this->type);

        return match ($meterType) {
            MeterType::ELECTRICITY, MeterType::HEATING => 'kWh',
            MeterType::WATER_COLD, MeterType::WATER_HOT => 'm³',
            default => 'unit',
        };
    }
}
