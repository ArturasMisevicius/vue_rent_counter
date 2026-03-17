<?php

namespace App\Support\Shell\Search\Data;

readonly class GlobalSearchResultData
{
    public function __construct(
        public string $group,
        public string $label,
        public string $detail,
        public string $typeLabel,
        public string $url,
    ) {}
}
