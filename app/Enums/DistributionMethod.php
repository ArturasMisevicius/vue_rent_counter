<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Distribution methods for shared utility cost allocation.
 * 
 * Defines how shared costs (circulation energy, common area utilities, etc.) 
 * are distributed among properties in a building. Part of the Universal Utility 
 * Management System with full backward compatibility for gyvatukas calculations.
 * 
 * ## Available Methods
 * - **EQUAL**: Distribute costs equally among all properties
 * - **AREA**: Distribute costs proportionally based on property area
 * - **BY_CONSUMPTION**: Distribute costs based on actual consumption ratios
 * - **CUSTOM_FORMULA**: Use custom mathematical formulas for distribution
 * 
 * ## Usage Example
 * ```php
 * use App\Enums\DistributionMethod;
 * 
 * // Check requirements
 * $method = DistributionMethod::AREA;
 * if ($method->requiresAreaData()) {
 *     // Fetch area data
 *     $areaTypes = $method->getSupportedAreaTypes();
 * }
 * 
 * // Use in service configuration
 * $config = ServiceConfiguration::create([
 *     'distribution_method' => DistributionMethod::BY_CONSUMPTION,
 *     // ...
 * ]);
 * ```
 * 
 * @see \App\Services\GyvatukasCalculator For circulation cost distribution
 * @see \App\Services\UniversalBillingCalculator For universal billing integration
 * @see \App\Models\ServiceConfiguration For service-specific configuration
 * 
 * @package App\Enums
 * @since 1.0.0 (EQUAL, AREA)
 * @since 2.0.0 (BY_CONSUMPTION, CUSTOM_FORMULA - Universal Utility Management)
 */
enum DistributionMethod: string
{
    use HasLabel;

    /**
     * Equal distribution among all properties.
     * 
     * Simplest method with no special requirements. Divides total cost
     * equally regardless of property size or consumption.
     * 
     * @var string
     */
    case EQUAL = 'equal';
    
    /**
     * Area-based proportional distribution.
     * 
     * Distributes costs based on property area (square meters).
     * Supports multiple area types: total_area, heated_area, commercial_area.
     * 
     * @var string
     */
    case AREA = 'area';
    
    /**
     * Consumption-based distribution.
     * 
     * Distributes costs based on actual consumption ratios from historical data.
     * Falls back to equal distribution if consumption data unavailable.
     * 
     * @var string
     */
    case BY_CONSUMPTION = 'by_consumption';
    
    /**
     * Custom formula distribution.
     * 
     * Uses custom mathematical formulas for flexible distribution scenarios.
     * Falls back to equal distribution if formula evaluation fails.
     * 
     * @var string
     */
    case CUSTOM_FORMULA = 'custom_formula';

    public function getLabel(): string
    {
        return match ($this) {
            self::EQUAL => __('enums.distribution_method.equal'),
            self::AREA => __('enums.distribution_method.area'),
            self::BY_CONSUMPTION => __('enums.distribution_method.by_consumption'),
            self::CUSTOM_FORMULA => __('enums.distribution_method.custom_formula'),
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::EQUAL => __('enums.distribution_method.equal_description'),
            self::AREA => __('enums.distribution_method.area_description'),
            self::BY_CONSUMPTION => __('enums.distribution_method.by_consumption_description'),
            self::CUSTOM_FORMULA => __('enums.distribution_method.custom_formula_description'),
        };
    }

    /**
     * Check if this distribution method requires property area data.
     * 
     * Area data is required for area-based distribution calculations.
     * When true, properties must have valid area_sqm values.
     * 
     * @return bool True only for AREA method
     * 
     * @example
     * ```php
     * if ($method->requiresAreaData()) {
     *     // Validate area data exists
     *     $property->area_sqm > 0 or throw exception
     * }
     * ```
     */
    public function requiresAreaData(): bool
    {
        return $this === self::AREA;
    }

    /**
     * Check if this distribution method requires historical consumption data.
     * 
     * Consumption data is required for consumption-based distribution.
     * When true, system needs meter readings for all properties.
     * 
     * @return bool True only for BY_CONSUMPTION method
     * 
     * @example
     * ```php
     * if ($method->requiresConsumptionData()) {
     *     // Fetch consumption data from last 12 months
     *     $consumption = $property->getHistoricalConsumption(12);
     * }
     * ```
     */
    public function requiresConsumptionData(): bool
    {
        return $this === self::BY_CONSUMPTION;
    }

    /**
     * Check if this distribution method supports custom formula definitions.
     * 
     * Custom formulas allow flexible distribution scenarios combining
     * multiple factors (e.g., 70% area + 30% consumption).
     * 
     * @return bool True only for CUSTOM_FORMULA method
     * 
     * @example
     * ```php
     * if ($method->supportsCustomFormulas()) {
     *     // Apply custom formula
     *     $formula = 'area * 0.7 + consumption * 0.3';
     *     $cost = evaluateFormula($formula, $variables);
     * }
     * ```
     */
    public function supportsCustomFormulas(): bool
    {
        return $this === self::CUSTOM_FORMULA;
    }

    /**
     * Get supported area types for area-based distribution.
     * 
     * Returns available area types with translated labels for area-based
     * distribution. Different area types allow flexible allocation strategies
     * (e.g., heated area for heating costs, commercial area for mixed-use buildings).
     * 
     * @return array<string, string> Array of area types with translated labels,
     *                                empty array for non-area-based methods
     * 
     * @example
     * ```php
     * $areaTypes = DistributionMethod::AREA->getSupportedAreaTypes();
     * // Returns:
     * // [
     * //     'total_area' => 'Total Area',
     * //     'heated_area' => 'Heated Area',
     * //     'commercial_area' => 'Commercial Area',
     * // ]
     * 
     * // For non-area methods
     * DistributionMethod::EQUAL->getSupportedAreaTypes(); // []
     * ```
     */
    public function getSupportedAreaTypes(): array
    {
        if (!$this->requiresAreaData()) {
            return [];
        }

        return [
            'total_area' => __('enums.area_type.total_area'),
            'heated_area' => __('enums.area_type.heated_area'),
            'commercial_area' => __('enums.area_type.commercial_area'),
        ];
    }
}