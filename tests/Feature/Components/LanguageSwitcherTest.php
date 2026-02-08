<?php

declare(strict_types=1);

namespace Tests\Feature\Components;

use App\Models\Language;
use App\ValueObjects\LanguageData;
use App\View\Components\LanguageSwitcher;
use App\View\Components\Support\LanguageSwitcherStyles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Tests\TestCase;

final class LanguageSwitcherTest extends TestCase
{
    use RefreshDatabase;

    private Collection $testLanguages;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test languages
        Language::factory()->create([
            'code' => 'en',
            'name' => 'English',
            'native_name' => 'English',
            'is_active' => true,
            'display_order' => 1,
        ]);
        
        Language::factory()->create([
            'code' => 'lt',
            'name' => 'Lithuanian',
            'native_name' => 'Lietuvių',
            'is_active' => true,
            'display_order' => 2,
        ]);

        Language::factory()->create([
            'code' => 'ru',
            'name' => 'Russian',
            'native_name' => 'Русский',
            'is_active' => true,
            'display_order' => 3,
        ]);

        $this->testLanguages = Language::where('is_active', true)->orderBy('display_order')->get();
    }

    public function test_language_switcher_component_renders_correctly(): void
    {
        $view = $this->blade(
            '<x-language-switcher :languages="$languages" current-locale="en" />',
            ['languages' => $this->testLanguages]
        );

        $view->assertSee('English');
        $view->assertSee('Lietuvių');
        $view->assertSee('language-switcher-form');
        $view->assertSeeInOrder(['<select', 'id="language-select"']);
        $view->assertSee('data-language-switcher="true"');
        $view->assertSee('data-base-url');
    }

    public function test_language_switcher_has_proper_accessibility_attributes(): void
    {
        $view = $this->blade(
            '<x-language-switcher :languages="$languages" current-locale="en" />',
            ['languages' => $this->testLanguages]
        );

        $view->assertSee('aria-label');
        $view->assertSee('sr-only');
        $view->assertSee('Select Language');
    }

    public function test_language_switcher_shows_current_locale_as_selected(): void
    {
        $view = $this->blade(
            '<x-language-switcher :languages="$languages" current-locale="lt" />',
            ['languages' => $this->testLanguages]
        );

        $view->assertSee('value="lt" selected');
    }

    public function test_language_switcher_includes_noscript_fallback(): void
    {
        $view = $this->blade(
            '<x-language-switcher :languages="$languages" current-locale="en" />',
            ['languages' => $this->testLanguages]
        );

        $view->assertSee('<noscript>');
        $view->assertSee('Change Language');
    }

    public function test_language_switcher_dropdown_variant(): void
    {
        $view = $this->blade(
            '<x-language-switcher variant="dropdown" :languages="$languages" current-locale="en" />',
            ['languages' => $this->testLanguages]
        );

        $view->assertSee('x-data');
        $view->assertSee('aria-expanded');
        $view->assertSee('aria-haspopup');
        $view->assertSee('role="menu"');
        $view->assertSee('role="menuitem"');
        $view->assertSee('aria-current="true"'); // Current language indicator
    }

    public function test_language_switcher_component_class_methods(): void
    {
        $component = new LanguageSwitcher(
            variant: 'select',
            languages: $this->testLanguages,
            currentLocale: 'en'
        );

        $this->assertStringContainsString('bg-white/10', $component->getBaseClasses());
        $this->assertEquals('English', $component->getCurrentLanguageDisplay());
        $this->assertTrue($component->hasLanguages());
        $this->assertStringContainsString('language-switcher-container', $component->getContainerClasses());
    }

    public function test_language_switcher_with_invalid_variant_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid variant 'invalid'");

        new LanguageSwitcher(
            variant: 'invalid',
            languages: $this->testLanguages,
            currentLocale: 'en'
        );
    }

    public function test_language_switcher_with_empty_languages(): void
    {
        $view = $this->blade(
            '<x-language-switcher :languages="$languages" current-locale="en" />',
            ['languages' => collect()]
        );

        $view->assertSee('No languages available');
        $view->assertSee('role="status"');
        $view->assertSee('aria-live="polite"');
    }

    public function test_language_switcher_shows_codes_when_labels_disabled(): void
    {
        $view = $this->blade(
            '<x-language-switcher :languages="$languages" current-locale="en" :show-labels="false" />',
            ['languages' => $this->testLanguages]
        );

        $view->assertSee('EN');
        $view->assertSee('LT');
        $view->assertSee('RU');
        $view->assertDontSee('English');
        $view->assertDontSee('Lietuvių');
    }

    public function test_language_switcher_dropdown_shows_checkmark_for_current(): void
    {
        $view = $this->blade(
            '<x-language-switcher variant="dropdown" :languages="$languages" current-locale="lt" />',
            ['languages' => $this->testLanguages]
        );

        // Should show checkmark SVG for current language
        $view->assertSee('fill-rule="evenodd"'); // Part of checkmark SVG
        $view->assertSee('aria-current="true"');
    }

    public function test_language_switcher_handles_missing_native_names(): void
    {
        // Create language without native name
        $languageWithoutNative = Language::factory()->create([
            'code' => 'de',
            'name' => 'German',
            'native_name' => null,
            'is_active' => true,
            'display_order' => 4,
        ]);

        $languages = collect([$languageWithoutNative]);

        $view = $this->blade(
            '<x-language-switcher :languages="$languages" current-locale="de" />',
            ['languages' => $languages]
        );

        $view->assertSee('German'); // Should fall back to name
    }

    public function test_language_switcher_styles_class(): void
    {
        // Test LanguageSwitcherStyles class
        $this->assertStringContainsString('bg-white/10', LanguageSwitcherStyles::getVariantClasses('select'));
        $this->assertStringContainsString('flex items-center', LanguageSwitcherStyles::getVariantClasses('dropdown'));
        
        $this->assertTrue(LanguageSwitcherStyles::isValidVariant('select'));
        $this->assertTrue(LanguageSwitcherStyles::isValidVariant('dropdown'));
        $this->assertFalse(LanguageSwitcherStyles::isValidVariant('invalid'));
        
        $this->assertContains('select', LanguageSwitcherStyles::getSupportedVariants());
        $this->assertContains('dropdown', LanguageSwitcherStyles::getSupportedVariants());
    }

    public function test_language_data_value_object(): void
    {
        $language = $this->testLanguages->first();
        $languageData = LanguageData::fromModel($language);

        $this->assertEquals($language->code, $languageData->code);
        $this->assertEquals($language->name, $languageData->name);
        $this->assertEquals($language->native_name, $languageData->nativeName);
        $this->assertTrue($languageData->matches($language->code));
        $this->assertFalse($languageData->matches('invalid'));
        
        $this->assertEquals($language->native_name, $languageData->getDisplayName(true));
        $this->assertEquals($language->name, $languageData->getDisplayName(false));
        $this->assertEquals(strtoupper($language->code), $languageData->getUppercaseCode());
    }

    public function test_language_data_collection_conversion(): void
    {
        $languageDataCollection = LanguageData::fromCollection($this->testLanguages);

        $this->assertCount(3, $languageDataCollection);
        $this->assertInstanceOf(LanguageData::class, $languageDataCollection->first());
        
        $englishData = $languageDataCollection->first(fn (LanguageData $lang) => $lang->matches('en'));
        $this->assertNotNull($englishData);
        $this->assertEquals('English', $englishData->name);
    }

    public function test_language_switcher_custom_css_classes(): void
    {
        $view = $this->blade(
            '<x-language-switcher :languages="$languages" current-locale="en" class="custom-class" />',
            ['languages' => $this->testLanguages]
        );

        $view->assertSee('custom-class');
    }

    public function test_language_switcher_dropdown_keyboard_navigation(): void
    {
        $view = $this->blade(
            '<x-language-switcher variant="dropdown" :languages="$languages" current-locale="en" />',
            ['languages' => $this->testLanguages]
        );

        $view->assertSee('@keydown.escape="open = false"');
        $view->assertSee('@click.away="open = false"');
    }

    public function test_welcome_page_uses_language_switcher_component(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('language-switcher-form');
    }

    public function test_language_switching_works_correctly(): void
    {
        $response = $this->get(route('language.switch', 'lt'));

        $response->assertRedirect();
        $this->assertEquals('lt', session('locale'));
    }

    public function test_language_switcher_handles_array_input(): void
    {
        // Test with array input instead of Language models
        $arrayLanguages = collect([
            [
                'code' => 'en',
                'name' => 'English',
                'native_name' => 'English',
                'is_active' => true,
                'display_order' => 1,
            ],
            [
                'code' => 'fr',
                'name' => 'French',
                'native_name' => 'Français',
                'is_active' => true,
                'display_order' => 2,
            ],
        ]);

        $component = new LanguageSwitcher(
            languages: $arrayLanguages,
            currentLocale: 'en'
        );

        $this->assertTrue($component->hasLanguages());
        $this->assertEquals('English', $component->getCurrentLanguageDisplay());
    }
}