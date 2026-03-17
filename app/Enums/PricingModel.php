<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum PricingModel: string implements HasLabel
{
    use HasTranslatedLabel;

    case FIXED_MONTHLY = 'fixed_monthly';
    case CONSUMPTION_BASED = 'consumption_based';
    case TIERED_RATES = 'tiered_rates';
    case HYBRID = 'hybrid';
    case CUSTOM_FORMULA = 'custom_formula';
    case FLAT = 'flat';
    case TIME_OF_USE = 'time_of_use';

    public function requiresConsumptionData(): bool
    {
        return match ($this) {
            self::CONSUMPTION_BASED,
            self::TIERED_RATES,
            self::HYBRID,
            self::TIME_OF_USE => true,
            default => false,
        };
    }

    public function supportsCustomFormulas(): bool
    {
        return $this === self::CUSTOM_FORMULA;
    }
}
