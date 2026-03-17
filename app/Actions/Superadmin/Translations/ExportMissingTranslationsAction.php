<?php

namespace App\Actions\Superadmin\Translations;

use App\Support\Superadmin\Translations\TranslationCatalogService;

class ExportMissingTranslationsAction
{
    public function __construct(
        private readonly TranslationCatalogService $translationCatalogService,
    ) {}

    public function __invoke(string $locale, string $group): string
    {
        return $this->translationCatalogService->exportMissingTranslations($locale, $group);
    }
}
