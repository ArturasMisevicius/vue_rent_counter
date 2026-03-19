<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum SubscriptionDuration: string implements HasLabel
{
    use HasTranslatedLabel;

    case MONTHLY = 'monthly';
    case QUARTERLY = 'quarterly';
    case SEMI_ANNUAL = 'semi_annual';
    case YEARLY = 'yearly';
    case BIENNIAL = 'biennial';
    case TRIENNIAL = 'triennial';

    public function months(): int
    {
        return match ($this) {
            self::MONTHLY => 1,
            self::QUARTERLY => 3,
            self::SEMI_ANNUAL => 6,
            self::YEARLY => 12,
            self::BIENNIAL => 24,
            self::TRIENNIAL => 36,
        };
    }
}
