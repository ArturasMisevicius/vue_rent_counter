<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Enums\DistributionMethod;

final class SharedServiceCostDistributorService
{
    public function __construct(
        private readonly UniversalBillingCalculator $calculator,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function distribute(
        string|int|float $totalCost,
        DistributionMethod $distributionMethod,
        array $context = [],
    ): string {
        return match ($distributionMethod) {
            DistributionMethod::EQUAL => $this->allocatedShare(
                $totalCost,
                array_fill(0, (int) ($context['participant_count'] ?? 1), '1'),
                $context,
                fn (): string => $this->divideEqually(
                    $totalCost,
                    (int) ($context['participant_count'] ?? 1),
                ),
            ),
            DistributionMethod::AREA => $this->allocatedShare(
                $totalCost,
                $context['area_weights'] ?? [],
                $context,
                fn (): string => $this->distributeProportionally(
                    $totalCost,
                    $context['participant_area'] ?? 0,
                    $context['total_area'] ?? 0,
                ),
            ),
            DistributionMethod::BY_CONSUMPTION => $this->allocatedShare(
                $totalCost,
                $context['consumption_weights'] ?? [],
                $context,
                fn (): string => $this->distributeProportionally(
                    $totalCost,
                    $context['participant_consumption'] ?? 0,
                    $context['total_consumption'] ?? 0,
                ),
            ),
            DistributionMethod::BY_OCCUPANCY => $this->allocatedShare(
                $totalCost,
                $context['occupancy_weights'] ?? [],
                $context,
                fn (): string => $this->distributeProportionally(
                    $totalCost,
                    $context['participant_occupants'] ?? $context['participant_count'] ?? 1,
                    $context['total_occupants'] ?? $context['participant_count'] ?? 1,
                ),
            ),
            DistributionMethod::FIXED_SHARE => $this->calculator->money($context['fixed_share'] ?? 0),
            DistributionMethod::WEIGHTED_SHARE => $this->allocatedShare(
                $totalCost,
                $context['weighted_share_weights'] ?? [],
                $context,
                fn (): string => $this->distributeProportionally(
                    $totalCost,
                    $context['participant_weight'] ?? 0,
                    $context['total_weight'] ?? 0,
                ),
            ),
            DistributionMethod::CUSTOM_FORMULA => $this->calculator->money($context['custom_share'] ?? $totalCost),
        };
    }

    private function divideEqually(string|int|float $totalCost, int $participantCount): string
    {
        if ($participantCount <= 0) {
            return $this->calculator->money('0');
        }

        return $this->calculator->money(
            $this->calculator->divide($totalCost, (string) $participantCount, 6),
        );
    }

    private function distributeProportionally(
        string|int|float $totalCost,
        string|int|float $participantWeight,
        string|int|float $totalWeight,
    ): string {
        if ($this->calculator->compare($totalWeight, '0', 6) <= 0) {
            return $this->calculator->money('0');
        }

        $shareRatio = $this->calculator->divide($participantWeight, $totalWeight, 6);

        return $this->calculator->money(
            $this->calculator->multiply($totalCost, $shareRatio, 6),
        );
    }

    /**
     * @param  array<int, string|int|float>  $weights
     * @param  array<string, mixed>  $context
     */
    private function allocatedShare(
        string|int|float $totalCost,
        array $weights,
        array $context,
        callable $fallback,
    ): string {
        $participantIndex = $context['participant_index'] ?? null;

        if (! is_int($participantIndex) || $weights === []) {
            return $fallback();
        }

        $allocations = $this->calculator->allocate($totalCost, $weights);

        return $allocations[$participantIndex] ?? $fallback();
    }
}
