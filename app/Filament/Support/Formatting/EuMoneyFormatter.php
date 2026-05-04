<?php

declare(strict_types=1);

namespace App\Filament\Support\Formatting;

final class EuMoneyFormatter
{
    private const HARD_SPACE = "\u{00A0}";

    public static function format(float|int|string|null $amount, ?string $currency = 'EUR', int $decimals = 2): string
    {
        if ($amount === null || $amount === '') {
            return '—';
        }

        $normalizedCurrency = strtoupper(trim((string) ($currency ?: 'EUR')));
        $unit = $normalizedCurrency === 'EUR' ? '€' : $normalizedCurrency;

        return self::number($amount, $decimals).self::HARD_SPACE.$unit;
    }

    public static function number(float|int|string|null $amount, int $decimals = 2): string
    {
        return number_format((float) ($amount ?? 0), $decimals, ',', ' ');
    }
}
