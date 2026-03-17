<?php

namespace App\Support\Shell\Navigation;

readonly class NavigationGroupData
{
    /**
     * @param  list<NavigationItemData>  $items
     */
    public function __construct(
        public string $label,
        public array $items,
    ) {}
}
