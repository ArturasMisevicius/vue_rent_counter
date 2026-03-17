<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum SubscriptionStatus: string implements HasLabel
{
    use HasTranslatedLabel;

    case TRIALING = 'trialing';
    case ACTIVE = 'active';
    case EXPIRED = 'expired';
    case SUSPENDED = 'suspended';
    case CANCELLED = 'cancelled';

    /**
     * @return array<int, string>
     */
    public static function activeLikeValues(): array
    {
        return self::onlyValues(
            self::ACTIVE,
            self::TRIALING,
        );
    }
}
