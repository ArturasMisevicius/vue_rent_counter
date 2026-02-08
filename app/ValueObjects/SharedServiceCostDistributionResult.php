<?php

declare(strict_types=1);

namespace App\ValueObjects;

use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Result of shared service cost distribution calculation.
 * 
 * Immutable value object containing the distributed amounts
 * and metadata about the distribution process. Provides methods
 * for analyzing distribution results, validating accuracy,
 * and extracting statistical information.
 * 
 * @package App\ValueObjects
 * @see \App\Services\SharedServiceCostDistributorService
 * @see \Tests\Property\SharedServiceCostDistributionPropertyTest
 * 
 * @example
 * ```php
 * $result = new SharedServiceCostDistributionResult(
 *     collect([1 => 250.00, 2 => 750.00]),
 *     1000.00,
 *     ['method' => 'area', 'total_area' => 150.0]
 * );
 * 
 * echo $result->getTotalDistributed(); // 1000.00
 * echo $result->isBalanced(); // true
 * ```
 */
final readonly class SharedServiceCostDistributionResult
{
    /**
     * @param Collection<int, float> $distributedAmounts Property ID => Amount mapping
     * @param float $totalDistributed Total amount distributed
     * @param array<string, mixed> $metadata Additional distribution metadata
     */
    public function __construct(
        private Collection $distributedAmounts,
        private float $totalDistributed = 0.0,
        private array $metadata = [],
    ) {
        // Validate that distributed amounts are non-negative
        foreach ($this->distributedAmounts as $propertyId => $amount) {
            if ($amount < 0) {
                throw new InvalidArgumentException("Negative amount for property {$propertyId}: {$amount}");
            }
        }
        
        // Calculate total if not provided
        if ($this->totalDistributed === 0.0) {
            $this->totalDistributed = $this->distributedAmounts->sum();
        }
    }

    /**
     * Create from a simple property ID => amount array.
     */
    public static function fromArray(array $amounts, array $metadata = []): self
    {
        return new self(
            collect($amounts),
            array_sum($amounts),
            $metadata
        );
    }

    /**
     * Create an empty result (no distribution).
     */
    public static function empty(): self
    {
        return new self(collect(), 0.0, ['reason' => 'no_properties']);
    }

    /**
     * Get the distributed amounts collection.
     */
    public function getDistributedAmounts(): Collection
    {
        return $this->distributedAmounts;
    }

    /**
     * Get the total amount distributed.
     */
    public function getTotalDistributed(): float
    {
        return $this->totalDistributed;
    }

    /**
     * Get distribution metadata.
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Get the amount distributed to a specific property.
     */
    public function getAmountForProperty(int $propertyId): float
    {
        return $this->distributedAmounts->get($propertyId, 0.0);
    }

    /**
     * Get the number of properties that received allocations.
     */
    public function getPropertyCount(): int
    {
        return $this->distributedAmounts->count();
    }

    /**
     * Check if any amount was distributed.
     */
    public function hasDistribution(): bool
    {
        return $this->totalDistributed > 0;
    }

    /**
     * Check if the distribution is balanced (all amounts sum to total).
     */
    public function isBalanced(float $tolerance = 0.01): bool
    {
        $calculatedTotal = $this->distributedAmounts->sum();
        return abs($calculatedTotal - $this->totalDistributed) <= $tolerance;
    }

    /**
     * Get properties sorted by allocation amount (descending).
     */
    public function getPropertiesByAllocation(): Collection
    {
        return $this->distributedAmounts->sortDesc();
    }

    /**
     * Get the average allocation per property.
     */
    public function getAverageAllocation(): float
    {
        if ($this->getPropertyCount() === 0) {
            return 0.0;
        }
        
        return $this->totalDistributed / $this->getPropertyCount();
    }

    /**
     * Get the minimum allocation.
     */
    public function getMinAllocation(): float
    {
        return $this->distributedAmounts->min() ?? 0.0;
    }

    /**
     * Get the maximum allocation.
     */
    public function getMaxAllocation(): float
    {
        return $this->distributedAmounts->max() ?? 0.0;
    }

    /**
     * Get distribution statistics.
     */
    public function getStatistics(): array
    {
        return [
            'total_distributed' => $this->totalDistributed,
            'property_count' => $this->getPropertyCount(),
            'average_allocation' => $this->getAverageAllocation(),
            'min_allocation' => $this->getMinAllocation(),
            'max_allocation' => $this->getMaxAllocation(),
            'is_balanced' => $this->isBalanced(),
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Convert to array representation.
     */
    public function toArray(): array
    {
        return [
            'distributed_amounts' => $this->distributedAmounts->toArray(),
            'total_distributed' => $this->totalDistributed,
            'statistics' => $this->getStatistics(),
        ];
    }

    /**
     * Create a new result with additional metadata.
     */
    public function withMetadata(array $additionalMetadata): self
    {
        return new self(
            $this->distributedAmounts,
            $this->totalDistributed,
            array_merge($this->metadata, $additionalMetadata)
        );
    }

    /**
     * Filter results to only include properties with non-zero allocations.
     */
    public function nonZeroAllocations(): self
    {
        $filtered = $this->distributedAmounts->filter(fn($amount) => $amount > 0);
        
        return new self(
            $filtered,
            $filtered->sum(),
            array_merge($this->metadata, ['filtered' => 'non_zero_only'])
        );
    }
}