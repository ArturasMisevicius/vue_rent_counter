<?php

namespace App\Support\Superadmin\Translations;

final readonly class TranslationRowData
{
    public function __construct(
        public string $key,
        public string $stateKey,
        public string $sourceValue,
        public string $translatedValue,
        public bool $missing,
    ) {}
}
