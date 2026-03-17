<?php

namespace App\View\Components\Shell;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AppFrame extends Component
{
    /**
     * @param  array<int, mixed>  $breadcrumbs
     */
    public function __construct(
        public ?string $title = null,
        public ?string $eyebrow = null,
        public ?string $heading = null,
        public bool $showTenantNavigation = false,
        public array $breadcrumbs = [],
    ) {}

    public function render(): View
    {
        return view('components.shell.app-frame');
    }
}
