<?php

declare(strict_types=1);

namespace App\View\Components\Framework;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Alert extends Component
{
    public function __construct(public string $type = 'info') {}

    public function render(): View
    {
        return view('components.framework.⚡alert');
    }
}
