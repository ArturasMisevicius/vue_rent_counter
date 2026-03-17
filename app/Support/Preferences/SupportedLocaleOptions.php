<?php

namespace App\Support\Preferences;

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

        foreach (array_keys($supportedLocales) as $locale) {
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
}
