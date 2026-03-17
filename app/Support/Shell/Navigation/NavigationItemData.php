<?php

namespace App\Support\Shell\Navigation;

readonly class NavigationItemData
{
    public function __construct(
        public string $label,
        public string $routeName,
        public string $url,
        public bool $isActive,
    ) {}
}
