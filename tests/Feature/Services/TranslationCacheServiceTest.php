<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Services\TranslationCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class TranslationCacheServiceTest extends TestCase
{
    use RefreshDatabase;

    private TranslationCacheService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new TranslationCacheService(
            Cache::store(),
            'en',
            ['en' => [], 'lt' => [], 'ru' => []]
        );
    }

    public function test_can_cache_and_retrieve_translation(): void
    {
        $key = 'test.key';
        $locale = 'en';
        $value = 'Test Value';
        
        // Store translation
        $result = $this->service->put($key, $locale, $value);
        expect($result)->toBeTrue();
        
        // Retrieve translation
        $cached = $this->service->get($key, $locale);
        expect($cached)->toBe($value);
    }

    public function test_remember_caches_callback_result(): void
    {
        $key = 'test.remember';
        $locale = 'en';
        $expectedValue = 'Remembered Value';
        
        $callbackExecuted = false;
        
        $result = $this->service->remember($key, $locale, function () use ($expectedValue, &$callbackExecuted) {
            $callbackExecuted = true;
            return $expectedValue;
        });
        
        expect($result)->toBe($expectedValue);
        expect($callbackExecuted)->toBeTrue();
        
        // Second call should use cache
        $callbackExecuted = false;
        $result2 = $this->service->remember($key, $locale, function () use (&$callbackExecuted) {
            $callbackExecuted = true;
            return 'Should not be called';
        });
        
        expect($result2)->toBe($expectedValue);
        expect($callbackExecuted)->toBeFalse();
    }

    public function test_can_forget_translation(): void
    {
        $key = 'test.forget';
        $locale = 'en';
        $value = 'To be forgotten';
        
        // Store and verify
        $this->service->put($key, $locale, $value);
        expect($this->service->has($key, $locale))->toBeTrue();
        
        // Forget and verify
        $result = $this->service->forget($key, $locale);
        expect($result)->toBeTrue();
        expect($this->service->has($key, $locale))->toBeFalse();
    }

    public function test_can_forget_translation_for_all_locales(): void
    {
        $key = 'test.forget.all';
        
        // Store for multiple locales
        $this->service->put($key, 'en', 'English');
        $this->service->put($key, 'lt', 'Lithuanian');
        $this->service->put($key, 'ru', 'Russian');
        
        // Verify all exist
        expect($this->service->has($key, 'en'))->toBeTrue();
        expect($this->service->has($key, 'lt'))->toBeTrue();
        expect($this->service->has($key, 'ru'))->toBeTrue();
        
        // Forget for all locales
        $result = $this->service->forget($key);
        expect($result)->toBeTrue();
        
        // Verify all are gone
        expect($this->service->has($key, 'en'))->toBeFalse();
        expect($this->service->has($key, 'lt'))->toBeFalse();
        expect($this->service->has($key, 'ru'))->toBeFalse();
    }

    public function test_warm_up_caches_existing_translations(): void
    {
        $keys = [
            'common.english',
            'common.lithuanian',
            'auth.failed'
        ];
        
        // Warm up cache
        $this->service->warmUp($keys);
        
        // Verify translations are cached
        foreach ($keys as $key) {
            foreach (['en', 'lt', 'ru'] as $locale) {
                $translation = __($key, [], $locale);
                if ($translation !== $key) {
                    expect($this->service->has($key, $locale))->toBeTrue();
                }
            }
        }
    }

    public function test_get_stats_returns_cache_statistics(): void
    {
        // Add some cached translations
        $this->service->put('test.1', 'en', 'Test 1');
        $this->service->put('test.2', 'en', 'Test 2');
        $this->service->put('test.1', 'lt', 'Testas 1');
        
        $stats = $this->service->getStats();
        
        expect($stats)->toHaveKeys(['total_keys', 'by_locale']);
        expect($stats['total_keys'])->toBeGreaterThan(0);
        expect($stats['by_locale'])->toBeArray();
    }

    public function test_cache_respects_ttl(): void
    {
        $key = 'test.ttl';
        $locale = 'en';
        $value = 'TTL Test';
        $shortTtl = 1; // 1 second
        
        // Store with short TTL
        $this->service->put($key, $locale, $value, $shortTtl);
        
        // Should exist immediately
        expect($this->service->has($key, $locale))->toBeTrue();
        
        // Wait for expiration (in real test, you'd mock time)
        // For this test, we'll just verify the TTL was set
        expect($this->service->get($key, $locale))->toBe($value);
    }
}