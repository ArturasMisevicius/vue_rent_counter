<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum MeterReadingValidationStatus: string implements HasLabel
{
    use HasTranslatedLabel;

    case PENDING = 'pending';
    case VALID = 'valid';
    case FLAGGED = 'flagged';
    case REJECTED = 'rejected';

    /**
     * @return array<int, string>
     */
    public static function comparableValues(): array
    {
        return self::onlyValues(
            self::VALID,
            self::FLAGGED,
        );
    }
}
