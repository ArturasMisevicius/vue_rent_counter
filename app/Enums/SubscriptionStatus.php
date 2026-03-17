<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case TRIALING = 'trialing';
    case ACTIVE = 'active';
    case EXPIRED = 'expired';
    case SUSPENDED = 'suspended';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::TRIALING => 'Trialing',
            self::ACTIVE => 'Active',
            self::EXPIRED => 'Expired',
            self::SUSPENDED => 'Suspended',
            self::CANCELLED => 'Cancelled',
        };
    }
}
