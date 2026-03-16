<?php

declare(strict_types=1);

use Illuminate\Support\Arr;

function projectRoot(): string
{
    return dirname(__DIR__, 3);
}

function enabledLocaleCodes(): array
{
    /** @var array{available?: array<string, array<string, mixed>>} $settings */
    $settings = require projectRoot() . '/lang/locales.php';

    return array_keys((array) ($settings['available'] ?? []));
}

function fallbackLocaleCode(): string
{
    /** @var array{fallback?: string} $settings */
    $settings = require projectRoot() . '/lang/locales.php';

    return (string) ($settings['fallback'] ?? 'en');
}

function translationGroupFilesForLocale(string $locale): array
{
    $directory = projectRoot() . "/lang/{$locale}";

    if (! is_dir($directory)) {
        return [];
    }

    return collect(glob($directory . '/*.php') ?: [])
        ->map(fn (string $path): string => basename($path))
        ->filter(fn (string $filename): bool => str_ends_with($filename, '.php'))
        ->map(fn (string $filename): string => pathinfo($filename, PATHINFO_FILENAME))
        ->sort()
        ->values()
        ->all();
}

function flattenedTranslationKeysFor(string $locale, string $group): array
{
    $path = projectRoot() . "/lang/{$locale}/{$group}.php";

    expect(file_exists($path))->toBeTrue();

    $translations = require $path;

    expect($translations)->toBeArray();

    return collect(Arr::dot($translations))
        ->keys()
        ->sort()
        ->values()
        ->all();
}

it('has a lang directory for every enabled locale', function (): void {
    foreach (enabledLocaleCodes() as $locale) {
        expect(is_dir(projectRoot() . "/lang/{$locale}"))
            ->toBeTrue();
    }
});

it('keeps the same translation file set for every enabled locale as the fallback locale', function (string $locale): void {
    expect(translationGroupFilesForLocale($locale))
        ->toBe(translationGroupFilesForLocale(fallbackLocaleCode()));
})->with(fn (): array => array_values(array_filter(
    enabledLocaleCodes(),
    fn (string $locale): bool => $locale !== fallbackLocaleCode(),
)));

it('loads every enabled locale translation file as an array', function (string $locale, string $group): void {
    expect(flattenedTranslationKeysFor($locale, $group))
        ->toBeArray();
})->with(function (): array {
    $fallbackLocale = fallbackLocaleCode();
    $locales = enabledLocaleCodes();

    $groups = translationGroupFilesForLocale($fallbackLocale);
    $dataset = [];

    foreach ($locales as $locale) {
        foreach ($groups as $group) {
            $dataset["{$locale}:{$group}"] = [$locale, $group];
        }
    }

    return $dataset;
});
