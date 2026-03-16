<?php

namespace App\Services;

use App\Models\Language;
use App\Models\Translation;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;

class TranslationPublisher
{
    public function __construct(
        private readonly Filesystem $files
    ) {
    }

    public function publish(?string $group = null): void
    {
        $languages = Language::query()->where('is_active', true)->get();
        if ($languages->isEmpty()) {
            return;
        }

        $translations = Translation::query()
            ->when($group, fn ($query) => $query->where('group', $group))
            ->get()
            ->groupBy('group');

        foreach ($translations as $groupName => $entries) {
            foreach ($languages as $language) {
                $payload = $this->buildPayloadForLanguage($groupName, $entries, $language->code);
                $this->writeFile($language->code, $groupName, $payload);
            }
        }
    }

    protected function buildPayloadForLanguage(string $group, $entries, string $locale): array
    {
        $existing = $this->readExisting($locale, $group);
        $data = $existing;

        foreach ($entries as $entry) {
            $value = $entry->values[$locale] ?? null;
            if ($value !== null) {
                Arr::set($data, $entry->key, $value);
            }
        }

        return $data;
    }

    protected function readExisting(string $locale, string $group): array
    {
        $path = lang_path("$locale/$group.php");
        if ($this->files->exists($path)) {
            $data = include $path;
            if (is_array($data)) {
                return $data;
            }
        }

        return [];
    }

    protected function writeFile(string $locale, string $group, array $data): void
    {
        $dir = lang_path($locale);
        $this->files->ensureDirectoryExists($dir);

        $content = "<?php\n\nreturn " . var_export($data, true) . ";\n";
        $this->files->put("$dir/$group.php", $content);
    }
}
