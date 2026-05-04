<?php

declare(strict_types=1);

namespace App\Filament\Support\Formatting;

final class MeasurementFormatter
{
    private const HARD_SPACE = "\u{00A0}";

    public static function squareMeters(float|int|string|null $value, int $fractionDigits = 2): string
    {
        return self::format($value, 'm²', $fractionDigits);
    }

    public static function meterReading(float|int|string|null $value, ?string $unit, int $fractionDigits = 3): string
    {
        return self::format($value, $unit, $fractionDigits);
    }

    public static function consumption(float|int|string|null $value, ?string $unit, int $fractionDigits = 3): string
    {
        return self::format($value, $unit, $fractionDigits);
    }

    public static function format(float|int|string|null $value, ?string $unit, int $fractionDigits = 3): string
    {
        $number = LocalizedNumberFormatter::decimal($value, $fractionDigits);

        if ($number === '—') {
            return $number;
        }

        $normalizedUnit = trim((string) $unit);

        if ($normalizedUnit === '') {
            return $number;
        }

        return $number.self::HARD_SPACE.$normalizedUnit;
    }
}
