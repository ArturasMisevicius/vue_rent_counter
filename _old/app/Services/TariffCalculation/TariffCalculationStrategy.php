<?php

namespace App\Services\TariffCalculation;

use App\Models\Tariff;
use Carbon\Carbon;

interface TariffCalculationStrategy
{
    /**
     * Calculate the cost for a given tariff and consumption.
     *
     * @param Tariff $tariff
     * @param float $consumption
     * @param Carbon $timestamp
     * @return float
     */
    public function calculate(Tariff $tariff, float $consumption, Carbon $timestamp): float;

    /**
     * Check if this strategy can handle the given tariff type.
     *
     * @param string $tariffType
     * @return bool
     */
    public function supports(string $tariffType): bool;
}
