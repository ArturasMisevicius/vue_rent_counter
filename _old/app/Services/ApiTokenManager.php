<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserRole;
use App\Models\PersonalAccessToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * API Token Manager Service
 * 
 * Centralized service for managing API tokens without requiring
 * the HasApiTokens trait on the User model.
 */
class ApiTokenManager
{
    private const CACHE_TTL = 900; // 15 minutes
    private const CACHE_PREFIX = 'api_tokens:';

    /**
     * Create an API token for a user.
     */
    public function createToken(
        User $user,
        string $name,
        ?array $abilities = null,
        ?\DateTimeInterface $expiresAt = null
    ): string {
        $defaultAbilities = $this->getDefaultAbilitiesForRole($user->role);
        $tokenAbilities = $abilities ?? $defaultAbilities;

        // Set default expiration if not provided
        $expiresAt = $expiresAt ?? now()->addMinutes(config('sanctum.expiration', 525600));

        $result = PersonalAccessToken::createToken(
            $user,
            $name,
            $tokenAbilities,
            $expiresAt
        );

        // Clear cache
        $this->clearUserTokenCache($user);

        // Log token creation
        Log::info('API token created', [
            'user_id' => $user->id,
            'token_name' => $name,
            'abilities' => $tokenAbilities,
            'expires_at' => $expiresAt?->toISOString(),
        ]);

        return $result['plainTextToken'];
    }

    /**
     * Get all tokens for a user.
     */
    public function getUserTokens(User $user): Collection
    {
        $cacheKey = $this->getCacheKey('user_tokens', $user->id);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user) {
            return PersonalAccessToken::where('tokenable_type', User::class)
                ->where('tokenable_id', $user->id)
                ->active()
                ->orderBy('created_at', 'desc')
                ->get();
        });
    }

    /**
     * Get active token count for a user.
     */
    public function getActiveTokenCount(User $user): int
    {
        $cacheKey = $this->getCacheKey('token_count', $user->id);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user) {
            return PersonalAccessToken::where('tokenable_type', User::class)
                ->where('tokenable_id', $user->id)
                ->active()
                ->count();
        });
    }

    /**
     * Revoke all tokens for a user.
     */
    public function revokeAllTokens(User $user): int
    {
        $count = PersonalAccessToken::where('tokenable_type', User::class)
            ->where('tokenable_id', $user->id)
            ->delete();

        $this->clearUserTokenCache($user);

        Log::info('All API tokens revoked', [
            'user_id' => $user->id,
            'tokens_revoked' => $count,
        ]);

        return $count;
    }

    /**
     * Revoke a specific token.
     */
    public function revokeToken(User $user, int $tokenId): bool
    {
        $deleted = PersonalAccessToken::where('tokenable_type', User::class)
            ->where('tokenable_id', $user->id)
            ->where('id', $tokenId)
            ->delete();

        if ($deleted) {
            $this->clearUserTokenCache($user);
            
            Log::info('API token revoked', [
                'user_id' => $user->id,
                'token_id' => $tokenId,
            ]);
        }

        return $deleted > 0;
    }

    /**
     * Find a token by its plain text value.
     */
    public function findToken(string $token): ?PersonalAccessToken
    {
        return PersonalAccessToken::findToken($token);
    }

    /**
     * Check if a user's current token has a specific ability.
     */
    public function hasAbility(User $user, string $ability): bool
    {
        $token = $this->getCurrentAccessToken($user);
        
        return $token ? $token->can($ability) : false;
    }

    /**
     * Get the current access token for a user.
     */
    public function getCurrentAccessToken(User $user): ?PersonalAccessToken
    {
        // This would typically be set by middleware during request processing
        return $user->currentAccessToken ?? null;
    }

    /**
     * Prune expired tokens.
     */
    public function pruneExpiredTokens(int $hours = 24): int
    {
        $count = PersonalAccessToken::expired()
            ->where('created_at', '<', now()->subHours($hours))
            ->delete();

        Log::info('Expired API tokens pruned', [
            'tokens_pruned' => $count,
            'hours_threshold' => $hours,
        ]);

        return $count;
    }

    /**
     * Get token usage statistics.
     */
    public function getTokenStatistics(): array
    {
        $cacheKey = $this->getCacheKey('statistics');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            return [
                'total_tokens' => PersonalAccessToken::count(),
                'active_tokens' => PersonalAccessToken::active()->count(),
                'expired_tokens' => PersonalAccessToken::expired()->count(),
                'recently_used' => PersonalAccessToken::recentlyUsed(7)->count(),
                'tokens_by_user_role' => $this->getTokensByUserRole(),
            ];
        });
    }

    /**
     * Get default abilities for a user role.
     */
    private function getDefaultAbilitiesForRole(UserRole $role): array
    {
        return match ($role) {
            UserRole::SUPERADMIN => ['*'],
            UserRole::ADMIN, UserRole::MANAGER => [
                'meter-reading:read',
                'meter-reading:write',
                'meter-reading:validate',
                'property:read',
                'property:write',
                'invoice:read',
                'invoice:write',
                'validation:read',
                'validation:write',
                'tenant:read',
                'tenant:write',
                'building:read',
                'building:write',
            ],
            UserRole::TENANT => [
                'meter-reading:read',
                'meter-reading:write',
                'validation:read',
                'property:read',
                'invoice:read',
            ],
            default => [],
        };
    }

    /**
     * Get tokens grouped by user role.
     */
    private function getTokensByUserRole(): array
    {
        return PersonalAccessToken::join('users', function ($join) {
            $join->on('personal_access_tokens.tokenable_id', '=', 'users.id')
                 ->where('personal_access_tokens.tokenable_type', '=', User::class);
        })
        ->selectRaw('users.role, COUNT(*) as token_count')
        ->groupBy('users.role')
        ->pluck('token_count', 'role')
        ->toArray();
    }

    /**
     * Clear token cache for a user.
     */
    private function clearUserTokenCache(User $user): void
    {
        $patterns = [
            $this->getCacheKey('user_tokens', $user->id),
            $this->getCacheKey('token_count', $user->id),
            $this->getCacheKey('statistics'),
        ];

        foreach ($patterns as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Generate cache key.
     */
    private function getCacheKey(string $type, mixed ...$params): string
    {
        return self::CACHE_PREFIX . $type . ':' . implode(':', $params);
    }
}