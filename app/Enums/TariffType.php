<?php

namespace App\Enums;

enum TariffType: string
{
    case FLAT = 'flat';
    case TIME_OF_USE = 'time_of_use';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match($this) {
            self::FLAT => 'Flat Rate',
            self::TIME_OF_USE => 'Time of Use',
        };
    }
}
