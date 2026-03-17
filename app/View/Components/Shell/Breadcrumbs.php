<?php

namespace App\View\Components\Shell;

use App\Filament\Support\Shell\Breadcrumbs\BreadcrumbItemData;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Breadcrumbs extends Component
{
    /**
     * @var array<int, BreadcrumbItemData>
     */
    public array $items;

    /**
     * @param  array<int, BreadcrumbItemData|array{label?: string, url?: string|null, current?: bool}|string>  $items
     */
    public function __construct(array $items = [])
    {
        $normalized = array_values(array_map(
            fn (BreadcrumbItemData|array|string $item): BreadcrumbItemData => BreadcrumbItemData::from($item),
            $items,
        ));

        $lastIndex = array_key_last($normalized);

        $this->items = array_map(
            fn (BreadcrumbItemData $item, int $index): BreadcrumbItemData => $index === $lastIndex
                ? $item->asCurrent()
                : $item,
            $normalized,
            array_keys($normalized),
        );
    }

    public function render(): View
    {
        return view('components.shell.breadcrumbs');
    }
}
