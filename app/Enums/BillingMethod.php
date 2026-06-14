<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum BillingMethod: string implements HasLabel
{
    use HasTranslatedLabel;

    case METER_BASED = 'meter_based';
    case FIXED_MONTHLY = 'fixed_monthly';
    case ONE_TIME = 'one_time';
    case MANUAL = 'manual';
    case PERCENTAGE = 'percentage';
    case FORMULA_BASED = 'formula_based';
    case INCLUDED_FREE = 'included_free';

    public function createsAutomaticInvoiceItems(): bool
    {
        return match ($this) {
            self::METER_BASED,
            self::FIXED_MONTHLY,
            self::PERCENTAGE,
            self::FORMULA_BASED,
            self::INCLUDED_FREE => true,
            self::ONE_TIME,
            self::MANUAL => false,
        };
    }

    public function requiresMeterRules(): bool
    {
        return $this === self::METER_BASED;
    }

    public function requiresFixedAmount(): bool
    {
        return $this === self::FIXED_MONTHLY;
    }

    public function isFree(): bool
    {
        return $this === self::INCLUDED_FREE;
    }
}
