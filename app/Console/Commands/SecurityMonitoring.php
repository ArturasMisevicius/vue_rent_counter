<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\PersonalAccessToken;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Security Monitoring Command
 * 
 * Monitors security metrics and sends alerts for suspicious activity.
 * Should be scheduled to run every 5-15 minutes via Laravel Scheduler.
 */
class SecurityMonitoring extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'security:monitor 
                           {--alert-email= : Override default alert email}
                           {--dry-run : Show alerts without sending notifications}';

    /**
     * The console command description.
     */
    protected $description = 'Monitor security metrics and send alerts for suspicious activity';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $alerts = [];
        $dryRun = $this->option('dry-run');
        $alertEmail = $this->option('alert-email') ?: config('security.monitoring.alert_channels.email');

        $this->info('Running security monitoring checks...');

        // Check 1: Suspicious token creation activity
        $alerts = array_merge($alerts, $this->checkSuspiciousTokenActivity());

        // Check 2: Failed authentication attempts
        $alerts = array_merge($alerts, $this->checkFailedAuthenticationAttempts());

        // Check 3: Unverified users with active tokens
        $alerts = array_merge($alerts, $this->checkUnverifiedUsersWithTokens());

        // Check 4: Superadmin activity monitoring
        $alerts = array_merge($alerts, $this->checkSuperadminActivity());

        // Check 5: Expired tokens accumulation
        $alerts = array_merge($alerts, $this->checkExpiredTokenAccumulation());

        // Check 6: Unusual login patterns
        $alerts = array_merge($alerts, $this->checkUnusualLoginPatterns());

        // Process alerts
        if (empty($alerts)) {
            $this->info('✅ No security alerts detected');
            return 0;
        }

        $this->warn('🚨 Security alerts detected: ' . count($alerts));
        
        foreach ($alerts as $alert) {
            $this->line("  - {$alert}");
        }

        if ($dryRun) {
            $this->info('Dry run mode - no notifications sent');
            return 0;
        }

        // Log alerts
        Log::warning('Security alerts detected', [
            'alerts' => $alerts,
            'alert_count' => count($alerts),
            'timestamp' => now()->toISOString(),
        ]);

        // Send notifications
        $this->sendAlertNotifications($alerts, $alertEmail);

        return 0;
    }

    /**
     * Check for suspicious token creation activity.
     */
    private function checkSuspiciousTokenActivity(): array
    {
        $alerts = [];

        // High token creation rate
        $recentTokens = PersonalAccessToken::where('created_at', '>', now()->subHour())->count();
        if ($recentTokens > 20) {
            $alerts[] = "High token creation rate: {$recentTokens} tokens created in last hour";
        }

        // Superadmin token creation
        $superadminTokens = PersonalAccessToken::where('created_at', '>', now()->subHour())
            ->whereHasMorph('tokenable', [User::class], function ($query) {
                $query->where('role', 'superadmin');
            })
            ->count();

        if ($superadminTokens > 3) {
            $alerts[] = "High superadmin token creation: {$superadminTokens} tokens in last hour";
        }

        // Tokens created for suspended users
        $suspendedUserTokens = PersonalAccessToken::where('created_at', '>', now()->subHour())
            ->whereHasMorph('tokenable', [User::class], function ($query) {
                $query->whereNotNull('suspended_at');
            })
            ->count();

        if ($suspendedUserTokens > 0) {
            $alerts[] = "Tokens created for suspended users: {$suspendedUserTokens}";
        }

        return $alerts;
    }

    /**
     * Check for failed authentication attempts.
     */
    private function checkFailedAuthenticationAttempts(): array
    {
        $alerts = [];

        // Get failed login count from cache (set by middleware)
        $failedLogins = Cache::get('security:failed_logins_last_hour', 0);
        if ($failedLogins > 50) {
            $alerts[] = "High failed login rate: {$failedLogins} failures in last hour";
        }

        // Check for token validation failures
        $tokenValidationFailures = Cache::get('security:token_validation_failures_last_hour', 0);
        if ($tokenValidationFailures > 100) {
            $alerts[] = "High token validation failure rate: {$tokenValidationFailures} failures in last hour";
        }

        return $alerts;
    }

    /**
     * Check for unverified users with active tokens.
     */
    private function checkUnverifiedUsersWithTokens(): array
    {
        $alerts = [];

        $unverifiedWithTokens = User::whereNull('email_verified_at')
            ->whereHas('tokens', function ($query) {
                $query->where(function ($q) {
                    $q->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
                });
            })
            ->count();

        if ($unverifiedWithTokens > 0) {
            $alerts[] = "Unverified users with active tokens: {$unverifiedWithTokens}";
        }

        return $alerts;
    }

    /**
     * Check superadmin activity.
     */
    private function checkSuperadminActivity(): array
    {
        $alerts = [];

        // New superadmin accounts
        $newSuperadmins = User::where('role', 'superadmin')
            ->where('created_at', '>', now()->subDay())
            ->count();

        if ($newSuperadmins > 0) {
            $alerts[] = "New superadmin accounts created: {$newSuperadmins} in last 24 hours";
        }

        // Superadmin promotions (check logs)
        $promotionLogs = Cache::get('security:superadmin_promotions_last_day', 0);
        if ($promotionLogs > 1) {
            $alerts[] = "Multiple superadmin promotions: {$promotionLogs} in last 24 hours";
        }

        return $alerts;
    }

    /**
     * Check for expired token accumulation.
     */
    private function checkExpiredTokenAccumulation(): array
    {
        $alerts = [];

        $expiredTokens = PersonalAccessToken::expired()->count();
        $totalTokens = PersonalAccessToken::count();

        if ($totalTokens > 0) {
            $expiredPercentage = ($expiredTokens / $totalTokens) * 100;
            
            if ($expiredPercentage > 30) {
                $alerts[] = "High expired token ratio: {$expiredPercentage}% ({$expiredTokens}/{$totalTokens})";
            }
        }

        return $alerts;
    }

    /**
     * Check for unusual login patterns.
     */
    private function checkUnusualLoginPatterns(): array
    {
        $alerts = [];

        // Users with no recent login but active tokens
        $staleActiveUsers = User::whereHas('tokens', function ($query) {
                $query->where(function ($q) {
                    $q->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
                });
            })
            ->where(function ($query) {
                $query->where('last_login_at', '<', now()->subDays(30))
                      ->orWhereNull('last_login_at');
            })
            ->count();

        if ($staleActiveUsers > 10) {
            $alerts[] = "Users with active tokens but no recent login: {$staleActiveUsers}";
        }

        return $alerts;
    }

    /**
     * Send alert notifications.
     */
    private function sendAlertNotifications(array $alerts, ?string $alertEmail): void
    {
        if (!$alertEmail) {
            $this->warn('No alert email configured - skipping email notification');
            return;
        }

        try {
            $subject = 'Security Alert - ' . config('app.name');
            $body = "Security alerts detected at " . now()->format('Y-m-d H:i:s') . ":\n\n";
            $body .= implode("\n", array_map(fn($alert) => "• {$alert}", $alerts));
            $body .= "\n\nPlease investigate these issues immediately.";

            Mail::raw($body, function ($message) use ($alertEmail, $subject) {
                $message->to($alertEmail)
                       ->subject($subject);
            });

            $this->info("✅ Alert email sent to {$alertEmail}");

        } catch (\Exception $e) {
            $this->error("❌ Failed to send alert email: " . $e->getMessage());
            Log::error('Failed to send security alert email', [
                'error' => $e->getMessage(),
                'alerts' => $alerts,
            ]);
        }
    }
}