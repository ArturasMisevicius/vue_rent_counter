<?php

declare(strict_types=1);

namespace App\Actions;

use App\Contracts\GyvatukasCalculatorInterface;
use App\Events\GyvatukasCalculated;
use App\Models\Building;
use App\ValueObjects\CalculationResult;
use Carbon\Carbon;

/**
 * Action for calculating gyvatukas with proper event handling.
 */
final readonly class CalculateGyvatukasAction
{
    public function __construct(
        private GyvatukasCalculatorInterface $calculator,
    ) {}

    public function execute(Building $building, Carbon $month): float
    {
        $energy = $this->calculator->calculate($building, $month);

        // Fire domain event for audit/logging purposes
        $result = CalculationResult::create(
            energy: $energy,
            calculationType: $this->calculator->isSummerPeriod($month) ? 'summer' : 'winter',
            buildingId: $building->id,
        );

        event(new GyvatukasCalculated($building, $result));

        return $energy;
    }
}