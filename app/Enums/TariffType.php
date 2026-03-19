<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum TariffType: string implements HasLabel
{
    use HasTranslatedLabel;

    case FLAT = 'flat';
    case TIME_OF_USE = 'time_of_use';
    case SEASONAL = 'seasonal';

    public function requiresRate(): bool
    {
        return match ($this) {
            self::FLAT, self::SEASONAL => true,
            self::TIME_OF_USE => false,
        };
    }

    public function supportsZones(): bool
    {
        return $this === self::TIME_OF_USE;
    }
}
