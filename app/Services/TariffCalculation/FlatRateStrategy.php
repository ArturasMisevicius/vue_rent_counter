<?php

namespace App\Services\TariffCalculation;

use App\Models\Tariff;
use Carbon\Carbon;

class FlatRateStrategy implements TariffCalculationStrategy
{
    /**
     * Calculate the cost for a flat rate tariff.
     *
     * @param Tariff $tariff
     * @param float $consumption
     * @param Carbon $timestamp
     * @return float
     */
    public function calculate(Tariff $tariff, float $consumption, Carbon $timestamp): float
    {
        $config = $tariff->configuration;
        return $consumption * $config['rate'];
    }

    /**
     * Check if this strategy supports flat rate tariffs.
     *
     * @param string $tariffType
     * @return bool
     */
    public function supports(string $tariffType): bool
    {
        return $tariffType === 'flat';
    }
}
