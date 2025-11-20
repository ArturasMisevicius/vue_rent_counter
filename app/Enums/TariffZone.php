<?php

namespace App\Enums;

enum TariffZone: string
{
    case DAY = 'day';
    case NIGHT = 'night';
    case WEEKEND = 'weekend';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match($this) {
            self::DAY => 'Day Rate',
            self::NIGHT => 'Night Rate',
            self::WEEKEND => 'Weekend Rate',
        };
    }
}
