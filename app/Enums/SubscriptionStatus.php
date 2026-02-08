<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatableLabel;
use Filament\Support\Contracts\HasLabel;

enum SubscriptionStatus: string implements HasLabel
{
    use HasTranslatableLabel;

    case ACTIVE = 'active';
    case EXPIRED = 'expired';
    case SUSPENDED = 'suspended';
    case CANCELLED = 'cancelled';

    /**
     * Get the raw values for validation rules.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
