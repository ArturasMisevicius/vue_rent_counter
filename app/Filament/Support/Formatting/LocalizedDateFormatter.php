<?php

declare(strict_types=1);

namespace App\Filament\Support\Formatting;

use DateTimeInterface;
use Illuminate\Support\Carbon;
use IntlDateFormatter;

final class LocalizedDateFormatter
{
    public static function date(DateTimeInterface|string|null $value): string
    {
        return self::format($value, IntlDateFormatter::LONG, IntlDateFormatter::NONE);
    }

    public static function dateTime(DateTimeInterface|string|null $value): string
    {
        return self::format($value, IntlDateFormatter::LONG, IntlDateFormatter::SHORT);
    }

    private static function format(DateTimeInterface|string|null $value, int $dateType, int $timeType): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        $date = $value instanceof DateTimeInterface
            ? $value
            : Carbon::parse($value);

        $formatter = new IntlDateFormatter(
            app()->getLocale(),
            $dateType,
            $timeType,
            $date->getTimezone()->getName(),
        );

        $formatted = $formatter->format($date);

        return $formatted === false ? '—' : (string) $formatted;
    }
}
