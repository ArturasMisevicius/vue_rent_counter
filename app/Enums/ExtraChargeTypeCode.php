<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum ExtraChargeTypeCode: string implements HasLabel
{
    use HasTranslatedLabel;

    case FIXED_SERVICE = 'fixed_service';
    case ONE_TIME_CHARGE = 'one_time_charge';
    case MANUAL_EXPENSE = 'manual_expense';
    case PENALTY = 'penalty';
    case DISCOUNT = 'discount';
    case RENT = 'rent';
    case DEPOSIT = 'deposit';
    case CORRECTION = 'correction';
    case OTHER = 'other';

    public function allowsNegativeAmount(): bool
    {
        return in_array($this, [
            self::DISCOUNT,
            self::CORRECTION,
        ], true);
    }
}
