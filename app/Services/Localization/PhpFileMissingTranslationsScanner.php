<?php

declare(strict_types=1);

namespace App\Services\Localization;

use Illuminate\Support\Arr;
use MohamedSaid\LaravelMissingTranslations\LaravelMissingTranslations;

class PhpFileMissingTranslationsScanner extends LaravelMissingTranslations
{
    public function getMissingKeys(string $locale): array
    {
        if (! $this->usesPhpFiles()) {
            return parent::getMissingKeys($locale);
        }

        $existingKeys = $this->existingKeys($locale);
        $excludePatterns = config('laravel-missing-translations.exclude_patterns', []);
        $missingKeys = [];

        foreach ($this->scan() as $key) {
            if (! $this->isStaticPhpTranslationKey($key)) {
                continue;
            }

            if ($this->pointsToArrayContainer($key)) {
                continue;
            }

            if (array_key_exists($key, $existingKeys)) {
                continue;
            }

            if ($this->matchesExcludedPattern($key, $excludePatterns)) {
                continue;
            }

            $missingKeys[] = $key;
        }

        return $missingKeys;
    }

    public function getUnusedKeys(string $locale): array
    {
        if (! $this->usesPhpFiles()) {
            return parent::getUnusedKeys($locale);
        }

        $scannedKeys = array_values(array_filter(
            $this->scan(),
            fn (string $key): bool => $this->isStaticPhpTranslationKey($key) && ! $this->pointsToArrayContainer($key),
        ));

        return array_values(array_diff(array_keys($this->existingKeys($locale)), $scannedKeys));
    }

    public function writeToJson(string $locale, array $missingKeys): int
    {
        if (! $this->usesPhpFiles()) {
            return parent::writeToJson($locale, $missingKeys);
        }

        $writtenCount = 0;

        foreach ($this->groupedKeys($missingKeys) as $group => $groupKeys) {
            $translations = $this->loadGroupFile($locale, $group);

            foreach ($groupKeys as $key) {
                if (Arr::has($translations, $key)) {
                    continue;
                }

                Arr::set($translations, $key, $this->placeholderValue($group, $key));
                $writtenCount++;
            }

            $this->writeGroupFile($locale, $group, $translations);
        }

        return $writtenCount;
    }

    public function removeKeys(string $locale, array $keys): int
    {
        if (! $this->usesPhpFiles()) {
            return parent::removeKeys($locale, $keys);
        }

        $removedCount = 0;

        foreach ($this->groupedKeys($keys) as $group => $groupKeys) {
            $translations = $this->loadGroupFile($locale, $group);

            foreach ($groupKeys as $key) {
                if (! Arr::has($translations, $key)) {
                    continue;
                }

                Arr::forget($translations, $key);
                $removedCount++;
            }

            $translations = $this->pruneEmptyArrays($translations);

            if ($translations === []) {
                $filePath = $this->groupFilePath($locale, $group);

                if (file_exists($filePath)) {
                    unlink($filePath);
                }

                continue;
            }

            $this->writeGroupFile($locale, $group, $translations);
        }

        return $removedCount;
    }

    public function existingKeys(string $locale): array
    {
        if (! $this->usesPhpFiles()) {
            return $this->existingJsonKeys($locale);
        }

        $existingKeys = [];

        foreach (glob(lang_path($locale.'/*.php')) ?: [] as $filePath) {
            $this->appendExistingGroupKeys($existingKeys, $locale, basename($filePath, '.php'));
        }

        foreach (glob(lang_path('vendor/*/'.$locale.'/*.php')) ?: [] as $filePath) {
            $namespace = basename(dirname(dirname($filePath)));
            $group = $namespace.'::'.basename($filePath, '.php');

            $this->appendExistingGroupKeys($existingKeys, $locale, $group);
        }

        return $existingKeys;
    }

    private function existingJsonKeys(string $locale): array
    {
        $filePath = lang_path($locale.'.json');

        if (! file_exists($filePath)) {
            return [];
        }

        return json_decode((string) file_get_contents($filePath), true) ?? [];
    }

    private function groupedKeys(array $keys): array
    {
        $groupedKeys = [];

        foreach ($keys as $key) {
            if (! $this->isStaticPhpTranslationKey($key)) {
                continue;
            }

            [$group, $nestedKey] = explode('.', $key, 2);
            $groupedKeys[$group][] = $nestedKey;
        }

        return $groupedKeys;
    }

    private function loadGroupFile(string $locale, string $group): array
    {
        $filePath = $this->groupFilePath($locale, $group);

        if (! file_exists($filePath)) {
            return [];
        }

        $translations = require $filePath;

        return is_array($translations) ? $translations : [];
    }

    private function writeGroupFile(string $locale, string $group, array $translations): void
    {
        $filePath = $this->groupFilePath($locale, $group);
        $directory = dirname($filePath);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        ksort($translations);

        file_put_contents(
            $filePath,
            "<?php\n\ndeclare(strict_types=1);\n\nreturn ".$this->renderPhpArray($translations).";\n",
            LOCK_EX,
        );
    }

    private function appendExistingGroupKeys(array &$existingKeys, string $locale, string $group): void
    {
        foreach ($this->flatten($this->loadGroupFile($locale, $group)) as $key => $value) {
            $existingKeys[$group.'.'.$key] = $value;
        }
    }

    private function renderPhpArray(array $values, int $depth = 0): string
    {
        if ($values === []) {
            return '[]';
        }

        $indent = str_repeat('    ', $depth);
        $nestedIndent = str_repeat('    ', $depth + 1);
        $lines = [];

        foreach ($values as $key => $value) {
            $renderedKey = var_export($key, true);
            $renderedValue = is_array($value)
                ? $this->renderPhpArray($value, $depth + 1)
                : var_export($value, true);

            $lines[] = $nestedIndent.$renderedKey.' => '.$renderedValue.',';
        }

        return "[\n".implode("\n", $lines)."\n{$indent}]";
    }

    private function flatten(array $translations, string $prefix = ''): array
    {
        $flattened = [];

        foreach ($translations as $key => $value) {
            $composedKey = $prefix === '' ? (string) $key : $prefix.'.'.(string) $key;

            if (is_array($value)) {
                $flattened = [
                    ...$flattened,
                    ...$this->flatten($value, $composedKey),
                ];

                continue;
            }

            if (is_bool($value)) {
                $flattened[$composedKey] = $value ? 'true' : 'false';

                continue;
            }

            if ($value === null) {
                $flattened[$composedKey] = '';

                continue;
            }

            $flattened[$composedKey] = (string) $value;
        }

        return $flattened;
    }

    private function pruneEmptyArrays(array $translations): array
    {
        $pruned = [];

        foreach ($translations as $key => $value) {
            if (! is_array($value)) {
                $pruned[$key] = $value;

                continue;
            }

            $nestedValue = $this->pruneEmptyArrays($value);

            if ($nestedValue === []) {
                continue;
            }

            $pruned[$key] = $nestedValue;
        }

        return $pruned;
    }

    private function groupFilePath(string $locale, string $group): string
    {
        [$namespace, $file] = $this->groupSegments($group);

        if ($namespace === null) {
            return lang_path($locale.'/'.$file.'.php');
        }

        return lang_path('vendor/'.$namespace.'/'.$locale.'/'.$file.'.php');
    }

    private function matchesExcludedPattern(string $key, array $excludePatterns): bool
    {
        foreach ($excludePatterns as $pattern) {
            if (preg_match($pattern, $key) === 1) {
                return true;
            }
        }

        return false;
    }

    private function usesPhpFiles(): bool
    {
        return config('laravel-missing-translations.translation_storage', 'json') === 'php';
    }

    private function placeholderValue(string $group, string $key): string
    {
        $referenceValue = Arr::get($this->loadGroupFile($this->referenceLocale(), $group), $key);

        if (is_scalar($referenceValue) && (string) $referenceValue !== '') {
            return (string) $referenceValue;
        }

        return $group.'.'.$key;
    }

    private function pointsToArrayContainer(string $key): bool
    {
        [$group, $nestedKey] = explode('.', $key, 2);
        $referenceValue = Arr::get($this->loadGroupFile($this->referenceLocale(), $group), $nestedKey);

        return is_array($referenceValue);
    }

    private function referenceLocale(): string
    {
        return (string) config('app.fallback_locale', config('app.locale', 'en'));
    }

    private function isStaticPhpTranslationKey(mixed $key): bool
    {
        if (! is_string($key)) {
            return false;
        }

        return preg_match('/^[^\s.{}]+(?:\.[^\s.{}]+)+$/', $key) === 1;
    }

    private function groupSegments(string $group): array
    {
        if (! str_contains($group, '::')) {
            return [null, $group];
        }

        [$namespace, $file] = explode('::', $group, 2);

        return [$namespace, $file];
    }
}
