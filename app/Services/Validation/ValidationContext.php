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
 * 
 * PERFORMANCE OPTIMIZATIONS:
 * - Memoized expensive computations
 * - Lazy loading of derived values
 * - Cached property access
 */
final class ValidationContext
{
    private array $memoizedValues = [];

    public function __construct(
        public readonly MeterReading $reading,
        public readonly ?ServiceConfiguration $serviceConfiguration = null,
        public readonly ?array $validationConfig = null,
        public readonly ?array $seasonalConfig = null,
        public readonly ?MeterReading $previousReading = null,
        public readonly ?Collection $historicalReadings = null,
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

    /**
     * OPTIMIZED: Get consumption with memoization and preloaded previous reading.
     */
    public function getConsumption(): ?float
    {
        return $this->memoize('consumption', function () {
            return $this->reading->getConsumption($this->previousReading);
        });
    }

    /**
     * OPTIMIZED: Get reading date with memoization.
     */
    public function getReadingDate(): Carbon
    {
        return $this->memoize('reading_date', function () {
            return $this->reading->reading_date;
        });
    }

    /**
     * OPTIMIZED: Get utility type with memoization and null coalescing.
     */
    public function getUtilityType(): ?string
    {
        return $this->memoize('utility_type', function () {
            return $this->serviceConfiguration?->utilityService?->service_type_bridge?->value;
        });
    }

    /**
     * OPTIMIZED: Get unit with memoization and fallback.
     */
    public function getUnit(): string
    {
        return $this->memoize('unit', function () {
            return $this->serviceConfiguration?->utilityService?->unit_of_measurement ?? 'units';
        });
    }

    /**
     * OPTIMIZED: Get historical average consumption with caching.
     */
    public function getHistoricalAverageConsumption(): ?float
    {
        return $this->memoize('historical_average', function () {
            if (!$this->hasHistoricalReadings()) {
                return null;
            }

            $consumptions = $this->historicalReadings
                ->map(fn($reading) => $reading->getConsumption())
                ->filter()
                ->values();

            return $consumptions->isEmpty() ? null : $consumptions->avg();
        });
    }

    /**
     * OPTIMIZED: Get seasonal period with caching.
     */
    public function getSeasonalPeriod(): string
    {
        return $this->memoize('seasonal_period', function () {
            $month = $this->getReadingDate()->month;
            
            // Northern hemisphere seasons (adjust for location if needed)
            return match (true) {
                $month >= 12 || $month <= 2 => 'winter',
                $month >= 3 && $month <= 5 => 'spring',
                $month >= 6 && $month <= 8 => 'summer',
                default => 'autumn',
            };
        });
    }

    /**
     * OPTIMIZED: Check if reading is in heating season.
     */
    public function isHeatingSeason(): bool
    {
        return $this->memoize('is_heating_season', function () {
            $period = $this->getSeasonalPeriod();
            return in_array($period, ['winter', 'autumn'], true);
        });
    }

    /**
     * OPTIMIZED: Get consumption variance from historical average.
     */
    public function getConsumptionVariance(): ?float
    {
        return $this->memoize('consumption_variance', function () {
            $current = $this->getConsumption();
            $average = $this->getHistoricalAverageConsumption();
            
            if ($current === null || $average === null || $average == 0) {
                return null;
            }
            
            return abs($current - $average) / $average;
        });
    }

    /**
     * Memoization helper to cache expensive computations.
     */
    private function memoize(string $key, callable $callback): mixed
    {
        if (!isset($this->memoizedValues[$key])) {
            $this->memoizedValues[$key] = $callback();
        }
        
        return $this->memoizedValues[$key];
    }
}