<?php

namespace App\Filament\Support\Superadmin\Translations;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class TranslationCatalogService
{
    public function __construct(
        private readonly ?string $basePath = null,
    ) {}

    /**
     * @return Collection<int, TranslationRowData>
     */
    public function rows(): Collection
    {
        $groups = collect($this->localeDirectories())
            ->flatMap(fn (string $locale): array => array_map(
                fn (string $file): array => [$locale, $file],
                glob($this->path($locale).'/*.php') ?: [],
            ))
            ->mapWithKeys(function (array $pair): array {
                [$locale, $file] = $pair;
                $group = basename($file, '.php');

                return [$group => true];
            })
            ->keys();

        return $groups
            ->flatMap(function (string $group): Collection {
                $valuesByLocale = collect($this->localeDirectories())
                    ->mapWithKeys(fn (string $locale): array => [$locale => $this->flatten($this->load($locale, $group))]);

                return collect($valuesByLocale->flatMap(fn (array $values): array => array_keys($values))->unique())
                    ->sort()
                    ->values()
                    ->map(fn (string $key): TranslationRowData => new TranslationRowData(
                        group: $group,
                        key: $key,
                        values: $valuesByLocale->map(fn (array $values): ?string => $values[$key] ?? null)->all(),
                    ));
            })
            ->values();
    }

    public function updateValue(string $group, string $key, string $locale, string $value): void
    {
        $translations = $this->load($locale, $group);
        Arr::set($translations, $key, $value);
        $this->write($locale, $group, $translations);
    }

    public function importCsv(string $path): void
    {
        $rows = array_map('str_getcsv', file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: []);

        foreach (array_slice($rows, 1) as $row) {
            [$group, $key, $locale, $value] = array_pad($row, 4, '');
            $this->updateValue($group, $key, $locale, $value);
        }
    }

    public function exportMissing(?string $locale = null): string
    {
        $targetLocales = $locale === null ? $this->localeDirectories() : [$locale];
        $path = storage_path('app/exports/missing-translations-'.now()->timestamp.'.csv');

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        $handle = fopen($path, 'w');
        fputcsv($handle, ['group', 'key', 'locale']);

        foreach ($this->rows() as $row) {
            foreach ($targetLocales as $targetLocale) {
                if (blank($row->values[$targetLocale] ?? null)) {
                    fputcsv($handle, [$row->group, $row->key, $targetLocale]);
                }
            }
        }

        fclose($handle);

        return $path;
    }

    /**
     * @return list<string>
     */
    protected function localeDirectories(): array
    {
        $directories = glob($this->root().'/*', GLOB_ONLYDIR) ?: [];

        return collect($directories)
            ->map(fn (string $directory): string => basename($directory))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function load(string $locale, string $group): array
    {
        $file = $this->path($locale).'/'.$group.'.php';

        if (! file_exists($file)) {
            return [];
        }

        /** @var array<string, mixed> $translations */
        $translations = require $file;

        return $translations;
    }

    /**
     * @param  array<string, mixed>  $translations
     */
    protected function write(string $locale, string $group, array $translations): void
    {
        $directory = $this->path($locale);

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $payload = "<?php\n\nreturn ".var_export($translations, true).";\n";

        file_put_contents($directory.'/'.$group.'.php', $payload);
    }

    /**
     * @param  array<string, mixed>  $translations
     * @return array<string, string>
     */
    protected function flatten(array $translations): array
    {
        return collect($translations)
            ->flatMap(fn (mixed $value, string|int $key): array => $this->flattenValue($value, (string) $key))
            ->all();
    }

    /**
     * @return array<string, string>
     */
    protected function flattenValue(mixed $value, string $key): array
    {
        if (is_array($value)) {
            return collect($value)
                ->flatMap(fn (mixed $nestedValue, string|int $nestedKey): array => $this->flattenValue(
                    $nestedValue,
                    $key.'.'.$nestedKey,
                ))
                ->all();
        }

        if (is_bool($value)) {
            return [$key => $value ? 'true' : 'false'];
        }

        if ($value === null) {
            return [$key => ''];
        }

        return [$key => (string) $value];
    }

    protected function root(): string
    {
        return $this->basePath ?? lang_path();
    }

    protected function path(string $locale): string
    {
        return $this->root().'/'.$locale;
    }
}
