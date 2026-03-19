<?php

namespace App\Services\TariffCalculation;

use App\Models\Tariff;
use Carbon\Carbon;

interface TariffCalculationStrategy
{
    /**
     * Calculate the cost for a given tariff and consumption.
     */
    public function calculate(Tariff $tariff, float $consumption, Carbon $timestamp): float;

    /**
     * Check if this strategy can handle the given tariff type.
     */
    public function supports(string $tariffType): bool;
}
