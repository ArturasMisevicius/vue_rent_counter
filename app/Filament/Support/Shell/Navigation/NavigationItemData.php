<?php

namespace App\Filament\Support\Shell\Navigation;

class NavigationItemData
{
    public function __construct(
        public readonly string $label,
        public readonly string $url,
        public readonly string $routeName,
        public readonly ?string $icon = null,
        public readonly bool $active = false,
    ) {}
}
