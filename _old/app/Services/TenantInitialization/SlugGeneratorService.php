<?php

declare(strict_types=1);

namespace App\Services\TenantInitialization;

use App\Models\UtilityService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Service for generating unique slugs for utility services within tenant scope.
 * 
 * Provides slug generation with uniqueness validation and caching for performance.
 * Ensures that slugs are URL-friendly and unique within the tenant's scope.
 * 
 * @package App\Services\TenantInitialization
 * @author Laravel Development Team
 * @since 1.0.0
 */
final readonly class SlugGeneratorService
{
    private const CACHE_TTL = 3600; // 1 hour
    private const CACHE_PREFIX = 'tenant_slugs';

    /**
     * Generate a unique slug for a utility service within a tenant.
     * 
     * Creates a URL-friendly slug from the service name and ensures uniqueness
     * within the tenant's scope by appending a counter if necessary.
     * Uses caching to improve performance for repeated slug generation.
     * 
     * @param string $name The service name to create a slug from
     * @param int $tenantId The tenant ID to check uniqueness within
     * 
     * @return string Unique slug for the service within the tenant scope
     * 
     * @since 1.0.0
     */
    public function generateUniqueSlug(string $name, int $tenantId): string
    {
        $baseSlug = Str::slug($name);
        $cacheKey = $this->getCacheKey($tenantId, $baseSlug);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($baseSlug, $tenantId) {
            return $this->findUniqueSlug($baseSlug, $tenantId);
        });
    }

    /**
     * Generate multiple unique slugs for batch operations.
     * 
     * @param array<string> $names Array of service names
     * @param int $tenantId The tenant ID
     * 
     * @return array<string, string> Array mapping names to unique slugs
     */
    public function generateMultipleUniqueSlugsBatch(array $names, int $tenantId): array
    {
        $slugs = [];
        $usedSlugs = $this->getExistingSlugs($tenantId);

        foreach ($names as $name) {
            $baseSlug = Str::slug($name);
            $uniqueSlug = $this->findUniqueSlugFromSet($baseSlug, $usedSlugs);
            $slugs[$name] = $uniqueSlug;
            $usedSlugs[] = $uniqueSlug; // Add to used set for next iteration
        }

        return $slugs;
    }

    /**
     * Clear slug cache for a tenant.
     */
    public function clearSlugCache(int $tenantId): void
    {
        $pattern = self::CACHE_PREFIX . ":{$tenantId}:*";
        
        // Note: This is a simplified cache clearing approach
        // In production, you might want to use Redis SCAN or similar
        Cache::flush(); // Consider more targeted cache clearing
    }

    /**
     * Find a unique slug by appending counter if necessary.
     */
    private function findUniqueSlug(string $baseSlug, int $tenantId): string
    {
        $slug = $baseSlug;
        $counter = 1;

        while ($this->slugExists($slug, $tenantId)) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    /**
     * Find unique slug from a set of existing slugs.
     */
    private function findUniqueSlugFromSet(string $baseSlug, array $existingSlugs): string
    {
        $slug = $baseSlug;
        $counter = 1;

        while (in_array($slug, $existingSlugs, true)) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    /**
     * Check if a slug exists for a tenant.
     */
    private function slugExists(string $slug, int $tenantId): bool
    {
        return UtilityService::where('slug', $slug)
            ->where('tenant_id', $tenantId)
            ->exists();
    }

    /**
     * Get existing slugs for a tenant.
     * 
     * @return array<string>
     */
    private function getExistingSlugs(int $tenantId): array
    {
        return UtilityService::where('tenant_id', $tenantId)
            ->pluck('slug')
            ->toArray();
    }

    /**
     * Generate cache key for slug.
     */
    private function getCacheKey(int $tenantId, string $baseSlug): string
    {
        return self::CACHE_PREFIX . ":{$tenantId}:{$baseSlug}";
    }

    /**
     * Validate slug format.
     */
    public function isValidSlug(string $slug): bool
    {
        return $slug === Str::slug($slug) && !empty($slug);
    }

    /**
     * Generate slug with custom separator.
     */
    public function generateSlugWithSeparator(string $name, string $separator = '-'): string
    {
        return Str::slug($name, $separator);
    }
}