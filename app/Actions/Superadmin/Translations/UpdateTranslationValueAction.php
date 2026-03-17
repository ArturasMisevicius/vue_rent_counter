<?php

namespace App\Actions\Superadmin\Translations;

use App\Support\Superadmin\Translations\TranslationCatalogService;

class UpdateTranslationValueAction
{
    public function __construct(
        private readonly TranslationCatalogService $translationCatalogService,
    ) {}

    public function __invoke(string $locale, string $group, string $key, string $value): void
    {
        $this->translationCatalogService->updateValue($locale, $group, $key, $value);
    }
}
