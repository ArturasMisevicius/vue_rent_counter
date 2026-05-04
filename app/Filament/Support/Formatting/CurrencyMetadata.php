<?php

declare(strict_types=1);

namespace App\Filament\Support\Formatting;

use Symfony\Component\Intl\Currencies;

final class CurrencyMetadata
{
    public static function normalize(?string $currency = 'EUR'): string
    {
        $currency = strtoupper(trim((string) ($currency ?: 'EUR')));

        return $currency !== '' ? $currency : 'EUR';
    }

    public static function symbol(?string $currency = 'EUR', ?string $locale = null): string
    {
        $currency = self::normalize($currency);

        if (! Currencies::exists($currency)) {
            return $currency;
        }

        return Currencies::getSymbol($currency, $locale ?? self::locale());
    }

    public static function name(?string $currency = 'EUR', ?string $locale = null): string
    {
        $currency = self::normalize($currency);

        if (! Currencies::exists($currency)) {
            return $currency;
        }

        return Currencies::getName($currency, $locale ?? self::locale());
    }

    public static function fractionDigits(?string $currency = 'EUR'): int
    {
        $currency = self::normalize($currency);

        if (! Currencies::exists($currency)) {
            return 2;
        }

        return Currencies::getFractionDigits($currency);
    }

    private static function locale(): string
    {
        return app()->bound('config') ? app()->getLocale() : 'en';
    }
}
