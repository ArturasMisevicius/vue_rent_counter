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
        $classes = [
            'inline-flex',
            'items-center',
            'justify-center',
            'gap-2',
            'rounded-xl',
            'font-semibold',
            'transition',
            'focus-visible:outline-none',
            'focus-visible:ring-2',
            'focus-visible:ring-indigo-500',
            'focus-visible:ring-offset-2',
        ];

        $variantMap = [
            'default' => 'bg-indigo-600 text-white hover:bg-indigo-500',
            'primary' => 'bg-indigo-600 text-white hover:bg-indigo-500',
            'secondary' => 'bg-white text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50',
            'danger' => 'bg-rose-600 text-white hover:bg-rose-500',
            'error' => 'bg-rose-600 text-white hover:bg-rose-500',
            'ghost' => 'bg-transparent text-slate-700 hover:bg-slate-100',
            'link' => 'bg-transparent text-indigo-600 hover:text-indigo-500',
            'info' => 'bg-sky-600 text-white hover:bg-sky-500',
            'success' => 'bg-emerald-600 text-white hover:bg-emerald-500',
            'warning' => 'bg-amber-500 text-slate-900 hover:bg-amber-400',
            'accent' => 'bg-violet-600 text-white hover:bg-violet-500',
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
            $classes[] = 'bg-transparent text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50';
        }

        if ($this->disabled || $this->loading) {
            $classes[] = 'cursor-not-allowed opacity-60';
        }

        return implode(' ', $classes);
    }
}
