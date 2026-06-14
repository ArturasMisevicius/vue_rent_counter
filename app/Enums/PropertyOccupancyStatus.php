<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum PropertyOccupancyStatus: string implements HasLabel
{
    use HasTranslatedLabel;

    case VACANT = 'vacant';
    case OCCUPIED = 'occupied';
    case MOVE_IN_SCHEDULED = 'move_in_scheduled';
    case MOVE_OUT_SCHEDULED = 'move_out_scheduled';
    case UNAVAILABLE = 'unavailable';
    case MAINTENANCE = 'maintenance';

    public function color(): string
    {
        return match ($this) {
            self::OCCUPIED => 'success',
            self::MOVE_IN_SCHEDULED, self::MOVE_OUT_SCHEDULED => 'warning',
            self::UNAVAILABLE, self::MAINTENANCE => 'danger',
            self::VACANT => 'gray',
        };
    }

    public function isManualHold(): bool
    {
        return in_array($this, [self::UNAVAILABLE, self::MAINTENANCE], true);
    }
}
