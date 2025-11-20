<?php

namespace App\Services\BillingCalculation;

use App\Models\Meter;
use App\Models\Tariff;
use Carbon\Carbon;

/**
 * Interface for meter-specific billing calculations.
 */
interface BillingCalculator
{
    /**
     * Calculate the bill for a meter's consumption.
     *
     * @param Meter $meter
     * @param float $consumption
     * @param Tariff $tariff
     * @param Carbon $periodStart
     * @param mixed $property
     * @return array ['unit_price' => float, 'total' => float]
     */
    public function calculate(
        Meter $meter,
        float $consumption,
        Tariff $tariff,
        Carbon $periodStart,
        $property
    ): array;
}
