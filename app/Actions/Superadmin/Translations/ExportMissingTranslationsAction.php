<?php

namespace App\Actions\Superadmin\Translations;

use App\Support\Superadmin\Translations\TranslationCatalogService;

class ExportMissingTranslationsAction
{
    public function handle(TranslationCatalogService $translationCatalogService, ?string $locale = null): string
    {
        return $translationCatalogService->exportMissing($locale);
    }
}
