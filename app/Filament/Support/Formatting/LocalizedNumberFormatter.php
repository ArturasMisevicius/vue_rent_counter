<?php

declare(strict_types=1);

namespace App\Filament\Support\Formatting;

use NumberFormatter;

final class LocalizedNumberFormatter
{
    public static function integer(float|int|string|null $value): string
    {
        return self::format($value, 0, 0);
    }

    public static function decimal(float|int|string|null $value, int $fractionDigits = 2): string
    {
        return self::format($value, $fractionDigits, $fractionDigits);
    }

    public static function flexible(float|int|string|null $value, int $minFractionDigits = 0, int $maxFractionDigits = 3): string
    {
        return self::format($value, $minFractionDigits, $maxFractionDigits);
    }

    public static function format(
        float|int|string|null $value,
        int $minFractionDigits = 0,
        int $maxFractionDigits = 3,
    ): string {
        if (! self::isDisplayableNumber($value)) {
            return '—';
        }

        $minFractionDigits = max(0, $minFractionDigits);
        $maxFractionDigits = max($minFractionDigits, $maxFractionDigits);

        $formatter = new NumberFormatter(app()->getLocale(), NumberFormatter::DECIMAL);
        $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $minFractionDigits);
        $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $maxFractionDigits);

        $formatted = $formatter->format((float) $value);

        return $formatted === false ? '—' : (string) $formatted;
    }

    private static function isDisplayableNumber(float|int|string|null $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        return is_int($value) || is_float($value) || is_numeric($value);
    }
}
