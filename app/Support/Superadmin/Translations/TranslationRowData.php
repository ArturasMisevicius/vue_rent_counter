<?php

namespace App\Support\Superadmin\Translations;

final readonly class TranslationRowData
{
    /**
     * @param  array<string, string|null>  $values
     */
    public function __construct(
        public string $group,
        public string $key,
        public array $values,
    ) {}
}
