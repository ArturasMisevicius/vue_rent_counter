<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DistributionMethod;
use App\Enums\PricingModel;
use App\Traits\BelongsToTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Property-specific utility service configuration.
 * Links properties to utility services with individual pricing and distribution settings.
 */
class ServiceConfiguration extends Model
{
    use BelongsToTenant, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'property_id',
        'utility_service_id',
        'pricing_model',
        'rate_schedule',
        'distribution_method',
        'is_shared_service',
        'effective_from',
        'effective_until',
        'configuration_overrides',
        'tariff_id',
        'provider_id',
        'area_type',
        'custom_formula',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pricing_model' => PricingModel::class,
            'rate_schedule' => 'array',
            'distribution_method' => DistributionMethod::class,
            'is_shared_service' => 'boolean',
            'effective_from' => 'datetime',
            'effective_until' => 'datetime',
            'configuration_overrides' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the property this configuration belongs to.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Get the utility service this configuration is for.
     */
    public function utilityService(): BelongsTo
    {
        return $this->belongsTo(UtilityService::class);
    }

    /**
     * Get the tariff for rate data.
     */
    public function tariff(): BelongsTo
    {
        return $this->belongsTo(Tariff::class);
    }

    /**
     * Get the provider for this service.
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    /**
     * Get the meters linked to this service configuration.
     */
    public function meters(): HasMany
    {
        return $this->hasMany(Meter::class, 'service_configuration_id');
    }

    /**
     * Scope a query to active configurations.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to configurations effective on a given date.
     */
    public function scopeEffectiveOn($query, ?Carbon $date = null)
    {
        $date = $date ?? now();

        return $query->where('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_until')
                    ->orWhere('effective_until', '>=', $date);
            });
    }

    /**
     * Scope a query to shared service configurations.
     */
    public function scopeSharedServices($query)
    {
        return $query->where('is_shared_service', true);
    }

    /**
     * Scope a query to individual service configurations.
     */
    public function scopeIndividualServices($query)
    {
        return $query->where('is_shared_service', false);
    }

    /**
     * Scope a query by pricing model.
     */
    public function scopeByPricingModel($query, PricingModel $pricingModel)
    {
        return $query->where('pricing_model', $pricingModel);
    }

    /**
     * Scope a query by distribution method.
     */
    public function scopeByDistributionMethod($query, DistributionMethod $distributionMethod)
    {
        return $query->where('distribution_method', $distributionMethod);
    }

    /**
     * Check if this configuration is currently effective.
     */
    public function isEffectiveOn(?Carbon $date = null): bool
    {
        $date = $date ?? now();

        return $this->effective_from <= $date
            && (is_null($this->effective_until) || $this->effective_until >= $date);
    }

    /**
     * Check if this configuration requires area data.
     */
    public function requiresAreaData(): bool
    {
        return $this->distribution_method->requiresAreaData();
    }

    /**
     * Check if this configuration requires consumption data.
     */
    public function requiresConsumptionData(): bool
    {
        return $this->pricing_model->requiresConsumptionData()
            || $this->distribution_method->requiresConsumptionData();
    }

    /**
     * Get the effective rate schedule for a given date and time.
     */
    /**
     * Get the effective rate for a given date/time and zone.
     *
     * @param  Carbon|null  $dateTime  The date/time to get rate for (defaults to now)
     * @param  string|null  $zone  The zone (e.g., 'day', 'night') for time-of-use rates
     * @return float|null The effective rate or null if not found
     */
    public function getEffectiveRate(?Carbon $dateTime = null, ?string $zone = null): ?float
    {
        $dateTime = $dateTime ?? now();
        $schedule = $this->rate_schedule ?? [];

        // Handle different pricing models
        return match ($this->pricing_model) {
            PricingModel::FIXED_MONTHLY => $schedule['monthly_rate'] ?? null,
            PricingModel::CONSUMPTION_BASED => $schedule['unit_rate'] ?? $schedule['rate_per_unit'] ?? null,
            PricingModel::TIME_OF_USE => $this->getTimeOfUseRate($dateTime, $zone, $schedule),
            PricingModel::TIERED_RATES => $schedule['base_rate'] ?? null, // Base rate, tiers handled separately
            PricingModel::HYBRID => $schedule['unit_rate'] ?? $schedule['rate_per_unit'] ?? null,
            PricingModel::FLAT => $schedule['rate'] ?? null, // Legacy compatibility
            default => null,
        };
    }

    /**
     * Get time-of-use rate for a specific date/time and zone.
     */
    protected function getTimeOfUseRate(Carbon $dateTime, ?string $zone, array $schedule): ?float
    {
        $zoneRates = $schedule['zone_rates'] ?? [];

        if (! empty($zoneRates)) {
            $key = $zone ?? 'default';

            return $zoneRates[$key] ?? $zoneRates['default'] ?? null;
        }

        $timeWindows = $schedule['time_windows'] ?? [];

        if (is_array($timeWindows) && $timeWindows !== []) {
            $rate = $this->resolveRateFromTimeWindows($timeWindows, $dateTime, $zone);

            if ($rate !== null) {
                return $rate;
            }
        }

        $timeSlots = $schedule['time_slots'] ?? [];
        $hour = $dateTime->hour;
        $dayType = $this->resolveDayType($dateTime);

        foreach ($timeSlots as $slot) {
            if (! is_array($slot) || ! is_numeric($slot['rate'] ?? null)) {
                continue;
            }

            $slotDayType = $slot['day_type'] ?? 'all';
            if (! is_string($slotDayType) || ! in_array($slotDayType, ['weekday', 'weekend', 'all'], true)) {
                continue;
            }

            $startHour = $slot['start_hour'] ?? null;
            $endHour = $slot['end_hour'] ?? null;

            if (! is_numeric($startHour) || ! is_numeric($endHour)) {
                continue;
            }

            $startHourValue = (int) $startHour;
            $endHourValue = (int) $endHour;

            if ($startHourValue < 0 || $startHourValue > 23 || $endHourValue < 0 || $endHourValue > 23) {
                continue;
            }

            if (
                ($slotDayType === 'all' || $slotDayType === $dayType) &&
                $this->matchesHourWindow($hour, $startHourValue, $endHourValue) &&
                ($zone === null || (($slot['zone'] ?? null) === $zone))
            ) {
                return (float) $slot['rate'];
            }
        }

        return isset($schedule['default_rate']) && is_numeric($schedule['default_rate'])
            ? (float) $schedule['default_rate']
            : null;
    }

    /**
     * @param  array<int, mixed>  $timeWindows
     */
    protected function resolveRateFromTimeWindows(array $timeWindows, Carbon $dateTime, ?string $zone): ?float
    {
        $targetZone = $zone ?? 'default';
        $dayType = $this->resolveDayType($dateTime);
        $month = (int) $dateTime->month;
        $minuteOfDay = ((int) $dateTime->hour * 60) + (int) $dateTime->minute;

        foreach ($timeWindows as $window) {
            if (! is_array($window)) {
                continue;
            }

            if (($window['zone'] ?? null) !== $targetZone) {
                continue;
            }

            if (! $this->windowMatchesContext($window, $dayType, $month, $minuteOfDay)) {
                continue;
            }

            if (! is_numeric($window['rate'] ?? null)) {
                continue;
            }

            return (float) $window['rate'];
        }

        return null;
    }

    protected function windowMatchesContext(array $window, string $dayType, int $month, int $minuteOfDay): bool
    {
        $start = $window['start'] ?? null;
        $end = $window['end'] ?? null;

        if (! is_string($start) || ! is_string($end) || ! $this->isValidTimeString($start) || ! $this->isValidTimeString($end)) {
            return false;
        }

        $allowedDayTypes = $window['day_types'] ?? ['weekday', 'weekend'];
        $allowedMonths = $window['months'] ?? range(1, 12);

        if (is_string($allowedDayTypes)) {
            $allowedDayTypes = [$allowedDayTypes];
        }

        if (! is_array($allowedDayTypes) || ! is_array($allowedMonths)) {
            return false;
        }

        $normalizedDayTypes = [];
        foreach ($allowedDayTypes as $value) {
            if (! is_string($value)) {
                continue;
            }

            if ($value === 'all') {
                $normalizedDayTypes[] = 'weekday';
                $normalizedDayTypes[] = 'weekend';

                continue;
            }

            $normalizedDayTypes[] = $value;
        }

        if (! in_array($dayType, $normalizedDayTypes, true)) {
            return false;
        }

        $normalizedMonths = [];
        foreach ($allowedMonths as $value) {
            if (! is_numeric($value)) {
                continue;
            }

            $normalizedMonths[] = (int) $value;
        }

        if (! in_array($month, $normalizedMonths, true)) {
            return false;
        }

        $startMinutes = $this->timeToMinutes($start);
        $endMinutes = $this->timeToMinutes($end);

        if ($startMinutes === $endMinutes) {
            return false;
        }

        if ($startMinutes < $endMinutes) {
            return $minuteOfDay >= $startMinutes && $minuteOfDay < $endMinutes;
        }

        return $minuteOfDay >= $startMinutes || $minuteOfDay < $endMinutes;
    }

    protected function matchesHourWindow(int $hour, int $startHour, int $endHour): bool
    {
        if ($startHour === $endHour) {
            return false;
        }

        if ($startHour < $endHour) {
            return $hour >= $startHour && $hour < $endHour;
        }

        return $hour >= $startHour || $hour < $endHour;
    }

    protected function resolveDayType(Carbon $dateTime): string
    {
        return $dateTime->isWeekend() ? 'weekend' : 'weekday';
    }

    protected function isValidTimeString(string $value): bool
    {
        return (bool) preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/', $value);
    }

    protected function timeToMinutes(string $value): int
    {
        [$hour, $minute] = explode(':', $value);

        return ((int) $hour * 60) + (int) $minute;
    }

    /**
     * Calculate tiered rate for a given consumption amount.
     */
    public function calculateTieredRate(float $consumption): float
    {
        if ($this->pricing_model !== PricingModel::TIERED_RATES) {
            throw new \InvalidArgumentException('Configuration does not use tiered rates');
        }

        $tiers = $this->rate_schedule['tiers'] ?? [];
        $totalCost = 0;
        $remainingConsumption = $consumption;

        foreach ($tiers as $tier) {
            $tierLimit = $tier['limit'] ?? PHP_FLOAT_MAX;
            $tierRate = $tier['rate'] ?? 0;

            if ($remainingConsumption <= 0) {
                break;
            }

            $tierConsumption = min($remainingConsumption, $tierLimit);
            $totalCost += $tierConsumption * $tierRate;
            $remainingConsumption -= $tierConsumption;
        }

        return $totalCost;
    }

    /**
     * Get merged configuration including overrides.
     */
    public function getMergedConfiguration(): array
    {
        $baseConfig = $this->utilityService->configuration_schema ?? [];
        $overrides = $this->configuration_overrides ?? [];

        return array_merge($baseConfig, $overrides);
    }

    /**
     * Validate configuration against utility service schema.
     */
    public function validateConfiguration(): array
    {
        return $this->utilityService->validateConfiguration(
            $this->getMergedConfiguration()
        );
    }

    /**
     * Create a configuration snapshot for billing immutability.
     */
    public function createSnapshot(): array
    {
        return [
            'id' => $this->id,
            'utility_service' => $this->utilityService->toArray(),
            'pricing_model' => $this->pricing_model->value,
            'rate_schedule' => $this->rate_schedule,
            'distribution_method' => $this->distribution_method->value,
            'configuration_overrides' => $this->configuration_overrides,
            'tariff_snapshot' => $this->tariff?->toArray(),
            'provider_snapshot' => $this->provider?->toArray(),
            'snapshot_date' => now()->toISOString(),
        ];
    }
}
