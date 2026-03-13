<?php

declare(strict_types=1);

namespace App\Filament\Resources\TranslationResource\Concerns;

/**
 * Trait for filtering empty language values from translation forms.
 *
 * This trait provides common functionality for both create and edit
 * translation pages to ensure empty language values are not stored
 * in the database.
 *
 * @see \App\Filament\Resources\TranslationResource\Pages\CreateTranslation
 * @see \App\Filament\Resources\TranslationResource\Pages\EditTranslation
 */
trait FiltersEmptyLanguageValues
{
    /**
     * Filter out empty language values from form data.
     *
     * This ensures that when a language value is empty (null or empty string),
     * it's removed from the values JSON field rather than stored.
     *
     * @param array<string, mixed> $data The form data to filter
     * @return array<string, mixed> The filtered form data
     */
    protected function filterEmptyLanguageValues(array $data): array
    {
        if (isset($data['values']) && is_array($data['values'])) {
            $data['values'] = array_filter(
                $data['values'],
                fn (mixed $value): bool => $value !== null && $value !== '' && trim((string) $value) !== ''
            );
        }

        return $data;
    }
}
