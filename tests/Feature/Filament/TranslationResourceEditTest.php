<?php

declare(strict_types=1);

/**
 * TranslationResource Edit Functionality Test Suite
 *
 * Comprehensive test suite verifying the edit functionality of TranslationResource
 * with consolidated Filament v4 namespace imports and multi-language support.
 *
 * ## Test Coverage (26 tests)
 *
 * ### Namespace Consolidation (2 tests)
 * - Verifies consolidated `use Filament\Tables;` import
 * - Confirms proper namespace prefix usage (Tables\Actions\EditAction::make())
 *
 * ### Authorization & Access Control (4 tests)
 * - SUPERADMIN: Full edit access
 * - ADMIN/MANAGER/TENANT: No access (403 Forbidden)
 *
 * ### Form Validation (5 tests)
 * - Required field validation (group, key)
 * - Max length validation (group: 120, key: 255)
 * - Alpha-dash format validation for group field
 *
 * ### Multi-Language Support (4 tests)
 * - Single/multiple language value updates
 * - Clearing and adding language values
 *
 * ### Database Persistence (3 tests)
 * - Translation updates and timestamp management
 *
 * ### Edge Cases (4 tests)
 * - Special characters, HTML, multiline, long text
 *
 * ### UI Behavior (2 tests)
 * - Active/inactive language field display
 *
 * ### Performance (1 test)
 * - Update operation < 500ms
 *
 * @package Tests\Feature\Filament
 * @group filament
 * @group translation
 * @group edit
 * @group namespace-consolidation
 */

use App\Enums\UserRole;
use App\Filament\Resources\TranslationResource;
use App\Filament\Resources\TranslationResource\Pages\EditTranslation;
use App\Models\Language;
use App\Models\Translation;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
    $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
    $this->manager = User::factory()->create(['role' => UserRole::MANAGER]);
    $this->tenant = User::factory()->create(['role' => UserRole::TENANT]);

    $this->english = Language::factory()->create([
        'code' => 'en',
        'name' => 'English',
        'is_active' => true,
        'is_default' => true,
        'display_order' => 1,
    ]);

    $this->lithuanian = Language::factory()->create([
        'code' => 'lt',
        'name' => 'Lithuanian',
        'is_active' => true,
        'is_default' => false,
        'display_order' => 2,
    ]);

    $this->russian = Language::factory()->create([
        'code' => 'ru',
        'name' => 'Russian',
        'is_active' => true,
        'is_default' => false,
        'display_order' => 3,
    ]);

    $this->translation = Translation::factory()->create([
        'group' => 'app',
        'key' => 'welcome',
        'values' => [
            'en' => 'Welcome',
            'lt' => 'Sveiki',
            'ru' => 'Добро пожаловать',
        ],
    ]);
});

// Namespace Consolidation Tests
test('translation resource uses consolidated tables namespace', function () {
    $reflection = new \ReflectionClass(TranslationResource::class);
    $fileContent = file_get_contents($reflection->getFileName());

    expect($fileContent)->toContain('use Filament\Tables;')
        ->and($fileContent)->not->toContain('use Filament\Tables\Actions\EditAction;')
        ->and($fileContent)->not->toContain('use Filament\Tables\Actions\DeleteAction;');
});

test('edit action uses namespace prefix', function () {
    $reflection = new \ReflectionClass(TranslationResource::class);
    $fileContent = file_get_contents($reflection->getFileName());

    expect($fileContent)->toContain('Tables\Actions\EditAction::make()');
});

// Edit Form Accessibility Tests
test('superadmin can access edit translation page', function () {
    Livewire::actingAs($this->superadmin)
        ->test(EditTranslation::class, ['record' => $this->translation->id])
        ->assertSuccessful();
});

test('admin cannot access edit translation page', function () {
    Livewire::actingAs($this->admin)
        ->test(EditTranslation::class, ['record' => $this->translation->id])
        ->assertForbidden();
});

test('manager cannot access edit translation page', function () {
    Livewire::actingAs($this->manager)
        ->test(EditTranslation::class, ['record' => $this->translation->id])
        ->assertForbidden();
});

test('tenant cannot access edit translation page', function () {
    Livewire::actingAs($this->tenant)
        ->test(EditTranslation::class, ['record' => $this->translation->id])
        ->assertForbidden();
});

// Form Field Validation Tests
test('edit form requires group field', function () {
    Livewire::actingAs($this->superadmin)
        ->test(EditTranslation::class, ['record' => $this->translation->id])
        ->fillForm(['group' => '', 'key' => 'updated_key'])
        ->call('save')
        ->assertHasFormErrors(['group' => 'required']);
});

test('edit form requires key field', function () {
    Livewire::actingAs($this->superadmin)
        ->test(EditTranslation::class, ['record' => $this->translation->id])
        ->fillForm(['group' => 'app', 'key' => ''])
        ->call('save')
        ->assertHasFormErrors(['key' => 'required']);
});

test('edit form validates group max length', function () {
    Livewire::actingAs($this->superadmin)
        ->test(EditTranslation::class, ['record' => $this->translation->id])
        ->fillForm(['group' => str_repeat('a', 121), 'key' => 'test_key'])
        ->call('save')
        ->assertHasFormErrors(['group']);
});

test('edit form validates key max length', function () {
    Livewire::actingAs($this->superadmin)
        ->test(EditTranslation::class, ['record' => $this->translation->id])
        ->fillForm(['group' => 'app', 'key' => str_repeat('a', 256)])
        ->call('save')
        ->assertHasFormErrors(['key']);
});

test('edit form validates group alpha dash', function () {
    Livewire::actingAs($this->superadmin)
        ->test(EditTranslation::class, ['record' => $this->translation->id])
        ->fillForm(['group' => 'invalid group!', 'key' => 'test_key'])
        ->call('save')
        ->assertHasFormErrors(['group']);
});

// Multi-Language Value Handling Tests
test('can update single language value', function () {
    Livewire::actingAs($this->superadmin)
        ->test(EditTranslation::class, ['record' => $this->translation->id])
        ->fillForm([
            'group' => 'app',
            'key' => 'welcome',
            'values' => ['en' => 'Welcome Updated', 'lt' => 'Sveiki', 'ru' => 'Добро пожаловать'],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->translation->refresh();
    expect($this->translation->values['en'])->toBe('Welcome Updated')
        ->and($this->translation->values['lt'])->toBe('Sveiki')
        ->and($this->translation->values['ru'])->toBe('Добро пожаловать');
});

test('can update multiple language values', function () {
    Livewire::actingAs($this->superadmin)
        ->test(EditTranslation::class, ['record' => $this->translation->id])
        ->fillForm([
            'group' => 'app',
            'key' => 'welcome',
            'values' => [
                'en' => 'Welcome Updated',
                'lt' => 'Sveiki Atnaujinta',
                'ru' => 'Добро пожаловать Обновлено',
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->translation->refresh();
    expect($this->translation->values['en'])->toBe('Welcome Updated')
        ->and($this->translation->values['lt'])->toBe('Sveiki Atnaujinta')
        ->and($this->translation->values['ru'])->toBe('Добро пожаловать Обновлено');
});

test('can clear language value', function () {
    Livewire::actingAs($this->superadmin)
        ->test(EditTranslation::class, ['record' => $this->translation->id])
        ->fillForm([
            'group' => 'app',
            'key' => 'welcome',
            'values' => ['en' => 'Welcome', 'lt' => '', 'ru' => 'Добро пожаловать'],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->translation->refresh();
    expect($this->translation->values['en'])->toBe('Welcome')
        ->and($this->translation->values)->not->toHaveKey('lt')
        ->and($this->translation->values['ru'])->toBe('Добро пожаловать');
});

test('can add new language value', function () {
    $spanish = Language::factory()->create([
        'code' => 'es',
        'name' => 'Spanish',
        'is_active' => true,
        'is_default' => false,
        'display_order' => 4,
    ]);

    Livewire::actingAs($this->superadmin)
        ->test(EditTranslation::class, ['record' => $this->translation->id])
        ->fillForm([
            'group' => 'app',
            'key' => 'welcome',
            'values' => [
                'en' => 'Welcome',
                'lt' => 'Sveiki',
                'ru' => 'Добро пожаловать',
                'es' => 'Bienvenido',
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->translation->refresh();
    expect($this->translation->values['es'])->toBe('Bienvenido');
});

// Database Persistence Tests
test('updating translation persists to database', function () {
    Livewire::actingAs($this->superadmin)
        ->test(EditTranslation::class, ['record' => $this->translation->id])
        ->fillForm([
            'group' => 'common',
            'key' => 'greeting',
            'values' => ['en' => 'Hello', 'lt' => 'Labas', 'ru' => 'Привет'],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->translation->fresh())
        ->group->toBe('common')
        ->key->toBe('greeting')
        ->values->en->toBe('Hello')
        ->values->lt->toBe('Labas')
        ->values->ru->toBe('Привет');
});

test('updating translation updates timestamps', function () {
    $originalUpdatedAt = $this->translation->updated_at;
    sleep(1);

    Livewire::actingAs($this->superadmin)
        ->test(EditTranslation::class, ['record' => $this->translation->id])
        ->fillForm(['group' => 'app', 'key' => 'welcome_updated', 'values' => ['en' => 'Welcome Updated']])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->translation->refresh();
    expect($this->translation->updated_at)->not->toEqual($originalUpdatedAt)
        ->and($this->translation->updated_at->isAfter($originalUpdatedAt))->toBeTrue();
});

test('can update translation with same group as other translations', function () {
    Translation::factory()->create(['group' => 'app', 'key' => 'goodbye', 'values' => ['en' => 'Goodbye']]);

    Livewire::actingAs($this->superadmin)
        ->test(EditTranslation::class, ['record' => $this->translation->id])
        ->fillForm(['group' => 'app', 'key' => 'welcome_updated', 'values' => ['en' => 'Welcome Updated']])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->translation->fresh())
        ->group->toBe('app')
        ->key->toBe('welcome_updated');
});

// Edge Case Tests
test('can update translation with special characters in values', function () {
    Livewire::actingAs($this->superadmin)
        ->test(EditTranslation::class, ['record' => $this->translation->id])
        ->fillForm([
            'group' => 'app',
            'key' => 'special_chars',
            'values' => [
                'en' => 'Hello! @#$%^&*()_+-=[]{}|;:\'",.<>?/',
                'lt' => 'Labas! ąčęėįšųūž',
                'ru' => 'Привет! абвгдеёжзийклмнопрстуфхцчшщъыьэюя',
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->translation->refresh();
    expect($this->translation->values['en'])->toBe('Hello! @#$%^&*()_+-=[]{}|;:\'",.<>?/')
        ->and($this->translation->values['lt'])->toBe('Labas! ąčęėįšųūž')
        ->and($this->translation->values['ru'])->toBe('Привет! абвгдеёжзийклмнопрстуфхцчшщъыьэюя');
});

test('can update translation with html in values', function () {
    Livewire::actingAs($this->superadmin)
        ->test(EditTranslation::class, ['record' => $this->translation->id])
        ->fillForm([
            'group' => 'app',
            'key' => 'html_content',
            'values' => [
                'en' => '<strong>Bold</strong> and <em>italic</em> text',
                'lt' => '<a href="#">Nuoroda</a>',
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->translation->refresh();
    expect($this->translation->values['en'])->toBe('<strong>Bold</strong> and <em>italic</em> text')
        ->and($this->translation->values['lt'])->toBe('<a href="#">Nuoroda</a>');
});

test('can update translation with multiline values', function () {
    $multilineText = "Line 1\nLine 2\nLine 3";

    Livewire::actingAs($this->superadmin)
        ->test(EditTranslation::class, ['record' => $this->translation->id])
        ->fillForm(['group' => 'app', 'key' => 'multiline', 'values' => ['en' => $multilineText]])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->translation->refresh();
    expect($this->translation->values['en'])->toBe($multilineText);
});

test('can update translation with very long values', function () {
    $longText = str_repeat('This is a very long translation text. ', 50);

    Livewire::actingAs($this->superadmin)
        ->test(EditTranslation::class, ['record' => $this->translation->id])
        ->fillForm(['group' => 'app', 'key' => 'long_text', 'values' => ['en' => $longText]])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->translation->refresh();
    expect($this->translation->values['en'])->toBe($longText);
});

// UI Behavior Tests
test('edit form displays all active language fields', function () {
    $component = Livewire::actingAs($this->superadmin)
        ->test(EditTranslation::class, ['record' => $this->translation->id]);

    $component->assertFormFieldExists('values.en')
        ->assertFormFieldExists('values.lt')
        ->assertFormFieldExists('values.ru');
});

test('edit form does not display inactive language fields', function () {
    Language::factory()->create([
        'code' => 'de',
        'name' => 'German',
        'is_active' => false,
        'display_order' => 5,
    ]);

    $component = Livewire::actingAs($this->superadmin)
        ->test(EditTranslation::class, ['record' => $this->translation->id]);

    $component->assertFormFieldDoesNotExist('values.de');
});

// Performance Test
test('updating translation completes in reasonable time', function () {
    $startTime = microtime(true);

    Livewire::actingAs($this->superadmin)
        ->test(EditTranslation::class, ['record' => $this->translation->id])
        ->fillForm([
            'group' => 'app',
            'key' => 'welcome_updated',
            'values' => [
                'en' => 'Welcome Updated',
                'lt' => 'Sveiki Atnaujinta',
                'ru' => 'Добро пожаловать Обновлено',
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $executionTime = (microtime(true) - $startTime) * 1000;
    expect($executionTime)->toBeLessThan(500);
});
