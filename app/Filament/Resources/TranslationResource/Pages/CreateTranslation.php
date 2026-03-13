<?php

declare(strict_types=1);

namespace App\Filament\Resources\TranslationResource\Pages;

use App\Filament\Resources\TranslationResource;
use App\Filament\Resources\TranslationResource\Concerns\FiltersEmptyLanguageValues;
use Filament\Resources\Pages\CreateRecord;

/**
 * Create page for Translation resource.
 *
 * Provides functionality to create new translation entries with:
 * - Automatic filtering of empty language values
 * - Superadmin-only access (enforced by resource)
 *
 * @see \App\Filament\Resources\TranslationResource
 * @see \App\Models\Translation
 */
class CreateTranslation extends CreateRecord
{
    use FiltersEmptyLanguageValues;

    protected static string $resource = TranslationResource::class;

    /**
     * Filter out empty language values before creating.
     *
     * This ensures that when a language value is left empty (null, empty string,
     * or whitespace-only), it's not stored in the values JSON field.
     *
     * @param array<string, mixed> $data The form data to mutate
     * @return array<string, mixed> The mutated form data
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->filterEmptyLanguageValues($data);
    }
}

