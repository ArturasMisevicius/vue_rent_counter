<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DistributionMethod;
use App\Enums\PricingModel;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

/**
 * Property-specific utility service configuration.
 * Links properties to utility services with individual pricing and distribution settings.
 */
class ServiceConfiguration extends Model
{
    use HasFactory, BelongsToTenant;

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
    public function scopeEffectiveOn($query, Carbon $date = null)
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
    public function isEffectiveOn(Carbon $date = null): bool
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
    public function getEffectiveRate(Carbon $dateTime = null, ?string $zone = null): ?float
    {
        $dateTime = $dateTime ?? now();
        $schedule = $this->rate_schedule ?? [];

        // Handle different pricing models
        return match ($this->pricing_model) {
            PricingModel::FIXED_MONTHLY => $schedule['monthly_rate'] ?? null,
            PricingModel::CONSUMPTION_BASED => $schedule['rate_per_unit'] ?? null,
            PricingModel::TIME_OF_USE => $this->getTimeOfUseRate($dateTime, $zone, $schedule),
            PricingModel::TIERED_RATES => $schedule['base_rate'] ?? null, // Base rate, tiers handled separately
            PricingModel::HYBRID => $schedule['base_rate'] ?? null,
            PricingModel::FLAT => $schedule['rate'] ?? null, // Legacy compatibility
            default => null,
        };
    }

    /**
     * Get time-of-use rate for a specific date/time and zone.
     */
    protected function getTimeOfUseRate(Carbon $dateTime, ?string $zone, array $schedule): ?float
    {
        $timeSlots = $schedule['time_slots'] ?? [];
        $hour = $dateTime->hour;
        $dayType = $dateTime->isWeekend() ? 'weekend' : 'weekday';

        foreach ($timeSlots as $slot) {
            if (
                $slot['day_type'] === $dayType &&
                $hour >= $slot['start_hour'] &&
                $hour < $slot['end_hour'] &&
                ($zone === null || $slot['zone'] === $zone)
            ) {
                return $slot['rate'];
            }
        }

        return $schedule['default_rate'] ?? null;
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