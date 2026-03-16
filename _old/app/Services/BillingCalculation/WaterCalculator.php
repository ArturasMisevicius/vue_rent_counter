<?php

declare(strict_types=1);

namespace App\Services\BillingCalculation;

use App\Models\Meter;
use App\Models\Tariff;
use Carbon\Carbon;

final class WaterCalculator
{
    /**
     * Calculate water billing amounts.
     *
     * Formula:
     * total = (consumption × supply_rate) + (consumption × sewage_rate) + fixed_fee
     *
     * Rates are read from the tariff configuration when present, otherwise fall back to config defaults:
     * - billing.water_tariffs.default_supply_rate (default 0.97)
     * - billing.water_tariffs.default_sewage_rate (default 1.23)
     * - billing.water_tariffs.default_fixed_fee (default 0.85)
     *
     * @return array{unit_price: float, total: float, supply_rate: float, sewage_rate: float, fixed_fee: float}
     */
    public function calculate(
        Meter $meter,
        float $consumption,
        Tariff $tariff,
        Carbon $periodStart,
        ?Carbon $periodEnd = null,
    ): array {
        $configuration = is_array($tariff->configuration ?? null) ? $tariff->configuration : [];

        $supplyRate = (float) ($configuration['supply_rate']
            ?? config('billing.water_tariffs.default_supply_rate', 0.97));

        $sewageRate = (float) ($configuration['sewage_rate']
            ?? config('billing.water_tariffs.default_sewage_rate', 1.23));

        $fixedFee = (float) ($configuration['fixed_fee']
            ?? config('billing.water_tariffs.default_fixed_fee', 0.85));

        $unitPrice = $supplyRate + $sewageRate;
        $total = ($consumption * $unitPrice) + $fixedFee;

        return [
            'unit_price' => round($unitPrice, 5),
            'total' => round($total, 3),
            'supply_rate' => $supplyRate,
            'sewage_rate' => $sewageRate,
            'fixed_fee' => $fixedFee,
        ];
    }
}

