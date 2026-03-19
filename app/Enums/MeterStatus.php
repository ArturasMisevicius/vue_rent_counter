<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum MeterStatus: string implements HasLabel
{
    use HasTranslatedLabel;

    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case MAINTENANCE = 'maintenance';
    case FAULTY = 'faulty';
    case RETIRED = 'retired';

    public function badgeColor(): string
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::INACTIVE, self::RETIRED => 'gray',
            self::MAINTENANCE => 'warning',
            self::FAULTY => 'danger',
        };
    }

    public function toggleTarget(): self
    {
        return match ($this) {
            self::ACTIVE => self::INACTIVE,
            self::INACTIVE, self::MAINTENANCE, self::FAULTY => self::ACTIVE,
            self::RETIRED => self::RETIRED,
        };
    }
}
