<?php

namespace App\Filament\Actions\Superadmin\Translations;

use App\Filament\Support\Superadmin\Translations\TranslationCatalogService;

class UpdateTranslationValueAction
{
    public function handle(
        TranslationCatalogService $translationCatalogService,
        string $group,
        string $key,
        string $locale,
        string $value,
    ): void {
        $translationCatalogService->updateValue($group, $key, $locale, $value);
    }
}
