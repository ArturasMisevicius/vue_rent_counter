<?php

namespace App\Enums;

enum DistributionMethod: string
{
    case EQUAL = 'equal';
    case AREA = 'area';
    case BY_CONSUMPTION = 'by_consumption';
    case CUSTOM_FORMULA = 'custom_formula';

    public function requiresAreaData(): bool
    {
        return $this === self::AREA;
    }

    public function requiresConsumptionData(): bool
    {
        return $this === self::BY_CONSUMPTION;
    }

    public function supportsCustomFormulas(): bool
    {
        return $this === self::CUSTOM_FORMULA;
    }
}
