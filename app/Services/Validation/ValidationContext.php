<?php

declare(strict_types=1);

namespace App\Services\Validation;

use App\Models\MeterReading;
use App\Models\ServiceConfiguration;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Validation context containing all data needed for validation operations.
 * 
 * This value object encapsulates the validation context to reduce parameter
 * passing and improve testability.
 */
final readonly class ValidationContext
{
    public function __construct(
        public MeterReading $reading,
        public ?ServiceConfiguration $serviceConfiguration = null,
        public ?array $validationConfig = null,
        public ?array $seasonalConfig = null,
        public ?MeterReading $previousReading = null,
        public ?Collection $historicalReadings = null,
    ) {
    }

    public function hasServiceConfiguration(): bool
    {
        return $this->serviceConfiguration !== null;
    }

    public function hasValidationConfig(): bool
    {
        return $this->validationConfig !== null;
    }

    public function hasSeasonalConfig(): bool
    {
        return $this->seasonalConfig !== null;
    }

    public function hasPreviousReading(): bool
    {
        return $this->previousReading !== null;
    }

    public function hasHistoricalReadings(): bool
    {
        return $this->historicalReadings !== null && $this->historicalReadings->isNotEmpty();
    }

    public function getConsumption(): ?float
    {
        return $this->reading->getConsumption();
    }

    public function getReadingDate(): Carbon
    {
        return $this->reading->reading_date;
    }

    public function getUtilityType(): ?string
    {
        return $this->serviceConfiguration?->utilityService?->service_type_bridge?->value;
    }

    public function getUnit(): string
    {
        return $this->serviceConfiguration?->utilityService?->unit_of_measurement ?? 'units';
    }
}