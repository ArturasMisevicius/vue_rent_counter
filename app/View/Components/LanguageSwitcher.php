<?php

declare(strict_types=1);

namespace App\View\Components;

use App\ValueObjects\LanguageData;
use App\View\Components\Support\LanguageSwitcherStyles;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;
use InvalidArgumentException;

/**
 * Language Switcher Component
 * 
 * Provides a multi-variant language switching interface for the multi-tenant utilities platform.
 * Supports both select dropdown and interactive dropdown variants with full accessibility support.
 * 
 * Features:
 * - Two UI variants: 'select' (form-based) and 'dropdown' (Alpine.js interactive)
 * - Progressive enhancement with JavaScript fallbacks
 * - Full accessibility support (ARIA attributes, keyboard navigation)
 * - Localized labels and content
 * - Type-safe language data handling via LanguageData value objects
 * 
 * @see LanguageData Value object for language information
 * @see LanguageSwitcherStyles CSS class management
 * @see resources/js/components/language-switcher.js Progressive enhancement
 */
final class LanguageSwitcher extends Component
{
    /**
     * Processed language data
     */
    public readonly Collection $languageData;

    /**
     * Current language data
     */
    public readonly ?LanguageData $currentLanguageData;

    /**
     * Create a new language switcher component instance.
     * 
     * @param string $variant UI variant: 'select' for form-based dropdown, 'dropdown' for interactive menu
     * @param string $class Additional CSS classes to apply to the container
     * @param bool $showLabels Whether to show language names (true) or codes only (false)
     * @param Collection<int, Language|array> $languages Collection of Language models or language data arrays
     * @param string $currentLocale Current locale code (e.g., 'en', 'lt', 'ru')
     * 
     * @throws InvalidArgumentException When an unsupported variant is provided
     */
    public function __construct(
        public readonly string $variant = 'select',
        public readonly string $class = '',
        public readonly bool $showLabels = true,
        Collection $languages = new Collection(),
        public readonly string $currentLocale = '',
    ) {
        $this->validateVariant();
        $this->languageData = $this->processLanguages($languages);
        $this->currentLanguageData = $this->findCurrentLanguage();
    }

    /**
     * Render the language switcher component.
     * 
     * @return View The Blade view for the component
     */
    public function render(): View
    {
        return view('components.language-switcher');
    }

    /**
     * Get CSS classes for the component variant.
     * 
     * @return string Combined CSS classes for the main interactive element
     */
    public function getBaseClasses(): string
    {
        return LanguageSwitcherStyles::getVariantClasses($this->variant);
    }

    /**
     * Get container CSS classes with custom classes appended.
     * 
     * @return string Combined container classes including any custom classes
     */
    public function getContainerClasses(): string
    {
        $baseClasses = LanguageSwitcherStyles::getContainerClasses($this->variant);
        
        return trim($baseClasses . ' ' . $this->class);
    }

    /**
     * Get form CSS classes for the language switching form.
     * 
     * @return string CSS classes for the form element
     */
    public function getFormClasses(): string
    {
        return LanguageSwitcherStyles::getFormClasses();
    }

    /**
     * Get dropdown menu CSS classes for the dropdown variant.
     * 
     * @return string CSS classes for the dropdown menu container
     */
    public function getDropdownMenuClasses(): string
    {
        return LanguageSwitcherStyles::getDropdownMenuClasses();
    }

    /**
     * Get dropdown item CSS classes with active state handling.
     * 
     * @param LanguageData $language The language to get classes for
     * @return string CSS classes for the dropdown item, including active state
     */
    public function getDropdownItemClasses(LanguageData $language): string
    {
        $isActive = $this->currentLanguageData?->matches($language->code) ?? false;
        return LanguageSwitcherStyles::getDropdownItemClasses($isActive);
    }

    /**
     * Get current language display text based on showLabels setting.
     * 
     * @return string Display text for the current language (name or uppercase code)
     */
    public function getCurrentLanguageDisplay(): string
    {
        if ($this->currentLanguageData === null) {
            return strtoupper($this->currentLocale);
        }

        return $this->showLabels 
            ? $this->currentLanguageData->getDisplayName()
            : $this->currentLanguageData->getUppercaseCode();
    }

    /**
     * Check if a language is currently selected.
     * 
     * @param LanguageData $language The language to check
     * @return bool True if the language matches the current locale
     */
    public function isLanguageSelected(LanguageData $language): bool
    {
        return $language->matches($this->currentLocale);
    }

    /**
     * Get language display text for options based on showLabels setting.
     * 
     * @param LanguageData $language The language to get display text for
     * @return string Display text (native name/name or uppercase code)
     */
    public function getLanguageDisplayText(LanguageData $language): string
    {
        return $this->showLabels 
            ? $language->getDisplayName()
            : $language->getUppercaseCode();
    }

    /**
     * Check if the component has any languages to display.
     * 
     * @return bool True if there are languages available for switching
     */
    public function hasLanguages(): bool
    {
        return $this->languageData->isNotEmpty();
    }

    /**
     * Get switch URL for a language using the language.switch route.
     * 
     * @param LanguageData $language The language to generate URL for
     * @return string URL for switching to the specified language
     */
    public function getSwitchUrl(LanguageData $language): string
    {
        return route('language.switch', $language->code);
    }

    /**
     * Validate the variant parameter
     */
    private function validateVariant(): void
    {
        if (!LanguageSwitcherStyles::isValidVariant($this->variant)) {
            throw new InvalidArgumentException(
                "Invalid variant '{$this->variant}'. Supported variants: " . 
                implode(', ', LanguageSwitcherStyles::getSupportedVariants())
            );
        }
    }

    /**
     * Process languages into value objects
     */
    private function processLanguages(Collection $languages): Collection
    {
        return $languages->map(function ($language) {
            // Handle both Language models and arrays/objects
            if ($language instanceof \App\Models\Language) {
                return LanguageData::fromModel($language);
            }

            // Handle array/object data
            $languageArray = is_array($language) ? $language : (array) $language;
            
            return new LanguageData(
                code: $languageArray['code'] ?? '',
                name: $languageArray['name'] ?? '',
                nativeName: $languageArray['native_name'] ?? $languageArray['name'] ?? '',
                isActive: $languageArray['is_active'] ?? true,
                isDefault: $languageArray['is_default'] ?? false,
                displayOrder: $languageArray['display_order'] ?? 0,
            );
        })->filter(fn (LanguageData $lang) => !empty($lang->code));
    }

    /**
     * Find current language data
     */
    private function findCurrentLanguage(): ?LanguageData
    {
        return $this->languageData->first(
            fn (LanguageData $lang) => $lang->matches($this->currentLocale)
        );
    }
}