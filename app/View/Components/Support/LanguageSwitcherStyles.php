<?php

declare(strict_types=1);

namespace App\View\Components\Support;

/**
 * Language Switcher Styles Configuration
 *
 * Centralizes CSS class management for language switcher variants.
 * Follows the Single Responsibility Principle by handling only styling concerns.
 */
final readonly class LanguageSwitcherStyles
{
    /**
     * Base CSS classes for different variants
     */
    private const VARIANT_CLASSES = [
        'select' => [
            'base' => 'bg-white/10 border border-white/20 text-white text-sm rounded-lg px-3 py-2',
            'focus' => 'focus:outline-none focus:ring-2 focus:ring-white/40',
            'transition' => 'transition-all duration-200',
        ],
        'dropdown' => [
            'base' => 'flex items-center gap-2 text-sm font-medium',
            'focus' => 'focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2',
            'transition' => 'transition-all duration-200',
        ],
    ];

    /**
     * Container CSS classes
     */
    private const CONTAINER_CLASSES = [
        'select' => 'inline-flex',
        'dropdown' => 'relative inline-block text-left',
    ];

    /**
     * Form CSS classes
     */
    private const FORM_CLASSES = 'inline-flex';

    /**
     * Get CSS classes for a specific variant
     */
    public static function getVariantClasses(string $variant): string
    {
        $classes = self::VARIANT_CLASSES[$variant] ?? self::VARIANT_CLASSES['select'];

        return implode(' ', [
            $classes['base'],
            $classes['focus'],
            $classes['transition'],
        ]);
    }

    /**
     * Get container CSS classes for a variant
     */
    public static function getContainerClasses(string $variant): string
    {
        return self::CONTAINER_CLASSES[$variant] ?? self::CONTAINER_CLASSES['select'];
    }

    /**
     * Get form CSS classes
     */
    public static function getFormClasses(): string
    {
        return self::FORM_CLASSES;
    }

    /**
     * Get dropdown menu CSS classes
     */
    public static function getDropdownMenuClasses(): string
    {
        return implode(' ', [
            'absolute right-0 z-10 mt-2 w-48 rounded-md bg-white shadow-lg',
            'ring-1 ring-black/5 divide-y divide-slate-100',
        ]);
    }

    /**
     * Get dropdown item CSS classes
     */
    public static function getDropdownItemClasses(bool $isActive = false): string
    {
        $baseClasses = 'block px-4 py-2 text-sm text-slate-700 hover:bg-slate-100 focus:bg-slate-100 focus:outline-none';

        if ($isActive) {
            $baseClasses .= ' bg-slate-50 font-medium';
        }

        return $baseClasses;
    }

    /**
     * Get loading spinner CSS classes
     */
    public static function getSpinnerClasses(): string
    {
        return implode(' ', [
            'pointer-events-none absolute right-2 top-1/2 -translate-y-1/2',
            'animate-spin text-white/70 text-sm pointer-events-none',
        ]);
    }

    /**
     * Get all supported variants
     */
    public static function getSupportedVariants(): array
    {
        return array_keys(self::VARIANT_CLASSES);
    }

    /**
     * Check if a variant is supported
     */
    public static function isValidVariant(string $variant): bool
    {
        return array_key_exists($variant, self::VARIANT_CLASSES);
    }
}
