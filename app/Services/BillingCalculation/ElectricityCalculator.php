<?php

namespace App\Services\BillingCalculation;

use App\Models\Meter;
use App\Models\Tariff;
use App\Services\TariffResolver;
use Carbon\Carbon;

/**
 * Calculator for electricity billing.
 */
class ElectricityCalculator implements BillingCalculator
{
    public function __construct(
        private TariffResolver $tariffResolver
    ) {}

    public function calculate(
        Meter $meter,
        float $consumption,
        Tariff $tariff,
        Carbon $periodStart,
        $property
    ): array {
        $unitPrice = $this->tariffResolver->calculateCost($tariff, 1, $periodStart);
        $total = $this->tariffResolver->calculateCost($tariff, $consumption, $periodStart);

        return [
            'unit_price' => $unitPrice,
            'total' => $total,
        ];
    }
}
