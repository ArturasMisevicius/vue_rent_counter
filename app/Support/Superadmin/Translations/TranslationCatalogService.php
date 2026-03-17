<?php

namespace App\Support\Superadmin\Translations;

use App\Enums\LanguageStatus;
use App\Models\Language;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class TranslationCatalogService
{
    /**
     * @return Collection<int, array{code: string, label: string, is_default: bool}>
     */
    public function activeLocales(): Collection
    {
        if (Schema::hasTable('languages') && Language::query()->exists()) {
            return Language::query()
                ->select([
                    'code',
                    'native_name',
                    'is_default',
                ])
                ->where('status', LanguageStatus::ACTIVE)
                ->orderByDesc('is_default')
                ->orderBy('native_name')
                ->get()
                ->map(fn (Language $language): array => [
                    'code' => $language->code,
                    'label' => $language->native_name,
                    'is_default' => $language->is_default,
                ])
                ->values();
        }

        return collect(config('tenanto.locales', []))
            ->map(function (array $locale, string $code): array {
                return [
                    'code' => $code,
                    'label' => (string) ($locale['native_name'] ?? $code),
                    'is_default' => $code === $this->defaultLocale(),
                ];
            })
            ->values();
    }

    public function defaultLocale(): string
    {
        if (Schema::hasTable('languages')) {
            $locale = Language::query()
                ->where('status', LanguageStatus::ACTIVE)
                ->where('is_default', true)
                ->value('code');

            if (is_string($locale) && filled($locale)) {
                return $locale;
            }
        }

        return (string) config('tenanto.localization.fallback_locale', config('app.fallback_locale', 'en'));
    }

    public function initialEditableLocale(): string
    {
        $nonDefault = $this->activeLocales()
            ->first(fn (array $locale): bool => ! $locale['is_default']);

        return $nonDefault['code'] ?? $this->defaultLocale();
    }

    /**
     * @return array<int, string>
     */
    public function groups(?string $locale = null): array
    {
        $locale ??= $this->defaultLocale();
        $directory = $this->localeDirectory($locale);

        if (! File::isDirectory($directory)) {
            return [];
        }

        $files = glob($directory.'/*.php') ?: [];

        return collect($files)
            ->map(fn (string $file): string => pathinfo($file, PATHINFO_FILENAME))
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, TranslationRowData>
     */
    public function rows(string $locale, string $group): Collection
    {
        $sourceTranslations = Arr::dot($this->loadGroup($this->defaultLocale(), $group));
        $targetTranslations = Arr::dot($this->loadGroup($locale, $group));

        return collect(array_keys($sourceTranslations))
            ->merge(array_keys($targetTranslations))
            ->unique()
            ->sort()
            ->values()
            ->map(function (string $key) use ($sourceTranslations, $targetTranslations): TranslationRowData {
                return new TranslationRowData(
                    key: $key,
                    stateKey: $this->stateKey($key),
                    sourceValue: (string) ($sourceTranslations[$key] ?? ''),
                    translatedValue: array_key_exists($key, $targetTranslations)
                        ? (string) $targetTranslations[$key]
                        : '',
                    missing: ! array_key_exists($key, $targetTranslations),
                );
            });
    }

    public function updateValue(string $locale, string $group, string $key, string $value): void
    {
        $translations = $this->loadGroup($locale, $group);

        Arr::set($translations, $key, $value);

        $this->writeGroup($locale, $group, $translations);
    }

    public function exportMissingTranslations(string $locale, string $group): string
    {
        $directory = $this->exportDirectory();
        $path = $directory."/{$locale}-{$group}-missing.php";

        File::ensureDirectoryExists($directory);
        File::put($path, $this->serializePhpArray($this->missingTranslations($locale, $group)));

        return $path;
    }

    public function importFromFile(string $locale, string $group, string $path): int
    {
        if (! File::exists($path)) {
            return 0;
        }

        /** @var array<string, mixed> $importedTranslations */
        $importedTranslations = require $path;

        $translations = array_replace_recursive(
            $this->loadGroup($locale, $group),
            $importedTranslations,
        );

        $this->writeGroup($locale, $group, $translations);

        return count(Arr::dot($importedTranslations));
    }

    /**
     * @return array<string, mixed>
     */
    private function missingTranslations(string $locale, string $group): array
    {
        $sourceTranslations = Arr::dot($this->loadGroup($this->defaultLocale(), $group));
        $targetTranslations = Arr::dot($this->loadGroup($locale, $group));
        $missingTranslations = [];

        foreach ($sourceTranslations as $key => $value) {
            if (array_key_exists($key, $targetTranslations)) {
                continue;
            }

            Arr::set($missingTranslations, $key, $value);
        }

        return $missingTranslations;
    }

    /**
     * @return array<string, mixed>
     */
    private function loadGroup(string $locale, string $group): array
    {
        $path = $this->localeDirectory($locale)."/{$group}.php";

        if (! File::exists($path)) {
            return [];
        }

        /** @var array<string, mixed> $translations */
        $translations = require $path;

        return $translations;
    }

    /**
     * @param  array<string, mixed>  $translations
     */
    private function writeGroup(string $locale, string $group, array $translations): void
    {
        $directory = $this->localeDirectory($locale);

        File::ensureDirectoryExists($directory);
        File::put("{$directory}/{$group}.php", $this->serializePhpArray($translations));
    }

    private function localeDirectory(string $locale): string
    {
        return $this->translationRoot()."/{$locale}";
    }

    private function translationRoot(): string
    {
        $path = (string) config('tenanto.localization.translation_sources.php_array_files', 'lang');

        return str_starts_with($path, DIRECTORY_SEPARATOR)
            ? $path
            : base_path($path);
    }

    private function exportDirectory(): string
    {
        $path = (string) config('tenanto.localization.translation_exports_directory', 'storage/app/private/translation-exports');

        return str_starts_with($path, DIRECTORY_SEPARATOR)
            ? $path
            : base_path($path);
    }

    private function stateKey(string $key): string
    {
        return str_replace('.', '__dot__', $key);
    }

    /**
     * @param  array<string, mixed>  $translations
     */
    private function serializePhpArray(array $translations): string
    {
        return "<?php\n\nreturn ".var_export($translations, true).";\n";
    }
}
