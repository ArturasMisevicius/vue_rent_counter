<?php

namespace App\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;

final readonly class TranslationCacheService
{
    private const string CACHE_PREFIX = 'translations';

    private const int DEFAULT_TTL_SECONDS = 3600;

    /**
     * @param  array<string, string>  $supportedLocales
     */
    public function __construct(
        private CacheRepository $cache,
        private string $defaultLocale,
        private array $supportedLocales = ['en' => 'EN', 'lt' => 'LT', 'ru' => 'RU'],
    ) {}

    public function remember(string $key, string $locale, callable $callback, ?int $ttl = null): mixed
    {
        $cacheKey = $this->buildCacheKey($key, $locale);

        return $this->cache->remember(
            $cacheKey,
            $ttl ?? self::DEFAULT_TTL_SECONDS,
            $callback,
        );
    }

    public function get(string $key, string $locale, mixed $default = null): mixed
    {
        return $this->cache->get($this->buildCacheKey($key, $locale), $default);
    }

    public function put(string $key, string $locale, mixed $value, ?int $ttl = null): bool
    {
        return $this->cache->put(
            $this->buildCacheKey($key, $locale),
            $value,
            $ttl ?? self::DEFAULT_TTL_SECONDS,
        );
    }

    public function forget(string $key, ?string $locale = null): bool
    {
        if ($locale === null) {
            return collect(array_keys($this->supportedLocales))
                ->every(fn (string $supportedLocale): bool => $this->cache->forget($this->buildCacheKey($key, $supportedLocale)));
        }

        return $this->cache->forget($this->buildCacheKey($key, $locale));
    }

    public function flush(): bool
    {
        return $this->cache->flush();
    }

    public function supportedLocales(): array
    {
        return array_keys($this->supportedLocales);
    }

    public function buildCacheKey(string $key, string $locale): string
    {
        return sprintf('%s.%s.%s', self::CACHE_PREFIX, $locale, $key);
    }

    public function resolveLocale(string $locale): string
    {
        $normalizedLocale = strtolower($locale);
        if ($normalizedLocale === '') {
            return $this->defaultLocale;
        }

        return in_array($normalizedLocale, $this->supportedLocales(), true)
            ? $normalizedLocale
            : $this->defaultLocale;
    }
}
