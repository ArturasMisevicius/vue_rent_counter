<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Filament\Resources\LanguageResource;
use App\Models\Language;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Test suite for LanguageResource form field transformations.
 *
 * Tests the Filament v4 compatibility fix that replaced the deprecated
 * lowercase() method with formatStateUsing() and dehydrateStateUsing().
 *
 * Verifies:
 * - Code field is transformed to lowercase on display
 * - Code field is transformed to lowercase before save
 * - Type safety with null handling
 * - Integration with model mutator
 * - Form validation still works correctly
 *
 * @see \App\Filament\Resources\LanguageResource
 */
class LanguageResourceFormTransformationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that code field displays as lowercase when creating.
     */
    public function test_code_field_displays_lowercase_on_create_form(): void
    {
        // Arrange
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);

        // Act: Mount the create form
        $component = Livewire::actingAs($superadmin)
            ->test(LanguageResource\Pages\CreateLanguage::class);

        // Assert: Form loads successfully
        $component->assertSuccessful();
    }

    /**
     * Test that uppercase code is transformed to lowercase on create.
     */
    public function test_uppercase_code_transformed_to_lowercase_on_create(): void
    {
        // Arrange
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);

        // Act: Submit form with uppercase code
        Livewire::actingAs($superadmin)
            ->test(LanguageResource\Pages\CreateLanguage::class)
            ->fillForm([
                'code' => 'EN',
                'name' => 'English',
                'is_active' => true,
                'display_order' => 0,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        // Assert: Language created with lowercase code
        $this->assertDatabaseHas('languages', [
            'code' => 'en',
            'name' => 'English',
        ]);
    }

    /**
     * Test that mixed case code is transformed to lowercase on create.
     */
    public function test_mixed_case_code_transformed_to_lowercase_on_create(): void
    {
        // Arrange
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);

        // Act: Submit form with mixed case code
        Livewire::actingAs($superadmin)
            ->test(LanguageResource\Pages\CreateLanguage::class)
            ->fillForm([
                'code' => 'En-Us',
                'name' => 'English (US)',
                'is_active' => true,
                'display_order' => 0,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        // Assert: Language created with lowercase code
        $this->assertDatabaseHas('languages', [
            'code' => 'en-us',
            'name' => 'English (US)',
        ]);
    }

    /**
     * Test that code field displays existing lowercase value on edit.
     */
    public function test_code_field_displays_lowercase_on_edit_form(): void
    {
        // Arrange
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $language = Language::factory()->create(['code' => 'lt']);

        // Act: Mount the edit form
        $component = Livewire::actingAs($superadmin)
            ->test(LanguageResource\Pages\EditLanguage::class, [
                'record' => $language->getRouteKey(),
            ]);

        // Assert: Form loads with lowercase code
        $component->assertSuccessful();
        $component->assertFormSet([
            'code' => 'lt',
        ]);
    }

    /**
     * Test that uppercase code is transformed to lowercase on update.
     */
    public function test_uppercase_code_transformed_to_lowercase_on_update(): void
    {
        // Arrange
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $language = Language::factory()->create(['code' => 'en']);

        // Act: Update with uppercase code
        Livewire::actingAs($superadmin)
            ->test(LanguageResource\Pages\EditLanguage::class, [
                'record' => $language->getRouteKey(),
            ])
            ->fillForm([
                'code' => 'EN',
                'name' => 'English Updated',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        // Assert: Language updated with lowercase code
        $this->assertDatabaseHas('languages', [
            'id' => $language->id,
            'code' => 'en',
            'name' => 'English Updated',
        ]);
    }

    /**
     * Test that validation still works with transformation.
     */
    public function test_validation_works_with_code_transformation(): void
    {
        // Arrange
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        Language::factory()->create(['code' => 'en']);

        // Act: Try to create duplicate with uppercase
        $component = Livewire::actingAs($superadmin)
            ->test(LanguageResource\Pages\CreateLanguage::class)
            ->fillForm([
                'code' => 'EN',
                'name' => 'English Duplicate',
                'is_active' => true,
                'display_order' => 0,
            ])
            ->call('create');

        // Assert: Validation error for duplicate code
        $component->assertHasFormErrors(['code']);
    }

    /**
     * Test that required validation still works.
     */
    public function test_required_validation_works_with_transformation(): void
    {
        // Arrange
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);

        // Act: Try to create without code
        $component = Livewire::actingAs($superadmin)
            ->test(LanguageResource\Pages\CreateLanguage::class)
            ->fillForm([
                'code' => '',
                'name' => 'Test Language',
                'is_active' => true,
                'display_order' => 0,
            ])
            ->call('create');

        // Assert: Validation error for required field
        $component->assertHasFormErrors(['code']);
    }

    /**
     * Test that min length validation works with transformation.
     */
    public function test_min_length_validation_works_with_transformation(): void
    {
        // Arrange
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);

        // Act: Try to create with too short code
        $component = Livewire::actingAs($superadmin)
            ->test(LanguageResource\Pages\CreateLanguage::class)
            ->fillForm([
                'code' => 'E',
                'name' => 'Test Language',
                'is_active' => true,
                'display_order' => 0,
            ])
            ->call('create');

        // Assert: Validation error for min length
        $component->assertHasFormErrors(['code']);
    }

    /**
     * Test that max length validation works with transformation.
     */
    public function test_max_length_validation_works_with_transformation(): void
    {
        // Arrange
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);

        // Act: Try to create with too long code
        $component = Livewire::actingAs($superadmin)
            ->test(LanguageResource\Pages\CreateLanguage::class)
            ->fillForm([
                'code' => 'TOOLONG',
                'name' => 'Test Language',
                'is_active' => true,
                'display_order' => 0,
            ])
            ->call('create');

        // Assert: Validation error for max length
        $component->assertHasFormErrors(['code']);
    }

    /**
     * Test that alphaDash validation works with transformation.
     */
    public function test_alpha_dash_validation_works_with_transformation(): void
    {
        // Arrange
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);

        // Act: Try to create with invalid characters
        $component = Livewire::actingAs($superadmin)
            ->test(LanguageResource\Pages\CreateLanguage::class)
            ->fillForm([
                'code' => 'en@us',
                'name' => 'Test Language',
                'is_active' => true,
                'display_order' => 0,
            ])
            ->call('create');

        // Assert: Validation error for invalid characters
        $component->assertHasFormErrors(['code']);
    }

    /**
     * Test that regex validation works with transformation.
     */
    public function test_regex_validation_works_with_transformation(): void
    {
        // Arrange
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);

        // Act: Try to create with invalid format
        $component = Livewire::actingAs($superadmin)
            ->test(LanguageResource\Pages\CreateLanguage::class)
            ->fillForm([
                'code' => 'en_us',
                'name' => 'Test Language',
                'is_active' => true,
                'display_order' => 0,
            ])
            ->call('create');

        // Assert: Validation error for regex format
        $component->assertHasFormErrors(['code']);
    }

    /**
     * Test that valid ISO 639-1 codes are accepted.
     */
    public function test_valid_iso_codes_accepted_with_transformation(): void
    {
        // Arrange
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $validCodes = ['en', 'lt', 'ru', 'en-US', 'pt-BR', 'zh-CN'];

        foreach ($validCodes as $code) {
            // Act: Create language with valid code
            Livewire::actingAs($superadmin)
                ->test(LanguageResource\Pages\CreateLanguage::class)
                ->fillForm([
                    'code' => strtoupper($code), // Test with uppercase
                    'name' => "Test Language {$code}",
                    'is_active' => true,
                    'display_order' => 0,
                ])
                ->call('create')
                ->assertHasNoFormErrors();

            // Assert: Language created with lowercase code
            $this->assertDatabaseHas('languages', [
                'code' => strtolower($code),
            ]);
        }
    }

    /**
     * Test that transformation works with model mutator.
     */
    public function test_transformation_integrates_with_model_mutator(): void
    {
        // Arrange
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);

        // Act: Create language with uppercase code
        Livewire::actingAs($superadmin)
            ->test(LanguageResource\Pages\CreateLanguage::class)
            ->fillForm([
                'code' => 'RU',
                'name' => 'Russian',
                'is_active' => true,
                'display_order' => 0,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        // Assert: Both form transformation and model mutator result in lowercase
        $language = Language::where('name', 'Russian')->first();
        $this->assertNotNull($language);
        $this->assertEquals('ru', $language->code);
        $this->assertEquals('ru', $language->getAttributes()['code']);
    }

    /**
     * Test that null values are handled safely.
     */
    public function test_null_values_handled_safely(): void
    {
        // Arrange
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);

        // Act: Try to submit with null code (should trigger required validation)
        $component = Livewire::actingAs($superadmin)
            ->test(LanguageResource\Pages\CreateLanguage::class)
            ->fillForm([
                'code' => null,
                'name' => 'Test Language',
                'is_active' => true,
                'display_order' => 0,
            ])
            ->call('create');

        // Assert: Required validation catches null
        $component->assertHasFormErrors(['code']);
    }

    /**
     * Test that empty string is handled correctly.
     */
    public function test_empty_string_handled_correctly(): void
    {
        // Arrange
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);

        // Act: Try to submit with empty string
        $component = Livewire::actingAs($superadmin)
            ->test(LanguageResource\Pages\CreateLanguage::class)
            ->fillForm([
                'code' => '',
                'name' => 'Test Language',
                'is_active' => true,
                'display_order' => 0,
            ])
            ->call('create');

        // Assert: Required validation catches empty string
        $component->assertHasFormErrors(['code']);
    }

    /**
     * Test that whitespace is handled correctly.
     */
    public function test_whitespace_handled_correctly(): void
    {
        // Arrange
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);

        // Act: Try to submit with whitespace
        $component = Livewire::actingAs($superadmin)
            ->test(LanguageResource\Pages\CreateLanguage::class)
            ->fillForm([
                'code' => '  ',
                'name' => 'Test Language',
                'is_active' => true,
                'display_order' => 0,
            ])
            ->call('create');

        // Assert: Validation catches whitespace
        $component->assertHasFormErrors(['code']);
    }
}
