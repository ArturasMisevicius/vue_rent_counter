<?php

namespace App\Enums;

enum WeekendLogic: string
{
    case APPLY_NIGHT_RATE = 'apply_night_rate';
    case APPLY_DAY_RATE = 'apply_day_rate';
    case APPLY_WEEKEND_RATE = 'apply_weekend_rate';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match($this) {
            self::APPLY_NIGHT_RATE => 'Apply Night Rate on Weekends',
            self::APPLY_DAY_RATE => 'Apply Day Rate on Weekends',
            self::APPLY_WEEKEND_RATE => 'Apply Special Weekend Rate',
        };
    }
}
