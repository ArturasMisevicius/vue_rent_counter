<?php

declare(strict_types=1);

namespace App\Actions;

use App\Contracts\GyvatukasCalculatorInterface;
use App\Events\SummerAverageCalculated;
use App\Models\Building;

/**
 * Action for calculating and storing summer average.
 */
final readonly class CalculateSummerAverageAction
{
    public function __construct(
        private GyvatukasCalculatorInterface $calculator,
    ) {}

    public function execute(Building $building): float
    {
        $average = $this->calculator->calculateAndStoreSummerAverage($building);

        // Fire domain event
        event(new SummerAverageCalculated(
            building: $building,
            summerAverage: $average,
            monthCount: 5, // May through September
        ));

        return $average;
    }
}