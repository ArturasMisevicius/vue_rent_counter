<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Collection;

final class Localization
{
    /**
     * Get all available locales from configuration.
     */
    public static function availableLocales(): Collection
    {
        return collect(config('locales.available', []))
            ->map(fn (array $config, string $code) => [
                'code' => $code,
                'label' => __($config['label']),
                'abbreviation' => $config['abbreviation'],
            ]);
    }

    /**
     * Get the fallback locale.
     */
    public static function fallbackLocale(): string
    {
        return config('locales.fallback', 'en');
    }

    /**
     * Get the current locale.
     */
    public static function currentLocale(): string
    {
        return app()->getLocale();
    }

    /**
     * Check if a locale is available.
     */
    public static function isAvailable(string $locale): bool
    {
        return array_key_exists($locale, config('locales.available', []));
    }

    /**
     * Get locale configuration.
     */
    public static function getLocaleConfig(string $locale): ?array
    {
        return config("locales.available.{$locale}");
    }
}