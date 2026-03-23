<?php

namespace App\Console\Commands;

use App\Models\Translation;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class SyncTranslationsCommand extends Command
{
    protected $signature = 'translations:sync {--locale=}';

    protected $description = 'Synchronize locale PHP translation files into the translations table';

    public function handle(): int
    {
        $requestedLocale = $this->option('locale');
        $locales = $this->resolveLocales($requestedLocale);

        if ($locales === []) {
            $this->warn('No locale directories found to sync.');

            return self::FAILURE;
        }

        $summary = [
            'created' => 0,
            'updated' => 0,
            'unchanged' => 0,
        ];

        foreach ($locales as $locale) {
            $localeSummary = $this->syncLocale($locale);
            $summary['created'] += $localeSummary['created'];
            $summary['updated'] += $localeSummary['updated'];
            $summary['unchanged'] += $localeSummary['unchanged'];

            $this->components->twoColumnDetail(
                "Locale {$locale}",
                "created={$localeSummary['created']}, updated={$localeSummary['updated']}, unchanged={$localeSummary['unchanged']}",
            );
        }

        $this->newLine();
        $this->components->info(sprintf(
            'Translation sync complete: created=%d, updated=%d, unchanged=%d',
            $summary['created'],
            $summary['updated'],
            $summary['unchanged'],
        ));

        return self::SUCCESS;
    }

    /**
     * @param  null|array<int, string>  $locales
     * @return array{created: int, updated: int, unchanged: int}
     */
    private function syncLocale(string $locale): array
    {
        $created = 0;
        $updated = 0;
        $unchanged = 0;

        foreach (File::files(lang_path($locale)) as $file) {
            $group = (string) pathinfo($file->getFilename(), PATHINFO_FILENAME);
            /** @var array<string, mixed> $contents */
            $contents = include $file->getRealPath();
            if (! is_array($contents)) {
                continue;
            }

            foreach ($this->flatten($contents) as $key => $value) {
                $translation = Translation::query()->firstOrNew([
                    'group' => $group,
                    'key' => $key,
                ]);

                $values = $translation->values ?? [];
                $current = data_get($values, $locale);
                $next = (string) $value;

                if (! $translation->exists) {
                    $created++;
                    $values[$locale] = $next;
                    $translation->fill([
                        'values' => $values,
                    ]);
                    $translation->save();

                    continue;
                }

                if ((string) $current !== (string) $next) {
                    $updated++;
                    $values[$locale] = $next;
                    $translation->fill([
                        'values' => $values,
                    ]);
                    $translation->save();

                    continue;
                }

                $unchanged++;
            }
        }

        return compact('created', 'updated', 'unchanged');
    }

    /**
     * @return list<string>
     */
    private function resolveLocales(?string $requestedLocale): array
    {
        $locales = Arr::where((array) config('app.supported_locales', []), fn (string $_, string $locale): bool => is_dir(lang_path($locale)));

        if ($requestedLocale !== null && trim($requestedLocale) !== '') {
            $requestedLocale = strtolower(trim($requestedLocale));
            if (! isset($locales[$requestedLocale])) {
                $this->error("Locale [{$requestedLocale}] is not configured or missing files.");

                return [];
            }

            return [$requestedLocale];
        }

        return array_keys($locales);
    }

    /**
     * @param  array<string, mixed>  $translations
     * @return array<string, string>
     */
    protected function flatten(array $translations, string $prefix = ''): array
    {
        $output = [];

        foreach ($translations as $key => $value) {
            $composed = $prefix === '' ? (string) $key : $prefix.'.'.$key;

            if (is_array($value)) {
                $output += $this->flatten($value, $composed);

                continue;
            }

            if (is_bool($value)) {
                $output[$composed] = $value ? 'true' : 'false';

                continue;
            }

            if ($value === null) {
                $output[$composed] = '';

                continue;
            }

            $output[$composed] = (string) $value;
        }

        return $output;
    }
}
