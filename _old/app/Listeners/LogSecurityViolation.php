<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\SecurityViolationDetected;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Log Security Violation Listener
 * 
 * Handles security violation events by logging them for monitoring
 * and potentially triggering alerts for repeated violations.
 * 
 * @package App\Listeners
 */
final class LogSecurityViolation implements ShouldQueue
{
    /**
     * Handle the event.
     * 
     * @param SecurityViolationDetected $event
     * @return void
     */
    public function handle(SecurityViolationDetected $event): void
    {
        Log::channel('security')->warning('Security violation detected', [
            'type' => $event->violationType,
            'original_input' => $event->originalInput,
            'sanitized_attempt' => $event->sanitizedAttempt,
            'ip_address' => $event->ipAddress,
            'user_id' => $event->userId,
            'context' => $event->context,
            'timestamp' => now()->toIso8601String(),
        ]);

        // Check for repeated violations from same IP
        $this->checkForRepeatedViolations($event);
    }

    /**
     * Check for repeated violations and trigger alerts if needed.
     * 
     * @param SecurityViolationDetected $event
     * @return void
     */
    private function checkForRepeatedViolations(SecurityViolationDetected $event): void
    {
        if (!$event->ipAddress) {
            return;
        }

        $cacheKey = "security:violations:{$event->ipAddress}";
        $violations = cache()->get($cacheKey, 0);
        $violations++;

        cache()->put($cacheKey, $violations, now()->addHour());

        // Alert if more than 5 violations in an hour
        if ($violations > 5) {
            Log::channel('security')->critical('Multiple security violations detected', [
                'ip_address' => $event->ipAddress,
                'violation_count' => $violations,
                'latest_type' => $event->violationType,
            ]);

            // TODO: Trigger additional alerting (email, Slack, etc.)
        }
    }
}
