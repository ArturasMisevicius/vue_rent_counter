<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PersonalAccessToken;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * API Token Monitoring Service
 * 
 * Provides monitoring and alerting for API token usage patterns.
 */
class ApiTokenMonitoringService
{
    private const CACHE_TTL = 3600; // 1 hour
    private const SUSPICIOUS_THRESHOLD = 10; // tokens per hour

    /**
     * Monitor token creation rate for suspicious activity.
     */
    public function monitorTokenCreation(User $user): void
    {
        $cacheKey = "token_creation_rate:{$user->id}:" . now()->format('Y-m-d-H');
        $count = Cache::increment($cacheKey, 1);
        
        if ($count === 1) {
            Cache::put($cacheKey, 1, self::CACHE_TTL);
        }

        if ($count > self::SUSPICIOUS_THRESHOLD) {
            $this->alertSuspiciousActivity($user, 'high_token_creation_rate', [
                'tokens_per_hour' => $count,
                'threshold' => self::SUSPICIOUS_THRESHOLD,
            ]);
        }
    }

    /**
     * Monitor token usage patterns.
     */
    public function monitorTokenUsage(PersonalAccessToken $token, string $ipAddress, string $userAgent): void
    {
        $cacheKey = "token_usage:{$token->id}";
        $usage = Cache::get($cacheKey, []);

        $usage[] = [
            'ip' => $ipAddress,
            'user_agent' => $userAgent,
            'timestamp' => now()->toISOString(),
        ];

        // Keep only last 10 usages
        $usage = array_slice($usage, -10);
        Cache::put($cacheKey, $usage, self::CACHE_TTL);

        // Check for suspicious patterns
        $this->detectSuspiciousUsage($token, $usage);
    }

    /**
     * Get token usage analytics.
     */
    public function getTokenAnalytics(): array
    {
        return [
            'total_tokens' => PersonalAccessToken::count(),
            'active_tokens' => PersonalAccessToken::active()->count(),
            'expired_tokens' => PersonalAccessToken::expired()->count(),
            'tokens_created_today' => PersonalAccessToken::whereDate('created_at', today())->count(),
            'tokens_used_today' => PersonalAccessToken::whereDate('last_used_at', today())->count(),
            'top_users_by_tokens' => $this->getTopUsersByTokenCount(),
            'token_usage_by_hour' => $this->getTokenUsageByHour(),
        ];
    }

    /**
     * Check system health related to tokens.
     */
    public function checkSystemHealth(): array
    {
        $totalTokens = PersonalAccessToken::count();
        $expiredTokens = PersonalAccessToken::expired()->count();
        $unusedTokens = PersonalAccessToken::whereNull('last_used_at')
            ->where('created_at', '<', now()->subDays(30))
            ->count();

        $health = [
            'status' => 'healthy',
            'total_tokens' => $totalTokens,
            'expired_tokens' => $expiredTokens,
            'unused_tokens' => $unusedTokens,
            'issues' => [],
        ];

        // Check for issues
        if ($expiredTokens > ($totalTokens * 0.3)) {
            $health['issues'][] = 'High number of expired tokens';
            $health['status'] = 'warning';
        }

        if ($unusedTokens > ($totalTokens * 0.5)) {
            $health['issues'][] = 'High number of unused tokens';
            $health['status'] = 'warning';
        }

        if ($totalTokens > 10000) {
            $health['issues'][] = 'Very high token count - consider cleanup';
            $health['status'] = 'warning';
        }

        return $health;
    }

    /**
     * Detect suspicious token usage patterns.
     */
    private function detectSuspiciousUsage(PersonalAccessToken $token, array $usage): void
    {
        if (count($usage) < 3) {
            return;
        }

        // Check for multiple IP addresses
        $ips = array_unique(array_column($usage, 'ip'));
        if (count($ips) > 3) {
            $this->alertSuspiciousActivity($token->tokenable, 'multiple_ip_usage', [
                'token_id' => $token->id,
                'ip_addresses' => $ips,
            ]);
        }

        // Check for rapid usage from different locations
        $recentUsage = array_filter($usage, function ($use) {
            return now()->subMinutes(10)->lessThan($use['timestamp']);
        });

        if (count($recentUsage) > 5) {
            $this->alertSuspiciousActivity($token->tokenable, 'rapid_token_usage', [
                'token_id' => $token->id,
                'usage_count' => count($recentUsage),
            ]);
        }
    }

    /**
     * Alert about suspicious activity.
     */
    private function alertSuspiciousActivity(User $user, string $type, array $data): void
    {
        Log::warning('Suspicious API token activity detected', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'activity_type' => $type,
            'data' => $data,
            'timestamp' => now()->toISOString(),
        ]);

        // Could also send notifications, trigger webhooks, etc.
    }

    /**
     * Get top users by token count.
     */
    private function getTopUsersByTokenCount(int $limit = 10): array
    {
        return PersonalAccessToken::join('users', function ($join) {
            $join->on('personal_access_tokens.tokenable_id', '=', 'users.id')
                 ->where('personal_access_tokens.tokenable_type', '=', User::class);
        })
        ->selectRaw('users.id, users.name, users.email, COUNT(*) as token_count')
        ->groupBy('users.id', 'users.name', 'users.email')
        ->orderBy('token_count', 'desc')
        ->limit($limit)
        ->get()
        ->toArray();
    }

    /**
     * Get token usage by hour for the last 24 hours.
     */
    private function getTokenUsageByHour(): array
    {
        $hours = [];
        for ($i = 23; $i >= 0; $i--) {
            $hour = now()->subHours($i);
            $hours[$hour->format('H:00')] = PersonalAccessToken::whereBetween('last_used_at', [
                $hour->startOfHour(),
                $hour->endOfHour(),
            ])->count();
        }

        return $hours;
    }
}