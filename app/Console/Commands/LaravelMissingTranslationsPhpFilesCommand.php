<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Localization\PhpFileMissingTranslationsScanner;
use MohamedSaid\LaravelMissingTranslations\Commands\LaravelMissingTranslationsCommand as BaseLaravelMissingTranslationsCommand;
use MohamedSaid\LaravelMissingTranslations\LaravelMissingTranslations;

class LaravelMissingTranslationsPhpFilesCommand extends BaseLaravelMissingTranslationsCommand
{
    public $signature = 'missing-translations
                        {locale? : The locale to process (e.g. en, es)}
                        {--dry-run : Show missing keys without writing to PHP translation files}
                        {--all : Process all existing locale directories}
                        {--remove-unused : Remove keys that exist in PHP translation files but are not found in any scanned file}
                        {--json : Output results as JSON to stdout}';

    public $description = 'Scan project files and append missing translation keys to locale translation files';

    public function handle(LaravelMissingTranslations $scanner): int
    {
        $locales = $this->resolveLocales();

        if ($locales === []) {
            $this->error(__('No locale specified. Provide a locale argument or use --all.'));

            return self::FAILURE;
        }

        if ($this->option('json')) {
            return $this->handleJsonOutput($scanner, $locales);
        }

        foreach ($locales as $locale) {
            $this->processLocale($scanner, $locale);
        }

        return self::SUCCESS;
    }

    private function resolveLocales(): array
    {
        if (! $this->option('all')) {
            $locale = $this->argument('locale') ?? config('app.locale');

            return $locale === null || $locale === '' ? [] : [$locale];
        }

        if (! $this->usesPhpFiles()) {
            $files = glob(lang_path('*.json')) ?: [];

            if ($files === []) {
                $this->error(__('No JSON locale files found. Provide a locale argument to create one.'));

                return [];
            }

            return array_map(static fn (string $file): string => pathinfo($file, PATHINFO_FILENAME), $files);
        }

        $directories = glob(lang_path('*'), GLOB_ONLYDIR) ?: [];

        if ($directories === []) {
            $this->error(__('No locale directories found. Provide a locale argument to create one.'));

            return [];
        }

        return array_values(array_filter(
            array_map('basename', $directories),
            static fn (string $directory): bool => $directory !== 'vendor',
        ));
    }

    private function processLocale(LaravelMissingTranslations $scanner, string $locale): void
    {
        $this->info(__('Scanning for missing translations in locale: :locale', ['locale' => $locale]));

        $this->output->progressStart();
        $missingKeys = $scanner->getMissingKeys($locale);
        $this->output->progressFinish();

        $existingKeys = $this->existingKeys($scanner, $locale);
        $unusedKeys = $this->option('remove-unused') ? $scanner->getUnusedKeys($locale) : [];

        if ($missingKeys !== []) {
            $this->table([__('Missing Key')], array_map(static fn (string $key): array => [$key], $missingKeys));
        }

        if ($this->option('remove-unused') && $unusedKeys !== []) {
            $this->table([__('Unused Key')], array_map(static fn (string $key): array => [$key], $unusedKeys));
        }

        $allScannedKeys = array_unique([...$existingKeys, ...$missingKeys]);

        $this->line(__('Keys scanned: :total | Existing: :existing | Missing: :missing', [
            'total' => count($allScannedKeys),
            'existing' => count($existingKeys),
            'missing' => count($missingKeys),
        ]));

        if ($this->option('remove-unused')) {
            $this->line(__('Unused keys: :count', ['count' => count($unusedKeys)]));
        }

        if ($this->option('dry-run')) {
            $this->warn(__('Dry run mode: no changes written.'));

            return;
        }

        if ($missingKeys !== []) {
            $written = $scanner->writeToJson($locale, $missingKeys);

            $this->info(__('Written :count new key(s) to :target.', [
                'count' => $written,
                'target' => $this->targetPath($locale),
            ]));
        } else {
            $this->info(__('No missing translations found for locale: :locale', ['locale' => $locale]));
        }

        if ($this->option('remove-unused') && $unusedKeys !== []) {
            $removed = $scanner->removeKeys($locale, $unusedKeys);

            $this->info(__('Removed :count unused key(s) from :target.', [
                'count' => $removed,
                'target' => $this->targetPath($locale),
            ]));
        }
    }

    private function handleJsonOutput(LaravelMissingTranslations $scanner, array $locales): int
    {
        $result = [];

        foreach ($locales as $locale) {
            $missingKeys = $scanner->getMissingKeys($locale);
            $unusedKeys = $this->option('remove-unused') ? $scanner->getUnusedKeys($locale) : [];

            $result[] = array_filter([
                'locale' => $locale,
                'existing_count' => count($this->existingKeys($scanner, $locale)),
                'missing_count' => count($missingKeys),
                'missing_keys' => $missingKeys,
                'unused_count' => $this->option('remove-unused') ? count($unusedKeys) : null,
                'unused_keys' => $this->option('remove-unused') ? $unusedKeys : null,
            ], static fn (mixed $value): bool => $value !== null);

            if (! $this->option('dry-run') && $missingKeys !== []) {
                $scanner->writeToJson($locale, $missingKeys);
            }

            if ($this->option('remove-unused') && ! $this->option('dry-run') && $unusedKeys !== []) {
                $scanner->removeKeys($locale, $unusedKeys);
            }
        }

        $this->line(json_encode(count($result) === 1 ? $result[0] : $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }

    private function existingKeys(LaravelMissingTranslations $scanner, string $locale): array
    {
        if (! $scanner instanceof PhpFileMissingTranslationsScanner) {
            return [];
        }

        return array_keys($scanner->existingKeys($locale));
    }

    private function targetPath(string $locale): string
    {
        if (! $this->usesPhpFiles()) {
            return 'lang/'.$locale.'.json';
        }

        return 'lang/'.$locale.'/*.php';
    }

    private function usesPhpFiles(): bool
    {
        return config('laravel-missing-translations.translation_storage', 'json') === 'php';
    }
}
