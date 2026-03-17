<?php

namespace App\Actions\Superadmin\Translations;

use App\Support\Superadmin\Translations\TranslationCatalogService;

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
