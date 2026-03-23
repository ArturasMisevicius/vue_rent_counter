<?php

namespace Database\Seeders;

use App\Models\Translation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class TranslationSeeder extends Seeder
{
    public function run(): void
    {
        foreach (array_keys((array) config('app.supported_locales', [])) as $locale) {
            $path = lang_path($locale);
            if (! is_dir($path)) {
                continue;
            }

            $this->syncLocale($locale);
        }
    }

    protected function syncLocale(string $locale): void
    {
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
                $values[$locale] = $value;

                $translation->fill([
                    'values' => $values,
                ]);

                $translation->save();
            }
        }
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
