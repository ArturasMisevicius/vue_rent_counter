<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum PropertyAssignmentStatus: string implements HasLabel
{
    use HasTranslatedLabel;

    case SCHEDULED = 'scheduled';
    case ACTIVE = 'active';
    case MOVE_OUT_SCHEDULED = 'move_out_scheduled';
    case ENDED = 'ended';
    case CANCELLED = 'cancelled';

    /**
     * @return array<int, string>
     */
    public static function openValues(): array
    {
        return self::onlyValues(self::SCHEDULED, self::ACTIVE, self::MOVE_OUT_SCHEDULED);
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::SCHEDULED, self::ACTIVE, self::MOVE_OUT_SCHEDULED], true);
    }
}
