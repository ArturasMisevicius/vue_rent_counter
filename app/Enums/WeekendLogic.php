<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatableLabel;
use Filament\Support\Contracts\HasLabel;

enum WeekendLogic: string implements HasLabel
{
    use HasTranslatableLabel;

    case APPLY_NIGHT_RATE = 'apply_night_rate';
    case APPLY_DAY_RATE = 'apply_day_rate';
    case APPLY_WEEKEND_RATE = 'apply_weekend_rate';
}
