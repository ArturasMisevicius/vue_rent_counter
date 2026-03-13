<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Translation;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Translatable
{
    /**
     * Get all translations for this model.
     */
    public function translations(): MorphMany
    {
        return $this->morphMany(Translation::class, 'translatable');
    }

    /**
     * Get translation for a specific field and locale.
     */
    public function getTranslation(string $field, ?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();
        
        $translation = $this->translations()
            ->where('field', $field)
            ->first();

        if (!$translation) {
            return null;
        }

        return $translation->values[$locale] ?? $translation->values[config('app.fallback_locale')] ?? null;
    }

    /**
     * Set translation for a specific field and locale.
     */
    public function setTranslation(string $field, string $locale, string $value): void
    {
        $translation = $this->translations()
            ->where('field', $field)
            ->first();

        if (!$translation) {
            $translation = new Translation([
                'field' => $field,
                'values' => [],
            ]);
            $this->translations()->save($translation);
        }

        $values = $translation->values;
        $values[$locale] = $value;
        $translation->update(['values' => $values]);
    }

    /**
     * Set translations for multiple locales at once.
     */
    public function setTranslations(string $field, array $translations): void
    {
        foreach ($translations as $locale => $value) {
            $this->setTranslation($field, $locale, $value);
        }
    }

    /**
     * Get all translations for a field as an array.
     */
    public function getTranslations(string $field): array
    {
        $translation = $this->translations()
            ->where('field', $field)
            ->first();

        return $translation ? $translation->values : [];
    }

    /**
     * Check if translation exists for a field and locale.
     */
    public function hasTranslation(string $field, ?string $locale = null): bool
    {
        $locale = $locale ?? app()->getLocale();
        
        return $this->translations()
            ->where('field', $field)
            ->whereJsonContains('values->' . $locale, fn($query) => $query->whereNotNull())
            ->exists();
    }

    /**
     * Delete all translations for a specific field.
     */
    public function deleteTranslations(string $field): void
    {
        $this->translations()
            ->where('field', $field)
            ->delete();
    }
}