<?php

namespace App\Enums;

enum SubscriptionDuration: string
{
    case MONTHLY = 'monthly';
    case QUARTERLY = 'quarterly';
    case YEARLY = 'yearly';

    public function months(): int
    {
        return match ($this) {
            self::MONTHLY => 1,
            self::QUARTERLY => 3,
            self::YEARLY => 12,
        };
    }
}
