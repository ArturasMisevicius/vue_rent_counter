<?php

declare(strict_types=1);

namespace App\View\Components\Shared;

use App\Filament\Support\View\BladeViewData;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Alert extends Component
{
    public string $style;

    public function __construct(
        public string $type = 'info',
        public string $message = '',
        public bool $dismissable = false,
    ) {
        $this->style = BladeViewData::alertClasses($type);
    }

    public function render(): View
    {
        return view('components.shared.alert');
    }
}
