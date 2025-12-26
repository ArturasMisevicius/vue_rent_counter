<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Collection;

/**
 * Translation Cache Service
 * 
 * Provides caching for translation data to improve performance
 * in multi-locale applications with frequent translation lookups.
 */
final readonly class TranslationCacheService
{
    private const CACHE_PREFIX = 'translations';
    private const DEFAULT_TTL = 3600; // 1 hour

    public function __construct(
        private CacheRepository $cache,
        private string $defaultLocale,
        private array $availableLocales,
    ) {}

    /**
     * Get cached translation or store if not exists
     */
    public function remember(string $key, string $locale, callable $callback, ?int $ttl = null): mixed
    {
        $cacheKey = $this->buildCacheKey($key, $locale);
        
        return $this->cache->remember(
            $cacheKey,
            $ttl ?? self::DEFAULT_TTL,
            $callback
        );
    }

    /**
     * Store translation in cache with performance tracking
     */
    public function put(string $key, string $locale, mixed $value, ?int $ttl = null): bool
    {
        $cacheKey = $this->buildCacheKey($key, $locale);
        
        $startTime = microtime(true);
        $result = $this->cache->put($cacheKey, $value, $ttl ?? self::DEFAULT_TTL);
        $duration = microtime(true) - $startTime;
        
        // Log slow cache operations
        if ($duration > 0.01) { // 10ms threshold
            Log::debug('Slow translation cache put operation', [
                'key' => $key,
                'locale' => $locale,
                'duration_ms' => round($duration * 1000, 2),
            ]);
        }
        
        return $result;
    }

    /**
     * Get translation from cache
     */
    public function get(string $key, string $locale, mixed $default = null): mixed
    {
        $cacheKey = $this->buildCacheKey($key, $locale);
        
        return $this->cache->get($cacheKey, $default);
    }

    /**
     * Forget translation from cache
     */
    public function forget(string $key, ?string $locale = null): bool
    {
        if ($locale) {
            $cacheKey = $this->buildCacheKey($key, $locale);
            return $this->cache->forget($cacheKey);
        }

        // Forget for all locales
        $success = true;
        foreach (array_keys($this->availableLocales) as $availableLocale) {
            $cacheKey = $this->buildCacheKey($key, $availableLocale);
            $success = $this->cache->forget($cacheKey) && $success;
        }

        return $success;
    }

    /**
     * Clear all translation cache
     */
    public function flush(): bool
    {
        return $this->cache->flush();
    }

    /**
     * Get all cached translations for a locale
     */
    public function getAllForLocale(string $locale): Collection
    {
        $pattern = $this->buildCacheKey('*', $locale);
        
        // This is cache-driver dependent - Redis supports pattern matching
        if (method_exists($this->cache, 'keys')) {
            $keys = $this->cache->keys($pattern);
            return collect($keys)->mapWithKeys(function ($key) {
                return [$key => $this->cache->get($key)];
            });
        }

        return collect();
    }

    /**
     * Warm up cache for all locales
     */
    public function warmUp(array $translationKeys): void
    {
        foreach (array_keys($this->availableLocales) as $locale) {
            foreach ($translationKeys as $key) {
                if (!$this->has($key, $locale)) {
                    $translation = __($key, [], $locale);
                    if ($translation !== $key) {
                        $this->put($key, $locale, $translation);
                    }
                }
            }
        }
    }

    /**
     * Check if translation exists in cache
     */
    public function has(string $key, string $locale): bool
    {
        $cacheKey = $this->buildCacheKey($key, $locale);
        
        return $this->cache->has($cacheKey);
    }

    /**
     * Build cache key for translation
     */
    private function buildCacheKey(string $key, string $locale): string
    {
        return sprintf('%s.%s.%s', self::CACHE_PREFIX, $locale, $key);
    }

    /**
     * Get cache statistics
     */
    public function getStats(): array
    {
        $stats = [
            'total_keys' => 0,
            'by_locale' => [],
        ];

        foreach (array_keys($this->availableLocales) as $locale) {
            $localeKeys = $this->getAllForLocale($locale);
            $stats['by_locale'][$locale] = $localeKeys->count();
            $stats['total_keys'] += $localeKeys->count();
        }

        return $stats;
    }
}