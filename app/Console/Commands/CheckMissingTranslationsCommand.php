<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Attributes\AsCommand;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use SplFileInfo;

#[AsCommand(name: 'lang:missing', description: 'List missing translation keys per locale')]
final class CheckMissingTranslationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lang:missing {--locale=* : Limit to specific locale codes}';

    /**
     * Execute the console command.
     */
    public function handle(Filesystem $files): int
    {
        $availableLocales = array_keys(config('locales.available', []));
        if (empty($availableLocales)) {
            $this->error('No locales configured in config/locales.php');
            return self::FAILURE;
        }

        $limitTo = collect($this->option('locale') ?? [])->filter()->all();
        if ($limitTo) {
            $availableLocales = array_values(array_intersect($availableLocales, $limitTo));
        }

        $usedKeys = $this->findUsedTranslationKeys();
        if ($usedKeys->isEmpty()) {
            $this->warn('No translation keys found in codebase.');
            return self::SUCCESS;
        }

        $langPath = lang_path();
        $allTranslations = $this->loadTranslations($files, $langPath, $availableLocales);

        foreach ($availableLocales as $locale) {
            $existing = $allTranslations[$locale] ?? collect();
            $missing = $usedKeys->diff($existing->keys());

            $this->line('');
            $this->info("Locale: {$locale}");
            $this->line(" - Used keys: {$usedKeys->count()}");
            $this->line(" - Present : {$existing->count()}");
            $this->line(" - Missing : {$missing->count()}");

            if ($missing->isNotEmpty()) {
                $this->warn('Missing keys:');
                foreach ($missing->sort() as $key) {
                    $this->line("  • {$key}");
                }
            }
        }

        return self::SUCCESS;
    }

    /**
     * Find translation keys used in app/ and resources/ (PHP + Blade).
     */
    private function findUsedTranslationKeys(): Collection
    {
        $paths = [app_path(), resource_path()];
        $regex = '/(?:__|@lang|@choice|trans)\(\s*[\'"]([^\'"()]+)[\'"]/m';
        $keys = collect();

        foreach ($paths as $path) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
            );

            /** @var SplFileInfo $file */
            foreach ($iterator as $file) {
                if (! $file->isFile()) {
                    continue;
                }

                if (! str_ends_with($file->getFilename(), '.php') && ! str_ends_with($file->getFilename(), '.blade.php')) {
                    continue;
                }

                $contents = @file_get_contents($file->getPathname());
                if ($contents === false) {
                    continue;
                }

                if (preg_match_all($regex, $contents, $matches)) {
                    $keys = $keys->merge($matches[1]);
                }
            }
        }

        return $keys->unique()->values();
    }

    /**
     * Load translations from lang directory (including namespaced backup/).
     */
    private function loadTranslations(Filesystem $files, string $langPath, array $locales): array
    {
        $result = [];

        // Standard lang/{locale}/*
        foreach ($locales as $locale) {
            $base = "{$langPath}/{$locale}";
            $result[$locale] = $this->loadLocaleFiles($files, $base, prefix: '');
        }

        // Namespaced: lang/{namespace}/{locale}/*
        foreach ($files->directories($langPath) as $directory) {
            $namespace = basename($directory);
            if (in_array($namespace, $locales, true)) {
                continue; // already handled standard locale folder
            }

            foreach ($locales as $locale) {
                $base = "{$directory}/{$locale}";
                if (! $files->isDirectory($base)) {
                    continue;
                }

                $existing = $result[$locale] ?? collect();
                $namespaced = $this->loadLocaleFiles($files, $base, prefix: "{$namespace}::");
                $result[$locale] = $existing->merge($namespaced);
            }
        }

        return $result;
    }

    /**
     * Load and flatten all translation arrays for a locale folder.
     */
    private function loadLocaleFiles(Filesystem $files, string $basePath, string $prefix): Collection
    {
        if (! $files->isDirectory($basePath)) {
            return collect();
        }

        $translations = collect();

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($basePath, \FilesystemIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relative = trim(str_replace('\\', '/', substr($file->getPathname(), strlen($basePath))), '/');
            $group = $prefix . str_replace('/', '.', substr($relative, 0, -4)); // drop .php

            $array = include $file->getPathname();
            if (! is_array($array)) {
                continue;
            }

            $this->flattenArray($array, $group, $translations);
        }

        return $translations;
    }

    /**
     * Flatten translation array into dot notation.
     */
    private function flattenArray(array $data, string $prefix, Collection &$target): void
    {
        foreach ($data as $key => $value) {
            $full = $prefix === '' ? (string) $key : "{$prefix}.{$key}";
            if (is_array($value)) {
                $this->flattenArray($value, $full, $target);
            } else {
                $target->put($full, $value);
            }
        }
    }
}
