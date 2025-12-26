<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Models\Language;
use Illuminate\Support\Collection;

/**
 * Language Data Value Object
 * 
 * Immutable value object representing language information for UI components.
 * Provides type safety and encapsulates language-related business logic.
 */
final readonly class LanguageData
{
    public function __construct(
        public string $code,
        public string $name,
        public string $nativeName,
        public bool $isActive,
        public bool $isDefault,
        public int $displayOrder,
    ) {}

    /**
     * Create from Language model
     */
    public static function fromModel(Language $language): self
    {
        return new self(
            code: $language->code,
            name: $language->name,
            nativeName: $language->native_name,
            isActive: $language->is_active,
            isDefault: $language->is_default,
            displayOrder: $language->display_order,
        );
    }

    /**
     * Create collection from Language models
     */
    public static function fromCollection(Collection $languages): Collection
    {
        return $languages->map(fn (Language $language) => self::fromModel($language));
    }

    /**
     * Get display name based on preference
     */
    public function getDisplayName(bool $preferNative = true): string
    {
        return $preferNative && !empty($this->nativeName) 
            ? $this->nativeName 
            : $this->name;
    }

    /**
     * Get uppercase code for display
     */
    public function getUppercaseCode(): string
    {
        return strtoupper($this->code);
    }

    /**
     * Check if this language matches a given code
     */
    public function matches(string $code): bool
    {
        return $this->code === strtolower($code);
    }

    /**
     * Get language for select option
     */
    public function toSelectOption(bool $showLabels = true): array
    {
        return [
            'value' => $this->code,
            'label' => $showLabels ? $this->getDisplayName() : $this->getUppercaseCode(),
            'selected' => false, // Will be set by component
        ];
    }

    /**
     * Convert to array for JSON serialization
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'native_name' => $this->nativeName,
            'is_active' => $this->isActive,
            'is_default' => $this->isDefault,
            'display_order' => $this->displayOrder,
        ];
    }
}