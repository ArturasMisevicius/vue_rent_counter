<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum WeekendLogic: string implements HasLabel
{
    use HasTranslatedLabel;

    case APPLY_NIGHT_RATE = 'apply_night_rate';
    case APPLY_DAY_RATE = 'apply_day_rate';
    case APPLY_WEEKEND_RATE = 'apply_weekend_rate';
    case APPLY_OFF_PEAK_RATE = 'apply_off_peak_rate';
    case APPLY_PEAK_RATE = 'apply_peak_rate';
    case APPLY_SHOULDER_RATE = 'apply_shoulder_rate';
    case APPLY_HOLIDAY_RATE = 'apply_holiday_rate';
}
