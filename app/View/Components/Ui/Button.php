<?php

declare(strict_types=1);

namespace App\View\Components\Ui;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Shared Design System Button Component
 *
 * Provides a consistent button interface following blade-guardrails
 */
class Button extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $variant = 'default',
        public string $size = 'md',
        public bool $outline = false,
        public bool $loading = false,
        public bool $disabled = false,
        public string $type = 'button',
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('components.ui.button');
    }

    /**
     * Get the button CSS classes.
     */
    public function classes(): string
    {
        $classes = ['ds-btn'];

        $variantMap = [
            'default' => 'ds-btn--primary',
            'primary' => 'ds-btn--primary',
            'secondary' => 'ds-btn--secondary',
            'danger' => 'ds-btn--danger',
            'error' => 'ds-btn--danger',
            'ghost' => 'ds-btn--secondary',
            'link' => 'ds-btn--secondary',
            'info' => 'ds-btn--secondary',
            'success' => 'ds-btn--secondary',
            'warning' => 'ds-btn--secondary',
            'accent' => 'ds-btn--primary',
        ];

        $sizeMap = [
            'xs' => 'px-2.5 py-1.5 text-xs',
            'sm' => 'px-3 py-2 text-xs',
            'md' => 'px-4 py-2.5 text-sm',
            'lg' => 'px-5 py-3 text-base',
        ];

        if (isset($variantMap[$this->variant])) {
            $classes[] = $variantMap[$this->variant];
        }

        if (isset($sizeMap[$this->size])) {
            $classes[] = $sizeMap[$this->size];
        }

        if ($this->outline) {
            $classes[] = 'bg-transparent border-slate-300 text-slate-800';
        }

        if ($this->disabled || $this->loading) {
            $classes[] = 'opacity-60 cursor-not-allowed';
        }

        return implode(' ', $classes);
    }
}
