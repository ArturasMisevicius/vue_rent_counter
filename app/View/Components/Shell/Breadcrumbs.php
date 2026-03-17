<?php

namespace App\View\Components\Shell;

use App\Support\Shell\Breadcrumbs\BreadcrumbItemData;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class Breadcrumbs extends Component
{
    /**
     * @param  array<int, BreadcrumbItemData>  $items
     */
    public function __construct(
        public array $items = [],
    ) {}

    public function render(): View
    {
        return view('components.shell.breadcrumbs', [
            'breadcrumbs' => Collection::make($this->items)
                ->mapWithKeys(function (BreadcrumbItemData $item, int $index): array {
                    return [$item->url ?? $index => $item->label];
                })
                ->all(),
        ]);
    }
}
