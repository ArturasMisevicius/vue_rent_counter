<?php

declare(strict_types=1);

namespace App\Filament\Support\Formatting;

use DateTimeInterface;
use Illuminate\Support\Carbon;
use IntlDateFormatter;

final class LocalizedDateFormatter
{
    /**
     * Carbon/Filament translatedFormat() patterns for places where Filament
     * requires a PHP date format string instead of an Intl formatter callback.
     *
     * @var array<string, string>
     */
    private const DATE_FORMATS = [
        'es' => 'j \\d\\e F \\d\\e Y',
        'lt' => 'Y \\m. F j \\d.',
        'ru' => 'j F Y \\г.',
    ];

    /**
     * @var array<string, string>
     */
    private const DATE_TIME_FORMATS = [
        'es' => 'j \\d\\e F \\d\\e Y H:i',
        'lt' => 'Y \\m. F j \\d. H:i',
        'ru' => 'j F Y \\г. H:i',
    ];

    public static function dateFormat(): string
    {
        return self::formatForLocale(self::DATE_FORMATS, 'F j, Y');
    }

    public static function dateTimeFormat(): string
    {
        return self::formatForLocale(self::DATE_TIME_FORMATS, self::dateFormat().' H:i');
    }

    public static function monthFormat(): string
    {
        return 'F';
    }

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

    /**
     * @param  array<string, string>  $formats
     */
    private static function formatForLocale(array $formats, string $fallback): string
    {
        $locale = str_replace('-', '_', app()->getLocale());
        $language = explode('_', $locale, 2)[0];

        return $formats[$locale] ?? $formats[$language] ?? $fallback;
    }
}
