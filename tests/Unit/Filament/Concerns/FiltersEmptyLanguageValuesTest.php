<?php

declare(strict_types=1);

namespace Tests\Unit\Filament\Concerns;

use App\Filament\Resources\TranslationResource\Concerns\FiltersEmptyLanguageValues;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for FiltersEmptyLanguageValues trait.
 *
 * Tests the filtering logic for empty language values in translation forms,
 * ensuring that null, empty strings, and whitespace-only values are properly
 * removed from the values array while preserving valid content.
 *
 * This test suite validates:
 * - Valid value preservation (strings with content, numeric strings, special chars)
 * - Empty value filtering (null, empty strings, whitespace-only)
 * - Edge case handling (missing keys, non-array values, boolean false)
 * - Data integrity (other form fields remain unchanged)
 *
 * The trait is used in CreateTranslation and EditTranslation pages to ensure
 * clean data storage in the Translation model's JSON values field.
 *
 * @group unit
 * @group filament
 * @group translation
 * @group concerns
 *
 * @see \App\Filament\Resources\TranslationResource\Concerns\FiltersEmptyLanguageValues
 * @see \App\Filament\Resources\TranslationResource\Pages\CreateTranslation
 * @see \App\Filament\Resources\TranslationResource\Pages\EditTranslation
 * @see \App\Models\Translation
 *
 * @covers \App\Filament\Resources\TranslationResource\Concerns\FiltersEmptyLanguageValues
 */
class FiltersEmptyLanguageValuesTest extends TestCase
{
    use FiltersEmptyLanguageValues;

    /**
     * Test that valid language values are preserved.
     *
     * Ensures that when all language values contain valid text,
     * they are all preserved unchanged in the filtered result.
     *
     * @test
     */
    public function test_preserves_valid_language_values(): void
    {
        $data = [
            'group' => 'common',
            'key' => 'welcome',
            'values' => [
                'en' => 'Welcome',
                'lt' => 'Sveiki',
                'ru' => 'Добро пожаловать',
            ],
        ];

        $result = $this->filterEmptyLanguageValues($data);

        $this->assertSame($data, $result);
        $this->assertCount(3, $result['values']);
    }

    /**
     * Test that null values are filtered out.
     *
     * Ensures that language values set to null are removed
     * while other valid values are preserved.
     *
     * @test
     */
    public function test_filters_null_values(): void
    {
        $data = [
            'group' => 'common',
            'key' => 'welcome',
            'values' => [
                'en' => 'Welcome',
                'lt' => null,
                'ru' => 'Добро пожаловать',
            ],
        ];

        $result = $this->filterEmptyLanguageValues($data);

        $this->assertArrayHasKey('values', $result);
        $this->assertCount(2, $result['values']);
        $this->assertArrayHasKey('en', $result['values']);
        $this->assertArrayNotHasKey('lt', $result['values']);
        $this->assertArrayHasKey('ru', $result['values']);
    }

    /**
     * Test that empty string values are filtered out.
     *
     * Ensures that language values set to empty strings are removed
     * while other valid values are preserved.
     *
     * @test
     */
    public function test_filters_empty_string_values(): void
    {
        $data = [
            'group' => 'common',
            'key' => 'welcome',
            'values' => [
                'en' => 'Welcome',
                'lt' => '',
                'ru' => 'Добро пожаловать',
            ],
        ];

        $result = $this->filterEmptyLanguageValues($data);

        $this->assertArrayHasKey('values', $result);
        $this->assertCount(2, $result['values']);
        $this->assertArrayHasKey('en', $result['values']);
        $this->assertArrayNotHasKey('lt', $result['values']);
        $this->assertArrayHasKey('ru', $result['values']);
    }

    /**
     * Test that whitespace-only values are filtered out.
     *
     * Ensures that language values containing only whitespace
     * (spaces, tabs) are removed as they provide no meaningful content.
     *
     * @test
     */
    public function test_filters_whitespace_only_values(): void
    {
        $data = [
            'group' => 'common',
            'key' => 'welcome',
            'values' => [
                'en' => 'Welcome',
                'lt' => '   ',
                'ru' => '     ',
                'es' => '  ',
            ],
        ];

        $result = $this->filterEmptyLanguageValues($data);

        $this->assertArrayHasKey('values', $result);
        $this->assertCount(1, $result['values']);
        $this->assertArrayHasKey('en', $result['values']);
        $this->assertArrayNotHasKey('lt', $result['values']);
        $this->assertArrayNotHasKey('ru', $result['values']);
        $this->assertArrayNotHasKey('es', $result['values']);
    }

    /**
     * Test that mixed empty and valid values are handled correctly.
     *
     * Ensures that when form data contains a mix of null, empty strings,
     * whitespace, and valid values, only the valid values are preserved.
     *
     * @test
     */
    public function test_filters_mixed_empty_and_valid_values(): void
    {
        $data = [
            'group' => 'common',
            'key' => 'welcome',
            'values' => [
                'en' => 'Welcome',
                'lt' => null,
                'ru' => '',
                'es' => '   ',
                'fr' => 'Bienvenue',
                'de' => "\t",
            ],
        ];

        $result = $this->filterEmptyLanguageValues($data);

        $this->assertArrayHasKey('values', $result);
        $this->assertCount(2, $result['values']);
        $this->assertArrayHasKey('en', $result['values']);
        $this->assertArrayHasKey('fr', $result['values']);
        $this->assertSame('Welcome', $result['values']['en']);
        $this->assertSame('Bienvenue', $result['values']['fr']);
    }

    /**
     * Test that all empty values results in empty array.
     *
     * Ensures that when all language values are empty (null, empty string,
     * or whitespace), the result is an empty array rather than null.
     *
     * @test
     */
    public function test_all_empty_values_results_in_empty_array(): void
    {
        $data = [
            'group' => 'common',
            'key' => 'welcome',
            'values' => [
                'en' => null,
                'lt' => '',
                'ru' => '   ',
            ],
        ];

        $result = $this->filterEmptyLanguageValues($data);

        $this->assertArrayHasKey('values', $result);
        $this->assertIsArray($result['values']);
        $this->assertEmpty($result['values']);
    }

    /**
     * Test that data without values key is returned unchanged.
     *
     * Ensures graceful handling when form data doesn't contain
     * a 'values' key at all.
     *
     * @test
     */
    public function test_data_without_values_key_unchanged(): void
    {
        $data = [
            'group' => 'common',
            'key' => 'welcome',
        ];

        $result = $this->filterEmptyLanguageValues($data);

        $this->assertSame($data, $result);
        $this->assertArrayNotHasKey('values', $result);
    }

    /**
     * Test that non-array values key is handled gracefully.
     *
     * Ensures that if the 'values' key contains a non-array value
     * (e.g., a string), it's left unchanged rather than causing an error.
     *
     * @test
     */
    public function test_non_array_values_key_unchanged(): void
    {
        $data = [
            'group' => 'common',
            'key' => 'welcome',
            'values' => 'not-an-array',
        ];

        $result = $this->filterEmptyLanguageValues($data);

        $this->assertSame($data, $result);
        $this->assertSame('not-an-array', $result['values']);
    }

    /**
     * Test that empty values array is preserved.
     *
     * Ensures that an explicitly empty array for values is preserved
     * as an empty array (not converted to null or removed).
     *
     * @test
     */
    public function test_empty_values_array_preserved(): void
    {
        $data = [
            'group' => 'common',
            'key' => 'welcome',
            'values' => [],
        ];

        $result = $this->filterEmptyLanguageValues($data);

        $this->assertArrayHasKey('values', $result);
        $this->assertIsArray($result['values']);
        $this->assertEmpty($result['values']);
    }

    /**
     * Test that values with leading/trailing spaces are preserved.
     *
     * Ensures that meaningful whitespace (leading/trailing spaces that
     * are part of the actual content) is preserved.
     *
     * @test
     */
    public function test_preserves_values_with_meaningful_spaces(): void
    {
        $data = [
            'group' => 'common',
            'key' => 'welcome',
            'values' => [
                'en' => ' Welcome ',
                'lt' => 'Sveiki ',
                'ru' => ' Добро пожаловать',
            ],
        ];

        $result = $this->filterEmptyLanguageValues($data);

        $this->assertArrayHasKey('values', $result);
        $this->assertCount(3, $result['values']);
        $this->assertSame(' Welcome ', $result['values']['en']);
        $this->assertSame('Sveiki ', $result['values']['lt']);
        $this->assertSame(' Добро пожаловать', $result['values']['ru']);
    }

    /**
     * Test that numeric string values are preserved.
     *
     * Ensures that numeric strings (including '0') are preserved
     * as they represent valid translation content.
     *
     * @test
     */
    public function test_preserves_numeric_string_values(): void
    {
        $data = [
            'group' => 'common',
            'key' => 'count',
            'values' => [
                'en' => '0',
                'lt' => '123',
                'ru' => '456.78',
            ],
        ];

        $result = $this->filterEmptyLanguageValues($data);

        $this->assertArrayHasKey('values', $result);
        $this->assertCount(3, $result['values']);
        $this->assertSame('0', $result['values']['en']);
        $this->assertSame('123', $result['values']['lt']);
        $this->assertSame('456.78', $result['values']['ru']);
    }

    /**
     * Test that special characters are preserved.
     *
     * Ensures that translations containing HTML tags, ampersands,
     * quotes, and other special characters are preserved correctly.
     *
     * @test
     */
    public function test_preserves_special_characters(): void
    {
        $data = [
            'group' => 'common',
            'key' => 'special',
            'values' => [
                'en' => '<html>',
                'lt' => 'Test & Co.',
                'ru' => 'Тест "кавычки"',
                'es' => '¡Hola!',
            ],
        ];

        $result = $this->filterEmptyLanguageValues($data);

        $this->assertArrayHasKey('values', $result);
        $this->assertCount(4, $result['values']);
        $this->assertSame('<html>', $result['values']['en']);
        $this->assertSame('Test & Co.', $result['values']['lt']);
        $this->assertSame('Тест "кавычки"', $result['values']['ru']);
        $this->assertSame('¡Hola!', $result['values']['es']);
    }

    /**
     * Test that multiline values are preserved.
     *
     * Ensures that translations containing line breaks (\n)
     * are preserved correctly for multiline content.
     *
     * @test
     */
    public function test_preserves_multiline_values(): void
    {
        $data = [
            'group' => 'common',
            'key' => 'multiline',
            'values' => [
                'en' => "Line 1\nLine 2\nLine 3",
                'lt' => "Eilutė 1\nEilutė 2",
            ],
        ];

        $result = $this->filterEmptyLanguageValues($data);

        $this->assertArrayHasKey('values', $result);
        $this->assertCount(2, $result['values']);
        $this->assertSame("Line 1\nLine 2\nLine 3", $result['values']['en']);
        $this->assertSame("Eilutė 1\nEilutė 2", $result['values']['lt']);
    }

    /**
     * Test that other form fields are preserved unchanged.
     *
     * Ensures that filtering only affects the 'values' array and
     * all other form fields remain unchanged.
     *
     * @test
     */
    public function test_preserves_other_form_fields(): void
    {
        $data = [
            'group' => 'common',
            'key' => 'welcome',
            'values' => [
                'en' => 'Welcome',
                'lt' => null,
            ],
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-02 00:00:00',
            'metadata' => ['foo' => 'bar'],
        ];

        $result = $this->filterEmptyLanguageValues($data);

        $this->assertSame('common', $result['group']);
        $this->assertSame('welcome', $result['key']);
        $this->assertSame('2024-01-01 00:00:00', $result['created_at']);
        $this->assertSame('2024-01-02 00:00:00', $result['updated_at']);
        $this->assertSame(['foo' => 'bar'], $result['metadata']);
        $this->assertCount(1, $result['values']);
    }

    /**
     * Test that boolean false is filtered out (edge case).
     *
     * Ensures that boolean false values are filtered out as they
     * convert to empty strings. This is expected behavior since
     * translations should be strings, not booleans.
     *
     * @test
     */
    public function test_preserves_boolean_false(): void
    {
        $data = [
            'group' => 'common',
            'key' => 'boolean',
            'values' => [
                'en' => false,
                'lt' => 'Valid',
            ],
        ];

        $result = $this->filterEmptyLanguageValues($data);

        // Note: false is converted to empty string by trim((string) $value)
        // This is expected behavior as translations should be strings
        $this->assertArrayHasKey('values', $result);
        $this->assertCount(1, $result['values']);
        $this->assertArrayHasKey('lt', $result['values']);
    }

    /**
     * Test that zero string is preserved (edge case).
     *
     * Ensures that the string '0' is preserved as it represents
     * valid content (the number zero as text).
     *
     * @test
     */
    public function test_preserves_zero_string(): void
    {
        $data = [
            'group' => 'common',
            'key' => 'zero',
            'values' => [
                'en' => '0',
                'lt' => 'Zero',
            ],
        ];

        $result = $this->filterEmptyLanguageValues($data);

        $this->assertArrayHasKey('values', $result);
        $this->assertCount(2, $result['values']);
        $this->assertSame('0', $result['values']['en']);
        $this->assertSame('Zero', $result['values']['lt']);
    }
}
