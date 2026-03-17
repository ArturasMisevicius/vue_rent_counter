<?php

namespace App\Actions\Superadmin\Translations;

use App\Support\Superadmin\Translations\TranslationCatalogService;

class ImportTranslationsAction
{
    public function handle(TranslationCatalogService $translationCatalogService, string $path): void
    {
        $translationCatalogService->importCsv($path);
    }
}
