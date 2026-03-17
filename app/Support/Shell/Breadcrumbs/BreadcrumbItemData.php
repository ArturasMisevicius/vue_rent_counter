<?php

namespace App\Support\Shell\Breadcrumbs;

class BreadcrumbItemData
{
    public function __construct(
        public string $label,
        public ?string $url = null,
    ) {}
}
