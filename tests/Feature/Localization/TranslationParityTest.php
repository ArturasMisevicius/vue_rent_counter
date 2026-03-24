<?php

declare(strict_types=1);

it('keeps baseline english translation files available for every supported locale', function () {
    $basePath = lang_path();
    $supportedLocales = array_keys(config('app.supported_locales', []));
    $baselineFiles = collect(glob($basePath.'/en/*.php') ?: [])
        ->map(static fn (string $path): string => basename($path))
        ->values()
        ->all();

    foreach ($supportedLocales as $locale) {
        foreach ($baselineFiles as $file) {
            expect(is_file($basePath.'/'.$locale.'/'.$file))
                ->toBeTrue("Missing {$file} in locale {$locale}");
        }
    }
});

it('keeps baseline english translation keys present for every supported locale', function () {
    $basePath = lang_path();
    $supportedLocales = array_keys(config('app.supported_locales', []));
    $baselineFiles = collect(glob($basePath.'/en/*.php') ?: [])
        ->map(static fn (string $path): string => basename($path))
        ->values()
        ->all();

    foreach ($baselineFiles as $file) {
        $english = require $basePath.'/en/'.$file;
        $englishKeys = flattenTranslationKeys($english);

        foreach ($supportedLocales as $locale) {
            $localized = require $basePath.'/'.$locale.'/'.$file;
            $localizedKeys = flattenTranslationKeys($localized);

            $missingKeys = array_values(array_diff($englishKeys, $localizedKeys));

            expect($missingKeys)
                ->toBe([], "Missing translation keys in {$locale}/{$file}: ".implode(', ', $missingKeys));
        }
    }
});

function flattenTranslationKeys(array $translations, string $prefix = ''): array
{
    $keys = [];

    foreach ($translations as $key => $value) {
        $composedKey = $prefix === '' ? (string) $key : $prefix.'.'.(string) $key;

        if (is_array($value)) {
            $keys = [
                ...$keys,
                ...flattenTranslationKeys($value, $composedKey),
            ];

            continue;
        }

        $keys[] = $composedKey;
    }

    return array_values(array_unique($keys));
}
