<?php

declare(strict_types=1);

namespace App\Services\Validation;

use App\Models\MeterReading;
use App\Models\ServiceConfiguration;
use Illuminate\Support\Collection;

/**
 * Immutable validation context value object.
 * 
 * Contains all data needed for validation operations in a thread-safe,
 * immutable structure. Prevents side effects and enables safe concurrent validation.
 */
final readonly class ValidationContext
{
    public function __construct(
        public MeterReading $reading,
        public ?ServiceConfiguration $serviceConfiguration,
        public array $validationConfig,
        public array $seasonalConfig,
        public ?MeterReading $previousReading = null,
        public ?Collection $historicalReadings = null,
    ) {}

    /**
     * Get the effective consumption for this reading.
     */
    public function getConsumption(): ?float
    {
        return $this->reading->getConsumption($this->previousReading);
    }

    /**
     * Get the utility service for this reading.
     */
    public function getUtilityService(): ?\App\Models\UtilityService
    {
        return $this->serviceConfiguration?->utilityService;
    }

    /**
     * Get the meter for this reading.
     */
    public function getMeter(): \App\Models\Meter
    {
        return $this->reading->meter;
    }

    /**
     * Check if this reading has historical data for pattern analysis.
     */
    public function hasHistoricalData(): bool
    {
        return $this->historicalReadings && $this->historicalReadings->isNotEmpty();
    }

    /**
     * Get historical consumption average.
     */
    public function getHistoricalAverage(): ?float
    {
        if (!$this->hasHistoricalData()) {
            return null;
        }

        $consumptions = $this->historicalReadings
            ->map(fn($reading) => $reading->getConsumption())
            ->filter(fn($consumption) => $consumption !== null);

        return $consumptions->isNotEmpty() ? $consumptions->avg() : null;
    }

    /**
     * Get seasonal configuration for the utility service.
     */
    public function getSeasonalConfig(): array
    {
        $serviceType = $this->getUtilityService()?->service_type_bridge?->value ?? 'default';
        return $this->seasonalConfig[$serviceType] ?? $this->seasonalConfig['default'] ?? [];
    }

    /**
     * Check if the reading date is in summer period.
     */
    public function isSummerPeriod(): bool
    {
        $month = $this->reading->reading_date->month;
        return $month >= 5 && $month <= 9; // May to September
    }

    /**
     * Check if the reading date is in winter period.
     */
    public function isWinterPeriod(): bool
    {
        $month = $this->reading->reading_date->month;
        return $month <= 3 || $month >= 11; // November to March
    }

    /**
     * Get validation configuration value with fallback.
     */
    public function getValidationConfig(string $key, mixed $default = null): mixed
    {
        return data_get($this->validationConfig, $key, $default);
    }

    /**
     * Create a new context with updated reading.
     */
    public function withReading(MeterReading $reading): self
    {
        return new self(
            reading: $reading,
            serviceConfiguration: $this->serviceConfiguration,
            validationConfig: $this->validationConfig,
            seasonalConfig: $this->seasonalConfig,
            previousReading: $this->previousReading,
            historicalReadings: $this->historicalReadings,
        );
    }

    /**
     * Create a new context with updated service configuration.
     */
    public function withServiceConfiguration(?ServiceConfiguration $serviceConfiguration): self
    {
        return new self(
            reading: $this->reading,
            serviceConfiguration: $serviceConfiguration,
            validationConfig: $this->validationConfig,
            seasonalConfig: $this->seasonalConfig,
            previousReading: $this->previousReading,
            historicalReadings: $this->historicalReadings,
        );
    }
}