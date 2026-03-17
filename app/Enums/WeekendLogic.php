<?php

namespace App\Enums;

enum WeekendLogic: string
{
    case APPLY_NIGHT_RATE = 'apply_night_rate';
    case APPLY_DAY_RATE = 'apply_day_rate';
    case APPLY_WEEKEND_RATE = 'apply_weekend_rate';
}
