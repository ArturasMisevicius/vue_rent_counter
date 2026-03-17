<?php

namespace App\Filament\Support\Dashboard;

use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Cache;

class DashboardCacheService
{
    public const SUPERADMIN_STATS_TTL_SECONDS = 60;

    public const ADMIN_STATS_TTL_SECONDS = 30;

    public const TENANT_STATS_TTL_SECONDS = 120;

    private const CACHE_PREFIX = 'dashboard';

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

        foreach ($context as $value) {
            $parts[] = (string) $value;
        }

        return implode(':', $parts);
    }
}
