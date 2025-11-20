<?php

namespace App\Services\BillingCalculation;

use App\Models\Meter;
use App\Models\Tariff;
use Carbon\Carbon;

/**
 * Calculator for water billing (cold and hot water).
 * 
 * Includes supply rate, sewage rate, and fixed monthly fee.
 */
class WaterCalculator implements BillingCalculator
{
    public function calculate(
        Meter $meter,
        float $consumption,
        Tariff $tariff,
        Carbon $periodStart,
        $property
    ): array {
        $config = $tariff->configuration;
        
        $supplyRate = $config['supply_rate'] ?? config('billing.water_tariffs.default_supply_rate', 0.97);
        $sewageRate = $config['sewage_rate'] ?? config('billing.water_tariffs.default_sewage_rate', 1.23);
        $fixedFee = $config['fixed_fee'] ?? config('billing.water_tariffs.default_fixed_fee', 0.85);

        // Calculate total: (consumption × supply) + (consumption × sewage) + fixed fee
        $supplyCharge = $consumption * $supplyRate;
        $sewageCharge = $consumption * $sewageRate;
        $total = $supplyCharge + $sewageCharge + $fixedFee;

        // Unit price is the combined rate per m³
        $unitPrice = $supplyRate + $sewageRate;

        return [
            'unit_price' => $unitPrice,
            'total' => $total,
        ];
    }
}
