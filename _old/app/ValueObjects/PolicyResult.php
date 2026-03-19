<?php

declare(strict_types=1);

namespace App\ValueObjects;

/**
 * Value object representing a policy authorization result.
 *
 * This provides structured return values for policy methods,
 * including the authorization decision and optional context.
 */
final readonly class PolicyResult
{
    public function __construct(
        public bool $authorized,
        public ?string $reason = null,
        public array $context = []
    ) {}

    /**
     * Create an authorized result.
     *
     * @param  string|null  $reason  Optional reason for authorization
     * @param  array  $context  Additional context data
     */
    public static function allow(?string $reason = null, array $context = []): self
    {
        return new self(
            authorized: true,
            reason: $reason,
            context: $context
        );
    }

    /**
     * Create a denied result.
     *
     * @param  string|null  $reason  Optional reason for denial
     * @param  array  $context  Additional context data
     */
    public static function deny(?string $reason = null, array $context = []): self
    {
        return new self(
            authorized: false,
            reason: $reason,
            context: $context
        );
    }

    /**
     * Check if the result is authorized.
     */
    public function isAuthorized(): bool
    {
        return $this->authorized;
    }

    /**
     * Check if the result is denied.
     */
    public function isDenied(): bool
    {
        return ! $this->authorized;
    }

    /**
     * Get the result as a boolean (for backward compatibility).
     */
    public function toBool(): bool
    {
        return $this->authorized;
    }

    /**
     * Get context data for logging.
     */
    public function toLogContext(): array
    {
        return [
            'authorized' => $this->authorized,
            'reason' => $this->reason,
            'context' => $this->context,
        ];
    }
}
