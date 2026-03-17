<?php

namespace App\Filament\Actions\Superadmin\Translations;

use App\Filament\Support\Superadmin\Translations\TranslationCatalogService;

class ExportMissingTranslationsAction
{
    public function handle(TranslationCatalogService $translationCatalogService, ?string $locale = null): string
    {
        return $translationCatalogService->exportMissing($locale);
    }
}
