<?php

namespace App\View\Components\Shell;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Vite;
use Illuminate\View\Component;

class AppFrame extends Component
{
    public ?string $cspNonce;

    /**
     * @param  array<int, mixed>  $breadcrumbs
     */
    public function __construct(
        public ?string $title = null,
        public ?string $eyebrow = null,
        public ?string $heading = null,
        public bool $showTenantNavigation = false,
        public array $breadcrumbs = [],
    ) {
        $this->cspNonce = Vite::cspNonce();
    }

    public function render(): View
    {
        return view('components.shell.app-frame');
    }
}
