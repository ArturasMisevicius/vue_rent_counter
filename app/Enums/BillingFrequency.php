<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum BillingFrequency: string implements HasLabel
{
    use HasTranslatedLabel;

    case MONTHLY = 'monthly';
    case QUARTERLY = 'quarterly';
    case YEARLY = 'yearly';
    case ONE_TIME = 'one_time';
}
