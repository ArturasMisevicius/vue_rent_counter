<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

/**
 * Trait for Filament resources to load validation messages from translation files.
 *
 * Ensures consistency between FormRequests and Filament form validation.
 *
 * Usage:
 * ```php
 * use App\Filament\Concerns\HasTranslatedValidation;
 *
 * class PropertyResource extends Resource
 * {
 *     use HasTranslatedValidation;
 *
 *     protected static string $translationPrefix = 'properties.validation';
 *
 *     // In form schema:
 *     ->validationMessages(self::getValidationMessages('address'))
 * }
 * ```
 */
trait HasTranslatedValidation
{
    /**
     * Get validation messages for a specific field from translation files.
     *
     * @param  string  $field  Field name (e.g., 'address', 'type')
     * @param  array<string>  $rules  Optional list of rules to check (defaults to common rules)
     * @return array<string, string> Validation messages keyed by rule name
     */
    protected static function getValidationMessages(string $field, array $rules = []): array
    {
        $translationPrefix = static::$translationPrefix ?? 'validation';

        $defaultRules = [
            'required', 'max', 'min', 'enum', 'numeric',
            'exists', 'unique', 'email', 'string', 'array',
            'date', 'boolean', 'integer', 'regex', 'in',
        ];

        $rulesToCheck = empty($rules) ? $defaultRules : $rules;
        $messages = [];

        foreach ($rulesToCheck as $rule) {
            $key = "{$translationPrefix}.{$field}.{$rule}";

            // Only include if translation exists (not the key itself)
            if (__($key) !== $key) {
                $messages[$rule] = __($key);
            }
        }

        return $messages;
    }

    /**
     * Get all validation messages for multiple fields at once.
     *
     * @param  array<string, array<string>>  $fieldRules  Map of field names to their rules
     * @return array<string, array<string, string>> Validation messages keyed by field and rule
     */
    protected static function getValidationMessagesForFields(array $fieldRules): array
    {
        $messages = [];

        foreach ($fieldRules as $field => $rules) {
            $messages[$field] = static::getValidationMessages($field, $rules);
        }

        return $messages;
    }
}
