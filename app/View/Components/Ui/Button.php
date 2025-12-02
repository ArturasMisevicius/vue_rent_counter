<?php

declare(strict_types=1);

namespace App\View\Components\Ui;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * daisyUI Button Component
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
        $classes = ['btn'];
        
        $variantMap = [
            'primary' => 'btn-primary',
            'secondary' => 'btn-secondary',
            'accent' => 'btn-accent',
            'ghost' => 'btn-ghost',
            'link' => 'btn-link',
            'info' => 'btn-info',
            'success' => 'btn-success',
            'warning' => 'btn-warning',
            'error' => 'btn-error',
        ];
        
        $sizeMap = [
            'xs' => 'btn-xs',
            'sm' => 'btn-sm',
            'lg' => 'btn-lg',
        ];
        
        if (isset($variantMap[$this->variant])) {
            $classes[] = $variantMap[$this->variant];
        }
        
        if (isset($sizeMap[$this->size])) {
            $classes[] = $sizeMap[$this->size];
        }
        
        if ($this->outline) {
            $classes[] = 'btn-outline';
        }
        
        return implode(' ', $classes);
    }
}
