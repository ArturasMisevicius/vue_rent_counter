<?php

namespace App\Enums;

enum SubscriptionDuration: string
{
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';
    case QUARTERLY = 'quarterly';
    case SEMIANNUAL = 'semiannual';
    case YEARLY = 'yearly';
    case BIENNIAL = 'biennial';
    case TRIENNIAL = 'triennial';

    public function label(): string
    {
        return match ($this) {
            self::WEEKLY => 'Weekly',
            self::MONTHLY => 'Monthly',
            self::QUARTERLY => 'Quarterly',
            self::SEMIANNUAL => 'Semiannual',
            self::YEARLY => 'Yearly',
            self::BIENNIAL => 'Biennial',
            self::TRIENNIAL => 'Triennial',
        };
    }

    public function months(): int
    {
        return match ($this) {
            self::WEEKLY => 0, // Weekly is less than a month; returning 0, or could use fractional value or days if refactoring
            self::MONTHLY => 1,
            self::QUARTERLY => 3,
            self::SEMIANNUAL => 6,
            self::YEARLY => 12,
            self::BIENNIAL => 24,
            self::TRIENNIAL => 36,
        };
    }

    public static function all(): array
    {
        return [
            self::WEEKLY,
            self::MONTHLY,
            self::QUARTERLY,
            self::SEMIANNUAL,
            self::YEARLY,
            self::BIENNIAL,
            self::TRIENNIAL,
        ];
    }
}
