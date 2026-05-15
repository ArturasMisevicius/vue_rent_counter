<?php

it('contains every English key in all configured locales', function (): void {
    $expected = flattenLocaleFiles('en');

    foreach (['en', 'lt', 'ru', 'es'] as $locale) {
        $translations = flattenLocaleFiles($locale);

        foreach ($expected as $key => $_) {
            expect(array_key_exists($key, $translations))->toBeTrue("Missing key [{$key}] in locale [{$locale}]");

            $value = $translations[$key];
            expect(is_string($value) ? trim($value) : (string) $value)
                ->toBeString()
                ->not->toBe('');
        }
    }
});

it('includes every enum value across locales', function (): void {
    $locales = ['en', 'lt', 'ru', 'es'];
    $enumFiles = glob(app_path('Enums/*.php')) ?: [];

    foreach ($enumFiles as $enumFile) {
        $class = 'App\\Enums\\'.basename($enumFile, '.php');
        if (! enum_exists($class) || ! method_exists($class, 'translationKeyPrefix')) {
            continue;
        }

        /** @var class-string<BackedEnum> $class */
        $values = $class::cases();
        $baseKey = $class::translationKeyPrefix();
        $baseKey = str_replace('enums.', '', $baseKey);

        foreach ($locales as $locale) {
            $enums = include lang_path("{$locale}/enums.php");
            expect(array_key_exists($baseKey, $enums))->toBeTrue("Missing enum group [{$baseKey}] in locale [{$locale}]");

            foreach ($values as $case) {
                expect(array_key_exists($case->value, $enums[$baseKey]))->toBeTrue("Missing enum key [{$baseKey}.{$case->value}] for locale [{$locale}]");
            }
        }
    }
});

it('returns Spanish strings from locale-specific files', function (): void {
    app()->setLocale('es');

    expect(__('invoices.admin.navigation'))
        ->toBe('Facturas');
});

it('does not leave exact English application strings in localized files', function (): void {
    $english = flattenLocaleFiles('en');
    $allowedExactValues = [
        ':currency :amount',
        ':type :unit',
        ':value ms',
        'Admin',
        'Android',
        'API',
        'Chrome',
        'Color',
        'CSV',
        'curl',
        'Edge',
        'Email',
        'Firefox',
        'Gas',
        'IBAN',
        'ID',
        'Internet',
        'iOS',
        'KYC Profile',
        'KYC Profiles',
        'kWh',
        'Linux',
        'Mac',
        'Manual',
        'MRR',
        'MWh',
        'name',
        'OAuth',
        'PDF',
        'Plan',
        'reference',
        'Safari',
        'Slug',
        'SMS',
        'Superadmin',
        'SWIFT / BIC',
        'Token',
        'Total',
        'true',
        'false',
        'URL',
        'Windows',
    ];

    foreach (['lt', 'ru', 'es'] as $locale) {
        $localized = flattenLocaleFiles($locale);
        $untranslated = [];

        foreach ($english as $key => $englishValue) {
            $localizedValue = $localized[$key] ?? null;

            if (
                is_string($localizedValue)
                && $localizedValue === $englishValue
                && preg_match('/[A-Za-z]{3,}/', $englishValue) === 1
                && ! in_array($englishValue, $allowedExactValues, true)
            ) {
                $untranslated[$key] = $englishValue;
            }
        }

        expect($untranslated)
            ->toBe([], 'Untranslated exact English values in '.$locale.': '.json_encode($untranslated, JSON_UNESCAPED_UNICODE));
    }
});

/**
 * @return array<string, string>
 */
function flattenLocaleFiles(string $locale): array
{
    $payload = [];

    foreach (glob(lang_path("{$locale}/*.php")) as $file) {
        $group = basename((string) $file, '.php');
        /** @var array<string, mixed> $translations */
        $translations = include $file;
        $payload = array_merge($payload, flattenTranslationsForGroup($translations, $group));
    }

    return $payload;
}

/**
 * @param  array<string, mixed>  $translations
 * @return array<string, string>
 */
function flattenTranslationsForGroup(array $translations, string $group, string $prefix = ''): array
{
    $result = [];

    foreach ($translations as $key => $value) {
        $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;
        if (is_array($value)) {
            $result = array_merge($result, flattenTranslationsForGroup($value, $group, $path));

            continue;
        }

        $result[$group.'.'.$path] = is_string($value) ? $value : (string) $value;
    }

    return $result;
}
