<?php

namespace App\Actions\Superadmin\Translations;

use App\Support\Superadmin\Translations\TranslationCatalogService;

class ImportTranslationsAction
{
    public function __construct(
        private readonly TranslationCatalogService $translationCatalogService,
    ) {}

    public function __invoke(string $locale, string $group, string $path): int
    {
        return $this->translationCatalogService->importFromFile($locale, $group, $path);
    }
}
