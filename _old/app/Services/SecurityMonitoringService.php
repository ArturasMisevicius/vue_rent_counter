<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Security Monitoring Service
 * 
 * Monitors security events and triggers alerts
 */
final readonly class SecurityMonitoringService
{
    private const ALERT_CACHE_TTL = 300; // 5 minutes

    public function __construct(
        private array $alertChannels = ['slack', 'email'],
        private array $thresholds = [
            'failed_logins' => 10,
            'policy_registration_failures' => 5,
            'suspicious_requests' => 20,
        ],
    ) {}

    /**
     * Record security violation
     */
    public function recordViolation(string $type, array $context = []): void
    {
        $key = "security_violations:{$type}";
        $count = Cache::increment($key, 1);
        
        // Set expiry on first increment
        if ($count === 1) {
            Cache::put($key, 1, now()->addMinutes(60));
        }
        
        Log::channel('security')->warning("Security violation: {$type}", [
            'type' => $type,
            'count' => $count,
            'context' => $this->sanitizeContext($context),
            'timestamp' => now()->toISOString(),
        ]);
        
        // Check if we need to alert
        if ($this->shouldAlert($type, $count)) {
            $this->sendAlert($type, $count, $context);
        }
    }

    /**
     * Record policy registration event
     */
    public function recordPolicyRegistration(array $policyResults, array $gateResults): void
    {
        $totalErrors = count($policyResults['errors']) + count($gateResults['errors']);
        
        if ($totalErrors > 0) {
            $this->recordViolation('policy_registration_failure', [
                'policy_errors' => count($policyResults['errors']),
                'gate_errors' => count($gateResults['errors']),
                'total_errors' => $totalErrors,
            ]);
        }
        
        // Log successful registrations (without sensitive data)
        Log::channel('security')->info('Policy registration completed', [
            'policies_registered' => $policyResults['registered'],
            'gates_registered' => $gateResults['registered'],
            'total_skipped' => $policyResults['skipped'] + $gateResults['skipped'],
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Check if we should send an alert
     */
    private function shouldAlert(string $type, int $count): bool
    {
        $threshold = $this->thresholds[$type] ?? 10;
        
        // Alert on threshold and then every 10 occurrences
        return $count === $threshold || ($count > $threshold && $count % 10 === 0);
    }

    /**
     * Send security alert
     */
    private function sendAlert(string $type, int $count, array $context): void
    {
        $alertKey = "security_alert:{$type}";
        
        // Prevent alert spam
        if (Cache::has($alertKey)) {
            return;
        }
        
        Cache::put($alertKey, true, self::ALERT_CACHE_TTL);
        
        $message = "Security Alert: {$type} threshold exceeded ({$count} occurrences)";
        
        Log::channel('security')->critical($message, [
            'type' => $type,
            'count' => $count,
            'context' => $this->sanitizeContext($context),
            'timestamp' => now()->toISOString(),
        ]);
        
        // Send notifications to configured channels
        foreach ($this->alertChannels as $channel) {
            try {
                Notification::route($channel, config("logging.channels.{$channel}.webhook"))
                    ->notify(new \App\Notifications\SecurityAlertNotification($type, $count));
            } catch (\Throwable $e) {
                Log::error('Failed to send security alert', [
                    'channel' => $channel,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Sanitize context data for logging
     */
    private function sanitizeContext(array $context): array
    {
        $sanitized = [];
        
        foreach ($context as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeContext($value);
            } elseif ($this->isSensitiveKey($key)) {
                $sanitized[$key] = '[REDACTED]';
            } elseif ($this->isPiiKey($key)) {
                $sanitized[$key] = 'hash:' . substr(hash('sha256', (string) $value), 0, 8);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }

    /**
     * Check if key contains sensitive information
     */
    private function isSensitiveKey(string $key): bool
    {
        $sensitiveKeys = ['password', 'token', 'secret', 'key', 'authorization'];
        
        foreach ($sensitiveKeys as $sensitiveKey) {
            if (str_contains(strtolower($key), $sensitiveKey)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if key contains PII
     */
    private function isPiiKey(string $key): bool
    {
        $piiKeys = ['email', 'phone', 'address', 'ip', 'user_agent'];
        
        foreach ($piiKeys as $piiKey) {
            if (str_contains(strtolower($key), $piiKey)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get security metrics
     */
    public function getSecurityMetrics(): array
    {
        $metrics = [];
        
        foreach (array_keys($this->thresholds) as $type) {
            $key = "security_violations:{$type}";
            $metrics[$type] = Cache::get($key, 0);
        }
        
        return [
            'violations' => $metrics,
            'timestamp' => now()->toISOString(),
        ];
    }
}