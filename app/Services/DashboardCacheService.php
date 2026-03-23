<?php

namespace App\Services;

use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Cache;

class DashboardCacheService
{
    public const SUPERADMIN_STATS_TTL_SECONDS = 60;

    public const ADMIN_STATS_TTL_SECONDS = 30;

    public const TENANT_STATS_TTL_SECONDS = 120;

    private const CACHE_PREFIX = 'dashboard';

    private const ORGANIZATION_VERSION_PREFIX = 'dashboard:organization-version';

    private const ORGANIZATION_VERSION_TTL_DAYS = 30;

    /**
     * @var array<string, mixed>
     */
    private array $memoized = [];

    public function remember(User $user, string $segment, Closure $callback, array $context = []): mixed
    {
        $key = $this->keyFor($user, $segment, $context);

        if (array_key_exists($key, $this->memoized)) {
            return $this->memoized[$key];
        }

        return $this->memoized[$key] = Cache::remember(
            $key,
            now()->addSeconds($this->ttlFor($user)),
            $callback,
        );
    }

    public function ttlFor(User $user): int
    {
        return match (true) {
            $user->isSuperadmin() => self::SUPERADMIN_STATS_TTL_SECONDS,
            $user->isAdmin(), $user->isManager() => self::ADMIN_STATS_TTL_SECONDS,
            $user->isTenant() => self::TENANT_STATS_TTL_SECONDS,
            default => self::ADMIN_STATS_TTL_SECONDS,
        };
    }

    /**
     * @param  array<int, string|int>  $context
     */
    public function keyFor(User $user, string $segment, array $context = []): string
    {
        $parts = [
            self::CACHE_PREFIX,
            $segment,
            'role-'.$user->role->value,
            'user-'.$user->id,
            'locale-'.$user->locale,
        ];

        if ($user->organization_id !== null) {
            $parts[] = 'org-version-'.$this->organizationVersion($user->organization_id);
        }

        foreach ($context as $value) {
            $parts[] = (string) $value;
        }

        return implode(':', $parts);
    }

    public function touchOrganization(?int $organizationId): void
    {
        if ($organizationId === null) {
            return;
        }

        $key = $this->organizationVersionKey($organizationId);

        if (! $this->addOrganizationVersion($key)) {
            $nextVersion = Cache::increment($key);

            if (! is_int($nextVersion)) {
                $nextVersion = 1;
            }

            $this->refreshOrganizationVersionTtl($key, $nextVersion);
        }

        $this->memoized = [];
    }

    private function organizationVersion(int $organizationId): int
    {
        $key = $this->organizationVersionKey($organizationId);

        if ($this->addOrganizationVersion($key)) {
            return 1;
        }

        $version = (int) Cache::get($key, 1);
        $this->refreshOrganizationVersionTtl($key, $version);

        return $version;
    }

    private function organizationVersionKey(int $organizationId): string
    {
        return self::ORGANIZATION_VERSION_PREFIX.':'.$organizationId;
    }

    private function addOrganizationVersion(string $key): bool
    {
        return Cache::add(
            $key,
            1,
            now()->addDays(self::ORGANIZATION_VERSION_TTL_DAYS),
        );
    }

    private function refreshOrganizationVersionTtl(string $key, int $version): void
    {
        $ttl = now()->addDays(self::ORGANIZATION_VERSION_TTL_DAYS);

        if (Cache::touch($key, $ttl)) {
            return;
        }

        Cache::put($key, $version, $ttl);
    }
}
