<?php

namespace App\Filament\Support\Shell\Navigation;

class NavigationGroupData
{
    /**
     * @param  array<int, NavigationItemData>  $items
     */
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly array $items,
    ) {}
}
