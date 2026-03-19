<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum DistributionMethod: string implements HasLabel
{
    use HasTranslatedLabel;

    case EQUAL = 'equal';
    case AREA = 'area';
    case BY_CONSUMPTION = 'by_consumption';
    case BY_OCCUPANCY = 'by_occupancy';
    case FIXED_SHARE = 'fixed_share';
    case WEIGHTED_SHARE = 'weighted_share';
    case CUSTOM_FORMULA = 'custom_formula';

    public function requiresAreaData(): bool
    {
        return $this === self::AREA;
    }

    public function requiresConsumptionData(): bool
    {
        return $this === self::BY_CONSUMPTION;
    }

    public function requiresOccupancyData(): bool
    {
        return $this === self::BY_OCCUPANCY;
    }

    public function requiresFixedShare(): bool
    {
        return $this === self::FIXED_SHARE;
    }

    public function requiresWeightData(): bool
    {
        return $this === self::WEIGHTED_SHARE;
    }

    public function supportsCustomFormulas(): bool
    {
        return $this === self::CUSTOM_FORMULA;
    }
}
