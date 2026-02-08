<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Enums\IconType;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

final class Icon extends Component
{
    public string $icon;

    public string $class;

    /**
     * Create a new component instance.
     */
    public function __construct(
        string $name,
        string $class = 'h-5 w-5'
    ) {
        $iconType = IconType::fromLegacyKey($name);
        $this->icon = $iconType->heroicon();
        $this->class = $class;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.icon');
    }
}
