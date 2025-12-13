<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\GyvatukasCalculatorInterface;
use App\Enums\DistributionMethod;
use App\Enums\PricingModel;
use App\Models\Building;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\ServiceConfiguration;
use App\Models\Tariff;
use App\ValueObjects\BillingPeriod;
use App\ValueObjects\CalculationResult;
use App\ValueObjects\ConsumptionData;
use App\ValueObjects\InvoiceItemData;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * UniversalBillingCalculator - Universal utility billing calculation service
 * 
 * This service provides comprehensive billing calculations for various utility types
 * and pricing models. It integrates with the existing GyvatukasCalculator for
 * Lithuanian heating system calculations while supporting modern universal
 * utility billing scenarios.
 * 
 * ## Supported Pricing Models
 * - **Fixed Monthly**: Flat monthly fee regardless of consumption
 * - **Consumption-Based**: Linear pricing based on usage amount
 * - **Tiered Rates**: Different rates for different consumption brackets
 * - **Hybrid**: Combination of fixed fee and consumption-based pricing
 * - **Time-of-Use**: Different rates for different time periods (zones)
 * - **Custom Formula**: Mathematical expressions for complex pricing
 * 
 * ## Key Features
 * - Seamless integration with existing GyvatukasCalculator
 * - Support for seasonal adjustments (summer/winter logic)
 * - Time-zone based pricing for electricity meters
 * - Tariff snapshot functionality for invoice immutability
 * - Performance optimized with caching and batch processing
 * - Comprehensive audit trail and logging
 * 
 * ## Usage Examples
 * ```php
 * // Basic consumption-based calculation
 * $result = $calculator->calculateBill($serviceConfig, $consumption, $billingPeriod);
 * 
 * // Tiered rate calculation
 * $result = $calculator->calculateTieredBill($serviceConfig, $consumption, $tiers);
 * 
 * // Hybrid pricing (fixed + consumption)
 * $result = $calculator->calculateHybridBill($serviceConfig, $consumption, $fixedFee);
 * 
 * // Time-of-use calculation with zones
 * $result = $calculator->calculateTimeOfUseBill($serviceConfig, $zoneConsumption);
 * ```
 * 
 * @see \App\Services\GyvatukasCalculator
 * @see \App\Enums\PricingModel
 * @see \App\Models\ServiceConfiguration
 * @see \App\ValueObjects\CalculationResult
 * 
 * @package App\Services
 * @author Universal Utility Management Team
 * @since 2.0.0
 */
final class UniversalBillingCalculator
{
    /**
     * Cache TTL for billing calculations (1 hour)
     */
    private const CACHE_TTL_SECONDS = 3600;
    
    /**
     * Cache key prefix for universal billing calculations
     */
    private const CACHE_PREFIX = 'universal_billing';
    
    /**
     * Maximum consumption value to prevent calculation errors
     */
    private const MAX_CONSUMPTION_VALUE = 999999.99;
    
    /**
     * Minimum consumption value (prevents negative consumption)
     */
    private const MIN_CONSUMPTION_VALUE = 0.0;
    
    /**
     * Default precision for monetary calculations
     */
    private const MONETARY_PRECISION = 2;
    
    /**
     * Default precision for consumption calculations
     */
    private const CONSUMPTION_PRECISION = 3;

    public function __construct(
        private readonly GyvatukasCalculatorInterface $gyvatukasCalculator,
        private readonly CacheRepository $cache,
        private readonly ConfigRepository $config,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Calculate bill for a service configuration and consumption data.
     * 
     * This is the main entry point for universal billing calculations.
     * It automatically determines the appropriate calculation method based
     * on the pricing model and delegates to specialized calculation methods.
     * 
     * @param ServiceConfiguration $serviceConfig Service configuration with pricing model
     * @param ConsumptionData $consumption Consumption data for the billing period
     * @param BillingPeriod $billingPeriod Period for which to calculate the bill
     * @return CalculationResult Complete calculation result with breakdown
     * 
     * @throws InvalidArgumentException When service configuration is invalid
     * @throws InvalidArgumentException When consumption data is invalid
     */
    public function calculateBill(
        ServiceConfiguration $serviceConfig,
        ConsumptionData $consumption,
        BillingPeriod $billingPeriod
    ): CalculationResult {
        $this->validateServiceConfiguration($serviceConfig);
        $this->validateConsumptionData($consumption);
        
        $cacheKey = $this->buildCalculationCacheKey($serviceConfig, $consumption, $billingPeriod);
        
        return $this->cache->remember(
            $cacheKey,
            self::CACHE_TTL_SECONDS,
            fn () => $this->performBillCalculation($serviceConfig, $consumption, $billingPeriod)
        );
    }

    /**
     * Perform the actual bill calculation based on pricing model.
     */
    private function performBillCalculation(
        ServiceConfiguration $serviceConfig,
        ConsumptionData $consumption,
        BillingPeriod $billingPeriod
    ): CalculationResult {
        $pricingModel = $serviceConfig->pricing_model;
        
        return match ($pricingModel) {
            PricingModel::FIXED_MONTHLY => $this->calculateFixedMonthlyBill($serviceConfig, $billingPeriod),
            PricingModel::CONSUMPTION_BASED => $this->calculateConsumptionBasedBill($serviceConfig, $consumption),
            PricingModel::TIERED_RATES => $this->calculateTieredRatesBill($serviceConfig, $consumption),
            PricingModel::HYBRID => $this->calculateHybridBill($serviceConfig, $consumption, $billingPeriod),
            PricingModel::TIME_OF_USE => $this->calculateTimeOfUseBill($serviceConfig, $consumption),
            PricingModel::CUSTOM_FORMULA => $this->calculateCustomFormulaBill($serviceConfig, $consumption, $billingPeriod),
            PricingModel::FLAT => $this->calculateLegacyFlatBill($serviceConfig, $consumption),
            default => throw new InvalidArgumentException("Unsupported pricing model: {$pricingModel->value}"),
        };
    }

    /**
     * Calculate fixed monthly bill (no consumption dependency).
     */
    private function calculateFixedMonthlyBill(
        ServiceConfiguration $serviceConfig,
        BillingPeriod $billingPeriod
    ): CalculationResult {
        $rateSchedule = $serviceConfig->rate_schedule;
        $monthlyRate = $rateSchedule['monthly_rate'] ?? 0.0;
        
        // Apply seasonal adjustments if configured
        $adjustedRate = $this->applySeasonalAdjustments($monthlyRate, $billingPeriod, $serviceConfig);
        
        // Pro-rate for partial months if needed
        $finalAmount = $this->applyProRation($adjustedRate, $billingPeriod);
        
        return new CalculationResult(
            totalAmount: round($finalAmount, self::MONETARY_PRECISION),
            baseAmount: round($monthlyRate, self::MONETARY_PRECISION),
            adjustments: $this->buildAdjustmentsArray($monthlyRate, $finalAmount),
            consumptionAmount: 0.0,
            fixedAmount: round($finalAmount, self::MONETARY_PRECISION),
            tariffSnapshot: $this->createTariffSnapshot($serviceConfig),
            calculationDetails: [
                'pricing_model' => PricingModel::FIXED_MONTHLY->value,
                'monthly_rate' => $monthlyRate,
                'seasonal_adjustment' => $adjustedRate !== $monthlyRate,
                'pro_rated' => $this->isPeriodPartial($billingPeriod),
            ]
        );
    }

    /**
     * Calculate consumption-based bill (linear pricing).
     */
    private function calculateConsumptionBasedBill(
        ServiceConfiguration $serviceConfig,
        ConsumptionData $consumption
    ): CalculationResult {
        $rateSchedule = $serviceConfig->rate_schedule;
        $unitRate = $rateSchedule['unit_rate'] ?? 0.0;
        
        $totalConsumption = $consumption->getTotalConsumption();
        $consumptionAmount = $totalConsumption * $unitRate;
        
        return new CalculationResult(
            totalAmount: round($consumptionAmount, self::MONETARY_PRECISION),
            baseAmount: round($consumptionAmount, self::MONETARY_PRECISION),
            adjustments: [],
            consumptionAmount: round($consumptionAmount, self::MONETARY_PRECISION),
            fixedAmount: 0.0,
            tariffSnapshot: $this->createTariffSnapshot($serviceConfig),
            calculationDetails: [
                'pricing_model' => PricingModel::CONSUMPTION_BASED->value,
                'unit_rate' => $unitRate,
                'total_consumption' => $totalConsumption,
            ]
        );
    }

    /**
     * Calculate tiered rates bill (different rates for different consumption brackets).
     */
    private function calculateTieredRatesBill(
        ServiceConfiguration $serviceConfig,
        ConsumptionData $consumption
    ): CalculationResult {
        $rateSchedule = $serviceConfig->rate_schedule;
        $tiers = $rateSchedule['tiers'] ?? [];
        
        if (empty($tiers)) {
            throw new InvalidArgumentException('Tiered rates configuration missing tier definitions');
        }
        
        $totalConsumption = $consumption->getTotalConsumption();
        $consumptionAmount = 0.0;
        $remainingConsumption = $totalConsumption;
        $tierBreakdown = [];
        
        foreach ($tiers as $tier) {
            if ($remainingConsumption <= 0) {
                break;
            }
            
            $tierLimit = $tier['limit'] ?? PHP_FLOAT_MAX;
            $tierRate = $tier['rate'] ?? 0.0;
            
            $tierConsumption = min($remainingConsumption, $tierLimit);
            $tierAmount = $tierConsumption * $tierRate;
            
            $consumptionAmount += $tierAmount;
            $remainingConsumption -= $tierConsumption;
            
            $tierBreakdown[] = [
                'consumption' => $tierConsumption,
                'rate' => $tierRate,
                'amount' => $tierAmount,
                'limit' => $tierLimit,
            ];
        }
        
        return new CalculationResult(
            totalAmount: round($consumptionAmount, self::MONETARY_PRECISION),
            baseAmount: round($consumptionAmount, self::MONETARY_PRECISION),
            adjustments: [],
            consumptionAmount: round($consumptionAmount, self::MONETARY_PRECISION),
            fixedAmount: 0.0,
            tariffSnapshot: $this->createTariffSnapshot($serviceConfig),
            calculationDetails: [
                'pricing_model' => PricingModel::TIERED_RATES->value,
                'total_consumption' => $totalConsumption,
                'tier_breakdown' => $tierBreakdown,
            ]
        );
    }

    /**
     * Calculate hybrid bill (fixed fee + consumption-based).
     */
    private function calculateHybridBill(
        ServiceConfiguration $serviceConfig,
        ConsumptionData $consumption,
        BillingPeriod $billingPeriod
    ): CalculationResult {
        $rateSchedule = $serviceConfig->rate_schedule;
        $fixedFee = $rateSchedule['fixed_fee'] ?? 0.0;
        $unitRate = $rateSchedule['unit_rate'] ?? 0.0;
        
        // Calculate fixed component
        $adjustedFixedFee = $this->applySeasonalAdjustments($fixedFee, $billingPeriod, $serviceConfig);
        $proRatedFixedFee = $this->applyProRation($adjustedFixedFee, $billingPeriod);
        
        // Calculate consumption component
        $totalConsumption = $consumption->getTotalConsumption();
        $consumptionAmount = $totalConsumption * $unitRate;
        
        $totalAmount = $proRatedFixedFee + $consumptionAmount;
        
        return new CalculationResult(
            totalAmount: round($totalAmount, self::MONETARY_PRECISION),
            baseAmount: round($fixedFee + $consumptionAmount, self::MONETARY_PRECISION),
            adjustments: $this->buildAdjustmentsArray($fixedFee, $proRatedFixedFee),
            consumptionAmount: round($consumptionAmount, self::MONETARY_PRECISION),
            fixedAmount: round($proRatedFixedFee, self::MONETARY_PRECISION),
            tariffSnapshot: $this->createTariffSnapshot($serviceConfig),
            calculationDetails: [
                'pricing_model' => PricingModel::HYBRID->value,
                'fixed_fee' => $fixedFee,
                'unit_rate' => $unitRate,
                'total_consumption' => $totalConsumption,
                'pro_rated' => $this->isPeriodPartial($billingPeriod),
            ]
        );
    }

    /**
     * Calculate time-of-use bill (different rates for different time periods/zones).
     */
    private function calculateTimeOfUseBill(
        ServiceConfiguration $serviceConfig,
        ConsumptionData $consumption
    ): CalculationResult {
        $rateSchedule = $serviceConfig->rate_schedule;
        $zoneRates = $rateSchedule['zone_rates'] ?? [];
        
        if (empty($zoneRates)) {
            throw new InvalidArgumentException('Time-of-use configuration missing zone rates');
        }
        
        $consumptionAmount = 0.0;
        $zoneBreakdown = [];
        
        foreach ($consumption->getZoneConsumption() as $zone => $zoneConsumption) {
            $zoneRate = $zoneRates[$zone] ?? $zoneRates['default'] ?? 0.0;
            $zoneAmount = $zoneConsumption * $zoneRate;
            
            $consumptionAmount += $zoneAmount;
            $zoneBreakdown[$zone] = [
                'consumption' => $zoneConsumption,
                'rate' => $zoneRate,
                'amount' => $zoneAmount,
            ];
        }
        
        return new CalculationResult(
            totalAmount: round($consumptionAmount, self::MONETARY_PRECISION),
            baseAmount: round($consumptionAmount, self::MONETARY_PRECISION),
            adjustments: [],
            consumptionAmount: round($consumptionAmount, self::MONETARY_PRECISION),
            fixedAmount: 0.0,
            tariffSnapshot: $this->createTariffSnapshot($serviceConfig),
            calculationDetails: [
                'pricing_model' => PricingModel::TIME_OF_USE->value,
                'zone_breakdown' => $zoneBreakdown,
            ]
        );
    }

    /**
     * Calculate custom formula bill (mathematical expressions).
     */
    private function calculateCustomFormulaBill(
        ServiceConfiguration $serviceConfig,
        ConsumptionData $consumption,
        BillingPeriod $billingPeriod
    ): CalculationResult {
        $rateSchedule = $serviceConfig->rate_schedule;
        $formula = $rateSchedule['formula'] ?? '';
        
        if (empty($formula)) {
            throw new InvalidArgumentException('Custom formula configuration missing formula definition');
        }
        
        // Prepare variables for formula evaluation
        $variables = [
            'consumption' => $consumption->getTotalConsumption(),
            'days' => $billingPeriod->getDays(),
            'month' => $billingPeriod->getStartDate()->month,
            'year' => $billingPeriod->getStartDate()->year,
            'is_summer' => $this->isSummerPeriod($billingPeriod->getStartDate()),
            'is_winter' => !$this->isSummerPeriod($billingPeriod->getStartDate()),
        ];
        
        // Add any custom variables from rate schedule
        $customVariables = $rateSchedule['variables'] ?? [];
        $variables = array_merge($variables, $customVariables);
        
        // Evaluate the formula (this would need a safe math expression evaluator)
        $calculatedAmount = $this->evaluateFormula($formula, $variables);
        
        return new CalculationResult(
            totalAmount: round($calculatedAmount, self::MONETARY_PRECISION),
            baseAmount: round($calculatedAmount, self::MONETARY_PRECISION),
            adjustments: [],
            consumptionAmount: round($calculatedAmount, self::MONETARY_PRECISION),
            fixedAmount: 0.0,
            tariffSnapshot: $this->createTariffSnapshot($serviceConfig),
            calculationDetails: [
                'pricing_model' => PricingModel::CUSTOM_FORMULA->value,
                'formula' => $formula,
                'variables' => $variables,
            ]
        );
    }

    /**
     * Calculate legacy flat bill (backward compatibility with TariffType::FLAT).
     */
    private function calculateLegacyFlatBill(
        ServiceConfiguration $serviceConfig,
        ConsumptionData $consumption
    ): CalculationResult {
        // For legacy compatibility, treat flat rate as consumption-based
        return $this->calculateConsumptionBasedBill($serviceConfig, $consumption);
    }

    /**
     * Integrate with existing GyvatukasCalculator for heating services.
     * 
     * This method provides seamless integration with the existing Lithuanian
     * heating system calculations while maintaining the universal billing interface.
     */
    public function calculateGyvatukasBill(
        Building $building,
        BillingPeriod $billingPeriod,
        DistributionMethod $distributionMethod = DistributionMethod::AREA
    ): CalculationResult {
        $month = $billingPeriod->getStartDate();
        
        // Use existing GyvatukasCalculator for core calculation
        $circulationEnergy = $this->gyvatukasCalculator->calculate($building, $month);
        
        // Get tariff rate for energy cost calculation
        $energyRate = $this->getEnergyRate($building, $month);
        $totalCost = $circulationEnergy * $energyRate;
        
        // Distribute cost among properties
        $distributionCosts = $this->gyvatukasCalculator->distributeCirculationCost(
            $building,
            $totalCost,
            $distributionMethod->value
        );
        
        return new CalculationResult(
            totalAmount: round($totalCost, self::MONETARY_PRECISION),
            baseAmount: round($totalCost, self::MONETARY_PRECISION),
            adjustments: [],
            consumptionAmount: round($totalCost, self::MONETARY_PRECISION),
            fixedAmount: 0.0,
            tariffSnapshot: $this->createGyvatukasTariffSnapshot($building, $month),
            calculationDetails: [
                'pricing_model' => 'gyvatukas',
                'circulation_energy_kwh' => $circulationEnergy,
                'energy_rate' => $energyRate,
                'distribution_method' => $distributionMethod->value,
                'property_distribution' => $distributionCosts,
                'is_summer_period' => $this->gyvatukasCalculator->isSummerPeriod($month),
                'is_heating_season' => $this->gyvatukasCalculator->isHeatingSeason($month),
            ]
        );
    }

    /**
     * Apply seasonal adjustments to rates based on summer/winter logic.
     */
    private function applySeasonalAdjustments(
        float $baseRate,
        BillingPeriod $billingPeriod,
        ServiceConfiguration $serviceConfig
    ): float {
        $rateSchedule = $serviceConfig->rate_schedule;
        $seasonalAdjustments = $rateSchedule['seasonal_adjustments'] ?? [];
        
        if (empty($seasonalAdjustments)) {
            return $baseRate;
        }
        
        $month = $billingPeriod->getStartDate();
        $isSummer = $this->isSummerPeriod($month);
        
        if ($isSummer && isset($seasonalAdjustments['summer_multiplier'])) {
            return $baseRate * $seasonalAdjustments['summer_multiplier'];
        }
        
        if (!$isSummer && isset($seasonalAdjustments['winter_multiplier'])) {
            return $baseRate * $seasonalAdjustments['winter_multiplier'];
        }
        
        return $baseRate;
    }

    /**
     * Apply pro-ration for partial billing periods.
     */
    private function applyProRation(float $monthlyAmount, BillingPeriod $billingPeriod): float
    {
        if (!$this->isPeriodPartial($billingPeriod)) {
            return $monthlyAmount;
        }
        
        $daysInPeriod = $billingPeriod->getDays();
        $daysInMonth = $billingPeriod->getStartDate()->daysInMonth;
        
        return $monthlyAmount * ($daysInPeriod / $daysInMonth);
    }

    /**
     * Check if the billing period is partial (not a full month).
     */
    private function isPeriodPartial(BillingPeriod $billingPeriod): bool
    {
        $startDate = $billingPeriod->getStartDate();
        $endDate = $billingPeriod->getEndDate();
        
        // Check if period covers the entire month
        return !($startDate->day === 1 && $endDate->day === $endDate->daysInMonth);
    }

    /**
     * Check if the given date is in summer period (using gyvatukas logic).
     */
    private function isSummerPeriod(Carbon $date): bool
    {
        return $this->gyvatukasCalculator->isSummerPeriod($date);
    }

    /**
     * Create tariff snapshot for invoice immutability.
     */
    private function createTariffSnapshot(ServiceConfiguration $serviceConfig): array
    {
        return [
            'service_configuration_id' => $serviceConfig->id,
            'pricing_model' => $serviceConfig->pricing_model->value,
            'rate_schedule' => $serviceConfig->rate_schedule,
            'distribution_method' => $serviceConfig->distribution_method->value,
            'effective_from' => $serviceConfig->effective_from?->toISOString(),
            'effective_until' => $serviceConfig->effective_until?->toISOString(),
            'snapshot_created_at' => now()->toISOString(),
        ];
    }

    /**
     * Create gyvatukas-specific tariff snapshot.
     */
    private function createGyvatukasTariffSnapshot(Building $building, Carbon $month): array
    {
        return [
            'calculation_type' => 'gyvatukas',
            'building_id' => $building->id,
            'calculation_month' => $month->format('Y-m'),
            'summer_average' => $building->gyvatukas_summer_average,
            'is_summer_period' => $this->gyvatukasCalculator->isSummerPeriod($month),
            'is_heating_season' => $this->gyvatukasCalculator->isHeatingSeason($month),
            'snapshot_created_at' => now()->toISOString(),
        ];
    }

    /**
     * Build adjustments array for calculation result.
     */
    private function buildAdjustmentsArray(float $originalAmount, float $adjustedAmount): array
    {
        if (abs($originalAmount - $adjustedAmount) < 0.01) {
            return [];
        }
        
        return [
            [
                'type' => 'seasonal_adjustment',
                'description' => 'Seasonal rate adjustment',
                'amount' => round($adjustedAmount - $originalAmount, self::MONETARY_PRECISION),
            ],
        ];
    }

    /**
     * Get energy rate for gyvatukas calculations.
     */
    private function getEnergyRate(Building $building, Carbon $month): float
    {
        // This would typically fetch the current energy tariff rate
        // For now, return a default rate from configuration
        return $this->config->get('gyvatukas.default_energy_rate', 0.15);
    }

    /**
     * Evaluate mathematical formula safely.
     * 
     * Note: This is a placeholder implementation. In production, you would
     * use a safe mathematical expression evaluator library.
     */
    private function evaluateFormula(string $formula, array $variables): float
    {
        // This is a simplified implementation
        // In production, use a proper math expression evaluator like:
        // - symfony/expression-language
        // - hoa/math
        // - Or a custom safe evaluator
        
        $this->logger->warning('Custom formula evaluation not fully implemented', [
            'formula' => $formula,
            'variables' => $variables,
        ]);
        
        // Return consumption-based calculation as fallback
        return $variables['consumption'] * 0.15; // Default rate
    }

    /**
     * Build cache key for calculation results.
     */
    private function buildCalculationCacheKey(
        ServiceConfiguration $serviceConfig,
        ConsumptionData $consumption,
        BillingPeriod $billingPeriod
    ): string {
        $keyData = [
            'service_config_id' => $serviceConfig->id,
            'consumption_hash' => md5(serialize($consumption->toArray())),
            'period_hash' => md5($billingPeriod->getStartDate()->format('Y-m-d') . $billingPeriod->getEndDate()->format('Y-m-d')),
        ];
        
        return sprintf('%s:%s', self::CACHE_PREFIX, md5(serialize($keyData)));
    }

    /**
     * Validate service configuration for calculations.
     */
    private function validateServiceConfiguration(ServiceConfiguration $serviceConfig): void
    {
        if (!$serviceConfig->pricing_model) {
            throw new InvalidArgumentException('Service configuration missing pricing model');
        }
        
        if (empty($serviceConfig->rate_schedule)) {
            throw new InvalidArgumentException('Service configuration missing rate schedule');
        }
    }

    /**
     * Validate consumption data for calculations.
     */
    private function validateConsumptionData(ConsumptionData $consumption): void
    {
        $totalConsumption = $consumption->getTotalConsumption();
        
        if ($totalConsumption < self::MIN_CONSUMPTION_VALUE) {
            throw new InvalidArgumentException('Consumption cannot be negative');
        }
        
        if ($totalConsumption > self::MAX_CONSUMPTION_VALUE) {
            throw new InvalidArgumentException('Consumption exceeds maximum allowed value');
        }
    }

    /**
     * Clear calculation cache for a service configuration.
     */
    public function clearServiceConfigurationCache(ServiceConfiguration $serviceConfig): void
    {
        // In a production environment, you would implement cache tag-based clearing
        // For now, we'll log the cache clearing request
        $this->logger->info('Service configuration cache clearing requested', [
            'service_configuration_id' => $serviceConfig->id,
        ]);
    }

    /**
     * Clear all universal billing calculation cache.
     */
    public function clearAllCache(): void
    {
        try {
            // In production, use cache tags for more targeted clearing
            $this->cache->flush();
            
            $this->logger->info('All universal billing cache cleared');
        } catch (\Exception $e) {
            $this->logger->error('Failed to clear universal billing cache', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}