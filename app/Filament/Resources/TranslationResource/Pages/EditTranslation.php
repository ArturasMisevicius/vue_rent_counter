<?php

declare(strict_types=1);

namespace App\Filament\Resources\TranslationResource\Pages;

use App\Filament\Resources\TranslationResource;
use App\Filament\Resources\TranslationResource\Concerns\FiltersEmptyLanguageValues;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

/**
 * Edit page for Translation resource.
 *
 * Provides functionality to edit existing translation entries with:
 * - Delete action in header
 * - Automatic filtering of empty language values
 * - Superadmin-only access (enforced by resource)
 *
 * ## Data Flow
 * 1. User submits form with translation values
 * 2. `mutateFormDataBeforeSave()` filters out empty language values
 * 3. Translation model is updated with cleaned data
 * 4. TranslationPublisher automatically publishes changes to PHP language files
 *
 * ## Empty Value Handling
 * When a language value is cleared (set to empty string or null), it's removed
 * from the values JSON field rather than stored. This keeps the database clean
 * and prevents empty strings from appearing in language files.
 *
 * ## Example Usage
 * ```php
 * // User clears the 'lt' language value in the form
 * // Before filtering: ['en' => 'Hello', 'lt' => '', 'ru' => 'Привет']
 * // After filtering:  ['en' => 'Hello', 'ru' => 'Привет']
 * ```
 *
 * @see \App\Filament\Resources\TranslationResource
 * @see \App\Models\Translation
 * @see \App\Services\TranslationPublisher
 * @see \App\Filament\Resources\TranslationResource\Concerns\FiltersEmptyLanguageValues
 */
class EditTranslation extends EditRecord
{
    use FiltersEmptyLanguageValues;

    protected static string $resource = TranslationResource::class;

    /**
     * Get the header actions for the edit page.
     *
     * Provides a delete action that allows superadmins to remove
     * translation entries. Deletion triggers automatic republishing
     * of language files via the Translation model observer.
     *
     * @return array<\Filament\Actions\Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * Filter out empty language values before saving.
     *
     * This ensures that when a language value is cleared (set to empty string),
     * it's removed from the values JSON field rather than stored as an empty string.
     *
     * This method is called automatically by Filament before the model is saved.
     * It processes the form data to remove any language values that are null or
     * empty strings, keeping the database clean and preventing empty translations
     * from being published to PHP language files.
     *
     * @param  array<string, mixed>  $data  The form data to mutate
     * @return array<string, mixed> The mutated form data with empty values removed
     *
     * @example
     * ```php
     * // Input data from form:
     * [
     *     'group' => 'app',
     *     'key' => 'welcome',
     *     'values' => [
     *         'en' => 'Welcome',
     *         'lt' => '',           // Empty - will be removed
     *         'ru' => 'Добро пожаловать',
     *         'es' => null,         // Null - will be removed
     *     ]
     * ]
     *
     * // Output after mutation:
     * [
     *     'group' => 'app',
     *     'key' => 'welcome',
     *     'values' => [
     *         'en' => 'Welcome',
     *         'ru' => 'Добро пожаловать',
     *     ]
     * ]
     * ```
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->filterEmptyLanguageValues($data);
    }
}
