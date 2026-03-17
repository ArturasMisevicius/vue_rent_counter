<?php

namespace App\Filament\Actions\Superadmin\Translations;

use App\Filament\Support\Superadmin\Translations\TranslationCatalogService;

class ImportTranslationsAction
{
    public function handle(TranslationCatalogService $translationCatalogService, string $path): void
    {
        $translationCatalogService->importCsv($path);
    }
}
