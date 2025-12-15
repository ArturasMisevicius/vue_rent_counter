<?php

declare(strict_types=1);

namespace App\ValueObjects;

use Symfony\Component\HttpFoundation\Response;

/**
 * Value object representing the result of a subscription check.
 * 
 * This immutable object encapsulates the outcome of subscription validation,
 * including whether access should be granted and any associated message.
 * 
 * Security: Validates redirect routes against whitelist to prevent open redirect attacks
 * 
 * @package App\ValueObjects
 */
final readonly class SubscriptionCheckResult
{
    /**
     * Allowed redirect routes (whitelist for security)
     */
    private const ALLOWED_REDIRECT_ROUTES = [
        'admin.dashboard',
        'manager.dashboard',
        'tenant.dashboard',
        'superadmin.dashboard',
    ];

    public function __construct(
        public bool $shouldProceed,
        public ?string $message = null,
        public ?string $messageType = null,
        public ?string $redirectRoute = null,
    ) {
        // Validate redirect route if provided
        if ($redirectRoute !== null && !$this->isValidRedirectRoute($redirectRoute)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid redirect route: %s. Allowed routes: %s',
                    $redirectRoute,
                    implode(', ', self::ALLOWED_REDIRECT_ROUTES)
                )
            );
        }
    }
    
    /**
     * Check if redirect route is in whitelist.
     *
     * @param  string  $route
     * @return bool
     */
    private function isValidRedirectRoute(string $route): bool
    {
        return in_array($route, self::ALLOWED_REDIRECT_ROUTES, true);
    }

    /**
     * Create a result that allows the request to proceed.
     */
    public static function allow(): self
    {
        return new self(shouldProceed: true);
    }

    /**
     * Create a result that allows the request with a warning message.
     */
    public static function allowWithWarning(string $message): self
    {
        return new self(
            shouldProceed: true,
            message: $message,
            messageType: 'warning'
        );
    }

    /**
     * Create a result that allows the request with an error message.
     */
    public static function allowWithError(string $message): self
    {
        return new self(
            shouldProceed: true,
            message: $message,
            messageType: 'error'
        );
    }

    /**
     * Create a result that blocks the request with an error message.
     */
    public static function block(string $message, string $redirectRoute): self
    {
        return new self(
            shouldProceed: false,
            message: $message,
            messageType: 'error',
            redirectRoute: $redirectRoute
        );
    }
}
