<?php

namespace App\Services\TariffCalculation;

use App\Models\Tariff;
use Carbon\Carbon;

class FlatRateStrategy implements TariffCalculationStrategy
{
    /**
     * Calculate the cost for a flat rate tariff.
     */
    public function calculate(Tariff $tariff, float $consumption, Carbon $timestamp): float
    {
        $config = $tariff->configuration;

        return $consumption * $config['rate'];
    }

    /**
     * Check if this strategy supports flat rate tariffs.
     */
    public function supports(string $tariffType): bool
    {
        return $tariffType === 'flat';
    }
}
