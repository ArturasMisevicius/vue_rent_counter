<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Security Violation Detected Event
 * 
 * Fired when a security violation is detected during input sanitization.
 * Allows for centralized security monitoring and alerting.
 * 
 * @package App\Events
 */
final class SecurityViolationDetected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     * 
     * @param string $violationType Type of violation (e.g., 'path_traversal', 'xss_attempt')
     * @param string $originalInput The original malicious input
     * @param string $sanitizedAttempt The sanitized version (if applicable)
     * @param string|null $ipAddress IP address of the request
     * @param int|null $userId User ID if authenticated
     * @param array<string, mixed> $context Additional context
     */
    public function __construct(
        public readonly string $violationType,
        public readonly string $originalInput,
        public readonly string $sanitizedAttempt,
        public readonly ?string $ipAddress = null,
        public readonly ?int $userId = null,
        public readonly array $context = [],
    ) {}
}
