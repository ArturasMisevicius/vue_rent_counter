<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\Localization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_available_locales_returns_configured_locales(): void
    {
        $locales = Localization::availableLocales();

        expect($locales)->toHaveCount(3);
        // Lithuanian is first as the primary locale for this Vilnius-based platform
        expect($locales->pluck('code')->toArray())->toEqual(['lt', 'en', 'ru']);
    }

    public function test_fallback_locale_returns_english(): void
    {
        expect(Localization::fallbackLocale())->toBe('en');
    }

    public function test_current_locale_returns_app_locale(): void
    {
        app()->setLocale('lt');
        
        expect(Localization::currentLocale())->toBe('lt');
    }

    public function test_is_available_checks_locale_existence(): void
    {
        expect(Localization::isAvailable('en'))->toBeTrue();
        expect(Localization::isAvailable('lt'))->toBeTrue();
        expect(Localization::isAvailable('ru'))->toBeTrue();
        expect(Localization::isAvailable('fr'))->toBeFalse();
    }

    public function test_base_translation_keys_exist_in_all_locales(): void
    {
        $baseKeys = [
            'common.english',
            'common.lithuanian', 
            'common.russian',
            'common.language',
        ];

        foreach (['en', 'lt', 'ru'] as $locale) {
            app()->setLocale($locale);
            
            foreach ($baseKeys as $key) {
                expect(__($key))->not->toBe($key, "Translation key '{$key}' missing in locale '{$locale}'");
            }
        }
    }

    public function test_auth_translations_exist_in_all_locales(): void
    {
        $authKeys = [
            'auth.failed',
            'auth.password',
            'auth.throttle',
        ];

        foreach (['en', 'lt', 'ru'] as $locale) {
            app()->setLocale($locale);
            
            foreach ($authKeys as $key) {
                expect(__($key))->not->toBe($key, "Auth translation key '{$key}' missing in locale '{$locale}'");
            }
        }
    }

    public function test_validation_translations_exist_in_all_locales(): void
    {
        $validationKeys = [
            'validation.required',
            'validation.email',
            'validation.min.string',
        ];

        foreach (['en', 'lt', 'ru'] as $locale) {
            app()->setLocale($locale);
            
            foreach ($validationKeys as $key) {
                expect(__($key))->not->toBe($key, "Validation translation key '{$key}' missing in locale '{$locale}'");
            }
        }
    }
}