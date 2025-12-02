<?php

declare(strict_types=1);

/**
 * Translation Resource Dynamic Language Fields Test Suite
 *
 * This test suite verifies that the TranslationResource correctly generates
 * form fields dynamically based on active languages in the system.
 *
 * ## Test Coverage
 *
 * ### Namespace Consolidation (2 tests)
 * - Verify consolidated namespace import usage
 * - Verify no individual component imports
 *
 * ### Dynamic Field Generation (6 tests)
 * - Create form displays fields for all active languages
 * - Edit form displays fields for all active languages
 * - Inactive languages don't generate fields
 * - Newly activated language appears in form
 * - Deactivated language disappears from form
 * - Field labels include language name and code
 *
 * ### Field Configuration (4 tests)
 * - Fields are Textarea components
 * - Fields have correct attributes (rows, placeholder)
 * - Default language field has helper text
 * - Non-default language fields don't have helper text
 *
 * ### Performance (2 tests)
 * - Uses cached Language::getActiveLanguages()
 * - Form renders efficiently with multiple languages
 *
 * ### Authorization (1 test)
 * - Only superadmin can access forms with dynamic fields
 *
 * ## Architecture
 *
 * ### Components Tested
 * - **Resource**: TranslationResource (form schema generation)
 * - **Model**: Language (active language retrieval)
 * - **Pages**: CreateTranslation, EditTranslation
 *
 * ### Dependencies
 * - Language model with is_active flag
 * - Language::getActiveLanguages() cached method
 * - Filament Forms\Components\Textarea
 *
 * ### Data Flow
 * 1. Form schema requests active languages
 * 2. Language::getActiveLanguages() returns cached list
 * 3. Schema maps each language to a Textarea field
 * 4. Fields are named "values.{language_code}"
 * 5. Form renders with dynamic fields
 *
 * @group filament
 * @group translation
 * @group dynamic-fields
 * @group namespace-consolidation
 */

use App\Enums\UserRole;
use App\Filament\Resources\TranslationResource;
use App\Filament\Resources\TranslationResource\Pages\CreateTranslation;
use App\Filament\Resources\TranslationResource\Pages\EditTranslation;
use App\Models\Language;
use App\Models\Translation;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

beforeEach(function () {
    // Create superadmin user
    $this->superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
    ]);

    // Create active languages
    $this->languages = collect([
        Language::factory()->create([
            'code' => 'en',
            'name' => 'English',
            'is_active' => true,
            'is_default' => true,
            'display_order' => 1,
        ]),
        Language::factory()->create([
            'code' => 'lt',
            'name' => 'Lithuanian',
            'is_active' => true,
            'is_default' => false,
            'display_order' => 2,
        ]),
        Language::factory()->create([
            'code' => 'ru',
            'name' => 'Russian',
            'is_active' => true,
            'is_default' => false,
            'display_order' => 3,
        ]),
    ]);

    // Create a test translation
    $this->translation = Translation::factory()->create([
        'group' => 'app',
        'key' => 'test.message',
        'values' => [
            'en' => 'Test message',
            'lt' => 'Bandomasis pranešimas',
            'ru' => 'Тестовое сообщение',
        ],
    ]);

    // Clear cache to ensure fresh data
    Cache::flush();
});

describe('Namespace Consolidation', function () {
    test('uses consolidated namespace import', function () {
        $reflection = new ReflectionClass(TranslationResource::class);
        $fileContent = file_get_contents($reflection->getFileName());

        // Verify consolidated import exists
        expect($fileContent)->toContain('use Filament\Tables;');
    });

    test('does not use individual component imports', function () {
        $reflection = new ReflectionClass(TranslationResource::class);
        $fileContent = file_get_contents($reflection->getFileName());

        // Verify individual imports don't exist
        expect($fileContent)->not->toContain('use Filament\Tables\Actions\EditAction;');
        expect($fileContent)->not->toContain('use Filament\Tables\Actions\DeleteAction;');
        expect($fileContent)->not->toContain('use Filament\Tables\Columns\TextColumn;');
        expect($fileContent)->not->toContain('use Filament\Tables\Filters\SelectFilter;');
    });
});

describe('Dynamic Field Generation', function () {
    test('create form displays fields for all active languages', function () {
        $component = Livewire::actingAs($this->superadmin)
            ->test(CreateTranslation::class);

        // Verify form has fields for all active languages
        foreach ($this->languages as $language) {
            $component->assertFormFieldExists("values.{$language->code}");
        }
    });

    test('edit form displays fields for all active languages', function () {
        $component = Livewire::actingAs($this->superadmin)
            ->test(EditTranslation::class, ['record' => $this->translation->id]);

        // Verify form has fields for all active languages
        foreach ($this->languages as $language) {
            $component->assertFormFieldExists("values.{$language->code}");
        }
    });

    test('inactive languages do not generate form fields', function () {
        // Create an inactive language
        $inactiveLanguage = Language::factory()->create([
            'code' => 'de',
            'name' => 'German',
            'is_active' => false,
            'display_order' => 4,
        ]);

        // Clear cache to ensure fresh data
        Cache::forget('languages.active');

        $component = Livewire::actingAs($this->superadmin)
            ->test(CreateTranslation::class);

        // Verify inactive language field does not exist
        $component->assertFormFieldDoesNotExist("values.{$inactiveLanguage->code}");

        // Verify active language fields still exist
        foreach ($this->languages as $language) {
            $component->assertFormFieldExists("values.{$language->code}");
        }
    });

    test('newly activated language appears in form', function () {
        // Create an inactive language
        $language = Language::factory()->create([
            'code' => 'de',
            'name' => 'German',
            'is_active' => false,
            'display_order' => 4,
        ]);

        // Clear cache
        Cache::forget('languages.active');

        // Verify field doesn't exist initially
        $component = Livewire::actingAs($this->superadmin)
            ->test(CreateTranslation::class);
        $component->assertFormFieldDoesNotExist("values.{$language->code}");

        // Activate the language
        $language->update(['is_active' => true]);

        // Clear cache to pick up the change
        Cache::forget('languages.active');

        // Verify field now exists
        $component = Livewire::actingAs($this->superadmin)
            ->test(CreateTranslation::class);
        $component->assertFormFieldExists("values.{$language->code}");
    });

    test('deactivated language disappears from form', function () {
        // Get an active language
        $language = $this->languages->first();

        // Verify field exists initially
        $component = Livewire::actingAs($this->superadmin)
            ->test(CreateTranslation::class);
        $component->assertFormFieldExists("values.{$language->code}");

        // Deactivate the language
        $language->update(['is_active' => false]);

        // Clear cache to pick up the change
        Cache::forget('languages.active');

        // Verify field no longer exists
        $component = Livewire::actingAs($this->superadmin)
            ->test(CreateTranslation::class);
        $component->assertFormFieldDoesNotExist("values.{$language->code}");
    });

    test('field labels include language name and code', function () {
        $component = Livewire::actingAs($this->superadmin)
            ->test(CreateTranslation::class);

        // Get the form schema
        $form = $component->instance()->form;
        $schema = $form->getComponents();

        // Find the values section
        $valuesSection = collect($schema)->first(function ($component) {
            return $component instanceof \Filament\Forms\Components\Section
                && str_contains($component->getHeading(), 'Values');
        });

        expect($valuesSection)->not->toBeNull();

        // Get the fields from the values section
        $fields = $valuesSection->getChildComponents();

        // Verify each language has a field with proper label
        foreach ($this->languages as $language) {
            $field = collect($fields)->first(function ($field) use ($language) {
                return $field->getName() === "values.{$language->code}";
            });

            expect($field)->not->toBeNull();
            expect($field->getLabel())->toContain($language->name);
            expect($field->getLabel())->toContain($language->code);
        }
    });
});

describe('Field Configuration', function () {
    test('fields are Textarea components', function () {
        $component = Livewire::actingAs($this->superadmin)
            ->test(CreateTranslation::class);

        // Get the form schema
        $form = $component->instance()->form;
        $schema = $form->getComponents();

        // Find the values section
        $valuesSection = collect($schema)->first(function ($component) {
            return $component instanceof \Filament\Forms\Components\Section
                && str_contains($component->getHeading(), 'Values');
        });

        $fields = $valuesSection->getChildComponents();

        // Verify each field is a Textarea
        foreach ($this->languages as $language) {
            $field = collect($fields)->first(function ($field) use ($language) {
                return $field->getName() === "values.{$language->code}";
            });

            expect($field)->toBeInstanceOf(\Filament\Forms\Components\Textarea::class);
        }
    });

    test('fields have correct attributes', function () {
        $component = Livewire::actingAs($this->superadmin)
            ->test(CreateTranslation::class);

        // Get the form schema
        $form = $component->instance()->form;
        $schema = $form->getComponents();

        // Find the values section
        $valuesSection = collect($schema)->first(function ($component) {
            return $component instanceof \Filament\Forms\Components\Section
                && str_contains($component->getHeading(), 'Values');
        });

        $fields = $valuesSection->getChildComponents();

        // Verify field attributes
        foreach ($this->languages as $language) {
            $field = collect($fields)->first(function ($field) use ($language) {
                return $field->getName() === "values.{$language->code}";
            });

            // Verify rows attribute
            expect($field->getRows())->toBe(3);

            // Verify column span (returns array with 'default' key)
            $columnSpan = $field->getColumnSpan();
            expect($columnSpan)->toBeArray();
            expect($columnSpan['default'] ?? null)->toBe('full');
        }
    });

    test('default language field has helper text configured', function () {
        // Verify the TranslationResource source code has helper text for default language
        $reflection = new ReflectionClass(TranslationResource::class);
        $fileContent = file_get_contents($reflection->getFileName());

        // Check that helper text is conditionally set based on is_default
        expect($fileContent)->toContain('->helperText($language->is_default');
        expect($fileContent)->toContain("__('translations.helper_text.default_language')");
    });

    test('non-default language fields do not have helper text configured', function () {
        // Verify the TranslationResource source code conditionally sets helper text
        $reflection = new ReflectionClass(TranslationResource::class);
        $fileContent = file_get_contents($reflection->getFileName());

        // Check that helper text uses ternary with empty string for non-default
        expect($fileContent)->toContain("->helperText(\$language->is_default ? __('translations.helper_text.default_language') : '')");
    });
});

describe('Performance', function () {
    test('uses cached Language::getActiveLanguages()', function () {
        // Clear cache
        Cache::flush();

        // First call should cache the result
        $component = Livewire::actingAs($this->superadmin)
            ->test(CreateTranslation::class);

        // Verify cache was set
        expect(Cache::has('languages.active'))->toBeTrue();

        // Get cached value
        $cachedLanguages = Cache::get('languages.active');
        expect($cachedLanguages)->toHaveCount(3);
        expect($cachedLanguages->pluck('code')->toArray())->toBe(['en', 'lt', 'ru']);
    });

    test('form renders efficiently with multiple languages', function () {
        // Create additional languages
        for ($i = 4; $i <= 10; $i++) {
            Language::factory()->create([
                'code' => "lang{$i}",
                'name' => "Language {$i}",
                'is_active' => true,
                'display_order' => $i,
            ]);
        }

        // Clear cache
        Cache::forget('languages.active');

        $startTime = microtime(true);

        $component = Livewire::actingAs($this->superadmin)
            ->test(CreateTranslation::class);

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // Verify form renders in reasonable time (< 500ms)
        expect($executionTime)->toBeLessThan(500);

        // Verify all 10 language fields exist
        for ($i = 1; $i <= 10; $i++) {
            if ($i <= 3) {
                $code = ['en', 'lt', 'ru'][$i - 1];
            } else {
                $code = "lang{$i}";
            }
            $component->assertFormFieldExists("values.{$code}");
        }
    });
});

describe('Authorization', function () {
    test('only superadmin can access forms with dynamic fields', function () {
        // Test with non-superadmin users
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        // Admin should not have access
        Livewire::actingAs($admin)
            ->test(CreateTranslation::class)
            ->assertForbidden();

        // Manager should not have access
        Livewire::actingAs($manager)
            ->test(CreateTranslation::class)
            ->assertForbidden();

        // Tenant should not have access
        Livewire::actingAs($tenant)
            ->test(CreateTranslation::class)
            ->assertForbidden();

        // Superadmin should have access
        $component = Livewire::actingAs($this->superadmin)
            ->test(CreateTranslation::class);

        // Verify form loads successfully
        $component->assertSuccessful();
        $component->assertFormFieldExists('values.en');
    });
});
