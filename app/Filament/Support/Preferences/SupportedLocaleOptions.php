<?php

namespace App\Filament\Support\Preferences;

use App\Models\Language;
use Illuminate\Support\Facades\Schema;

class SupportedLocaleOptions
{
    /**
     * @return array<string, string>
     */
    public function labels(): array
    {
        $supportedLocales = config('app.supported_locales', []);
        $configuredLabels = config('tenanto.locales', []);
        $labels = [];
        $activeLanguageCodes = $this->activeLanguageCodes();

        $locales = $activeLanguageCodes !== []
            ? $activeLanguageCodes
            : array_keys($supportedLocales);

        foreach ($locales as $locale) {
            $labels[$locale] = $configuredLabels[$locale]
                ?? (is_string($supportedLocales[$locale] ?? null) ? $supportedLocales[$locale] : strtoupper($locale));
        }

        return $labels;
    }

    /**
     * @return list<string>
     */
    public function codes(): array
    {
        return array_keys($this->labels());
    }

    public function fallbackLocale(): string
    {
        $codes = $this->codes();

        if ($codes === []) {
            return 'en';
        }

        $defaultLocale = $this->defaultActiveLocale();

        if ($defaultLocale !== null && in_array($defaultLocale, $codes, true)) {
            return $defaultLocale;
        }

        $fallbackLocale = config('app.fallback_locale', 'en');

        if (is_string($fallbackLocale) && in_array($fallbackLocale, $codes, true)) {
            return $fallbackLocale;
        }

        return $codes[0];
    }

    /**
     * @return list<string>
     */
    private function activeLanguageCodes(): array
    {
        if (! Schema::hasTable('languages')) {
            return [];
        }

        $codes = Language::query()
            ->active()
            ->orderByDesc('is_default')
            ->pluck('code')
            ->filter(fn (mixed $code): bool => is_string($code) && $code !== '')
            ->values()
            ->all();

        return $codes;
    }

    private function defaultActiveLocale(): ?string
    {
        if (! Schema::hasTable('languages')) {
            return null;
        }

        $defaultLocale = Language::query()
            ->active()
            ->where('is_default', true)
            ->value('code');

        return is_string($defaultLocale) ? $defaultLocale : null;
    }
}
