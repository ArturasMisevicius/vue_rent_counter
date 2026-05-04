<?php

declare(strict_types=1);

namespace App\Filament\Support\Formatting;

use Brick\Money\Money;

final class EuMoneyFormatter
{
    private const HARD_SPACE = "\u{00A0}";

    public static function format(float|int|string|Money|null $amount, ?string $currency = 'EUR', ?int $decimals = null): string
    {
        if ($amount instanceof Money) {
            return self::formatMoney($amount, $decimals);
        }

        if ($amount === null || $amount === '') {
            return '—';
        }

        $currency = CurrencyMetadata::normalize($currency);
        $decimals ??= CurrencyMetadata::fractionDigits($currency);

        return self::number($amount, $decimals).self::HARD_SPACE.self::displayUnit($currency);
    }

    public static function number(float|int|string|null $amount, int $decimals = 2): string
    {
        return number_format((float) ($amount ?? 0), $decimals, ',', ' ');
    }

    public static function formatMoney(Money $money, ?int $decimals = null): string
    {
        return self::format(
            (string) $money->getAmount(),
            $money->getCurrency()->getCurrencyCode(),
            $decimals,
        );
    }

    public static function money(float|int|string $amount, ?string $currency = 'EUR'): Money
    {
        return Money::of($amount, CurrencyMetadata::normalize($currency));
    }

    public static function moneyFromMinor(float|int|string $minorAmount, ?string $currency = 'EUR'): Money
    {
        return Money::ofMinor($minorAmount, CurrencyMetadata::normalize($currency));
    }

    private static function displayUnit(string $currency): string
    {
        return $currency === 'EUR' ? CurrencyMetadata::symbol($currency) : $currency;
    }
}
