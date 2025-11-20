<?php

namespace App\Services\BillingCalculation;

use App\Enums\MeterType;
use App\Services\GyvatukasCalculator;
use App\Services\TariffResolver;

/**
 * Factory for creating meter-specific billing calculators.
 */
class BillingCalculatorFactory
{
    public function __construct(
        private TariffResolver $tariffResolver,
        private GyvatukasCalculator $gyvatukasCalculator
    ) {}

    /**
     * Create a billing calculator for the given meter type.
     *
     * @param MeterType $meterType
     * @return BillingCalculator
     */
    public function create(MeterType $meterType): BillingCalculator
    {
        return match ($meterType) {
            MeterType::ELECTRICITY => new ElectricityCalculator($this->tariffResolver),
            MeterType::WATER_COLD, MeterType::WATER_HOT => new WaterCalculator(),
            MeterType::HEATING => new HeatingCalculator($this->gyvatukasCalculator),
        };
    }
}
