<?php

declare(strict_types=1);

namespace App\Filament\Support\Localization;

use Illuminate\Support\Str;

final class LocalizedCodeLabel
{
    public static function translate(string $prefix, int|string|null $value, ?string $emptyLabel = null): string
    {
        if (blank($value)) {
            return $emptyLabel ?? __('superadmin.audit_logs.placeholders.empty');
        }

        $rawValue = (string) $value;
        $translationKey = $prefix.'.'.self::segment($rawValue);

        if (trans()->has($translationKey)) {
            return __($translationKey);
        }

        return Str::of($rawValue)
            ->replace(['_', '-'], ' ')
            ->headline()
            ->toString();
    }

    /**
     * @param  list<string>  $values
     * @return array<string, string>
     */
    public static function options(string $prefix, array $values): array
    {
        return collect($values)
            ->mapWithKeys(fn (string $value): array => [$value => self::translate($prefix, $value)])
            ->all();
    }

    public static function segment(string $value): string
    {
        return Str::of($value)
            ->replace(['-', ' '], '_')
            ->snake()
            ->toString();
    }
}
