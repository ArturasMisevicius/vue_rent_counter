<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\ServiceConfiguration;
use App\ValueObjects\BillingPeriod;
use App\ValueObjects\SharedServiceCostDistributionResult;
use Illuminate\Support\Collection;

/**
 * Interface for shared service cost distribution.
 * 
 * Defines the contract for distributing shared utility costs
 * among properties based on various distribution methods.
 */
interface SharedServiceCostDistributor
{
    /**
     * Distribute cost among properties based on service configuration.
     * 
     * @param ServiceConfiguration $serviceConfig The service configuration with distribution method
     * @param Collection<int, \App\Models\Property> $properties Properties to distribute cost among
     * @param float $totalCost Total cost to distribute
     * @param BillingPeriod $billingPeriod The billing period for the distribution
     * @return SharedServiceCostDistributionResult The distribution result
     * 
     * @throws \InvalidArgumentException When invalid parameters are provided
     * @throws \App\Exceptions\DistributionException When distribution calculation fails
     */
    public function distributeCost(
        ServiceConfiguration $serviceConfig,
        Collection $properties,
        float $totalCost,
        BillingPeriod $billingPeriod
    ): SharedServiceCostDistributionResult;

    /**
     * Validate that properties can be used for the given distribution method.
     * 
     * @param ServiceConfiguration $serviceConfig The service configuration
     * @param Collection<int, \App\Models\Property> $properties Properties to validate
     * @return array<string> Array of validation errors (empty if valid)
     */
    public function validateProperties(
        ServiceConfiguration $serviceConfig,
        Collection $properties
    ): array;

    /**
     * Get supported distribution methods by this distributor.
     * 
     * @return array<\App\Enums\DistributionMethod>
     */
    public function getSupportedMethods(): array;
}