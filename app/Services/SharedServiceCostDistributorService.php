<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\SharedServiceCostDistributor;
use App\Enums\DistributionMethod;
use App\Exceptions\DistributionException;
use App\Models\ServiceConfiguration;
use App\Services\FormulaEvaluator;
use App\ValueObjects\BillingPeriod;
use App\ValueObjects\SharedServiceCostDistributionResult;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Service for distributing shared utility costs among properties.
 * 
 * Implements various distribution methods: equal, area-based,
 * consumption-based, and custom formula distribution. Ensures accurate
 * cost allocation according to configured business rules while maintaining
 * mathematical precision and audit trail requirements.
 * 
 * @package App\Services
 * @see \App\Contracts\SharedServiceCostDistributor
 * @see \App\ValueObjects\SharedServiceCostDistributionResult
 * @see \Tests\Property\SharedServiceCostDistributionPropertyTest
 * 
 * @example
 * ```php
 * $costDistributor = app(SharedServiceCostDistributorService::class);
 * $result = $costDistributor->distributeCost(
 *     $serviceConfig,
 *     $properties,
 *     1000.00,
 *     BillingPeriod::currentMonth()
 * );
 * ```
 */
final readonly class SharedServiceCostDistributorService implements SharedServiceCostDistributor
{
    public function __construct(
        private FormulaEvaluator $formulaEvaluator,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function distributeCost(
        ServiceConfiguration $serviceConfig,
        Collection $properties,
        float $totalCost,
        BillingPeriod $billingPeriod
    ): SharedServiceCostDistributionResult {
        // Validate inputs
        $this->validateInputs($serviceConfig, $properties, $totalCost);
        
        // Validate properties for this distribution method
        $validationErrors = $this->validateProperties($serviceConfig, $properties);
        if (!empty($validationErrors)) {
            throw new InvalidArgumentException('Property validation failed: ' . implode(', ', $validationErrors));
        }

        // Handle edge cases
        if ($totalCost <= 0) {
            return $this->createZeroDistribution($properties);
        }

        if ($properties->isEmpty()) {
            return SharedServiceCostDistributionResult::empty();
        }

        if ($properties->count() === 1) {
            return $this->createSinglePropertyDistribution($properties->first(), $totalCost);
        }

        // Distribute based on method
        return match ($serviceConfig->distribution_method) {
            DistributionMethod::EQUAL => $this->distributeEqually($properties, $totalCost),
            DistributionMethod::AREA => $this->distributeByArea($properties, $totalCost, $serviceConfig),
            DistributionMethod::BY_CONSUMPTION => $this->distributeByConsumption($properties, $totalCost, $billingPeriod),
            DistributionMethod::CUSTOM_FORMULA => $this->distributeByFormula($properties, $totalCost, $serviceConfig, $billingPeriod),
        };
    }

    /**
     * {@inheritDoc}
     */
    public function validateProperties(
        ServiceConfiguration $serviceConfig,
        Collection $properties
    ): array {
        $errors = [];
        $method = $serviceConfig->distribution_method;

        // Check area data requirements
        if ($method->requiresAreaData()) {
            foreach ($properties as $property) {
                if (!isset($property->area_sqm) || $property->area_sqm <= 0) {
                    $errors[] = "Property {$property->id} missing or invalid area data";
                }
            }
        }

        // Check consumption data requirements
        if ($method->requiresConsumptionData()) {
            foreach ($properties as $property) {
                if (!isset($property->historical_consumption) || $property->historical_consumption < 0) {
                    $errors[] = "Property {$property->id} missing or invalid consumption data";
                }
            }
        }

        // Check custom formula requirements
        if ($method === DistributionMethod::CUSTOM_FORMULA) {
            $formula = $serviceConfig->rate_schedule['formula'] ?? '';
            if (empty($formula)) {
                $errors[] = 'Custom formula is required but not provided';
            } elseif (!$this->formulaEvaluator->validateFormula($formula)) {
                $errors[] = 'Invalid custom formula syntax';
            }
        }

        return $errors;
    }

    /**
     * {@inheritDoc}
     */
    public function getSupportedMethods(): array
    {
        return [
            DistributionMethod::EQUAL,
            DistributionMethod::AREA,
            DistributionMethod::BY_CONSUMPTION,
            DistributionMethod::CUSTOM_FORMULA,
        ];
    }

    /**
     * Distribute cost equally among all properties.
     */
    private function distributeEqually(Collection $properties, float $totalCost): SharedServiceCostDistributionResult
    {
        $amountPerProperty = $totalCost / $properties->count();
        $distributedAmounts = collect();

        foreach ($properties as $property) {
            $distributedAmounts[$property->id] = $amountPerProperty;
        }

        return new SharedServiceCostDistributionResult(
            $distributedAmounts,
            $totalCost,
            ['method' => 'equal', 'amount_per_property' => $amountPerProperty]
        );
    }

    /**
     * Distribute cost based on property areas.
     */
    private function distributeByArea(
        Collection $properties, 
        float $totalCost, 
        ServiceConfiguration $serviceConfig
    ): SharedServiceCostDistributionResult {
        $totalArea = $properties->sum('area_sqm');
        
        if ($totalArea <= 0) {
            // Fallback to equal distribution
            return $this->distributeEqually($properties, $totalCost)
                ->withMetadata(['fallback_reason' => 'no_area_data']);
        }

        $distributedAmounts = collect();
        foreach ($properties as $property) {
            $proportion = $property->area_sqm / $totalArea;
            $distributedAmounts[$property->id] = $totalCost * $proportion;
        }

        return new SharedServiceCostDistributionResult(
            $distributedAmounts,
            $totalCost,
            [
                'method' => 'area',
                'total_area' => $totalArea,
                'area_type' => $serviceConfig->area_type ?? 'total_area',
            ]
        );
    }

    /**
     * Distribute cost based on historical consumption.
     */
    private function distributeByConsumption(
        Collection $properties, 
        float $totalCost, 
        BillingPeriod $billingPeriod
    ): SharedServiceCostDistributionResult {
        $totalConsumption = $properties->sum('historical_consumption');
        
        if ($totalConsumption <= 0) {
            // Fallback to equal distribution
            return $this->distributeEqually($properties, $totalCost)
                ->withMetadata(['fallback_reason' => 'no_consumption_data']);
        }

        $distributedAmounts = collect();
        foreach ($properties as $property) {
            $proportion = $property->historical_consumption / $totalConsumption;
            $distributedAmounts[$property->id] = $totalCost * $proportion;
        }

        return new SharedServiceCostDistributionResult(
            $distributedAmounts,
            $totalCost,
            [
                'method' => 'consumption',
                'total_consumption' => $totalConsumption,
                'billing_period' => $billingPeriod->getLabel(),
            ]
        );
    }

    /**
     * Distribute cost using custom formula.
     */
    private function distributeByFormula(
        Collection $properties, 
        float $totalCost, 
        ServiceConfiguration $serviceConfig,
        BillingPeriod $billingPeriod
    ): SharedServiceCostDistributionResult {
        $formula = $serviceConfig->rate_schedule['formula'] ?? '';
        
        if (empty($formula)) {
            // Fallback to equal distribution
            return $this->distributeEqually($properties, $totalCost)
                ->withMetadata(['fallback_reason' => 'no_formula']);
        }

        try {
            $totalFormulaValue = 0;
            $formulaValues = [];

            // Calculate formula value for each property
            foreach ($properties as $property) {
                $variables = [
                    'area' => $property->area_sqm ?? 0,
                    'consumption' => $property->historical_consumption ?? 0,
                    'property_id' => $property->id,
                ];

                $formulaValue = $this->formulaEvaluator->evaluate($formula, $variables);
                $formulaValues[$property->id] = $formulaValue;
                $totalFormulaValue += $formulaValue;
            }

            if ($totalFormulaValue <= 0) {
                // Fallback to equal distribution
                return $this->distributeEqually($properties, $totalCost)
                    ->withMetadata(['fallback_reason' => 'zero_formula_result']);
            }

            // Distribute proportionally based on formula results
            $distributedAmounts = collect();
            foreach ($properties as $property) {
                $proportion = $formulaValues[$property->id] / $totalFormulaValue;
                $distributedAmounts[$property->id] = $totalCost * $proportion;
            }

            return new SharedServiceCostDistributionResult(
                $distributedAmounts,
                $totalCost,
                [
                    'method' => 'custom_formula',
                    'formula' => $formula,
                    'total_formula_value' => $totalFormulaValue,
                    'formula_values' => $formulaValues,
                ]
            );

        } catch (\Exception $e) {
            // Fallback to equal distribution on formula error
            return $this->distributeEqually($properties, $totalCost)
                ->withMetadata([
                    'fallback_reason' => 'formula_error',
                    'error' => $e->getMessage(),
                ]);
        }
    }

    /**
     * Create zero distribution for all properties.
     */
    private function createZeroDistribution(Collection $properties): SharedServiceCostDistributionResult
    {
        $distributedAmounts = collect();
        foreach ($properties as $property) {
            $distributedAmounts[$property->id] = 0.0;
        }

        return new SharedServiceCostDistributionResult(
            $distributedAmounts,
            0.0,
            ['reason' => 'zero_cost']
        );
    }

    /**
     * Create distribution for single property (gets all cost).
     */
    private function createSinglePropertyDistribution($property, float $totalCost): SharedServiceCostDistributionResult
    {
        return new SharedServiceCostDistributionResult(
            collect([$property->id => $totalCost]),
            $totalCost,
            ['reason' => 'single_property']
        );
    }

    /**
     * Validate input parameters.
     */
    private function validateInputs(
        ServiceConfiguration $serviceConfig,
        Collection $properties,
        float $totalCost
    ): void {
        if ($totalCost < 0) {
            throw new InvalidArgumentException('Total cost cannot be negative');
        }

        if (!in_array($serviceConfig->distribution_method, $this->getSupportedMethods())) {
            throw new InvalidArgumentException(
                "Unsupported distribution method: {$serviceConfig->distribution_method->value}"
            );
        }

        // Validate property collection contains Property models
        foreach ($properties as $property) {
            if (!$property instanceof \App\Models\Property) {
                throw new InvalidArgumentException('Properties collection must contain Property models');
            }
        }
    }
}