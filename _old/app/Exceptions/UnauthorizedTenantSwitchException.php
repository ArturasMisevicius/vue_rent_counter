<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Throwable;

/**
 * Exception thrown when a user attempts to switch to a tenant they don't have access to.
 */
final class UnauthorizedTenantSwitchException extends Exception
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $userId,
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        if (empty($message)) {
            $message = "User {$userId} is not authorized to switch to tenant {$tenantId}";
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * Create exception for superadmin-only operation.
     */
    public static function superadminRequired(int $tenantId, int $userId): self
    {
        return new self(
            $tenantId,
            $userId,
            "Only superadmins can switch to tenant {$tenantId}. User {$userId} lacks required permissions."
        );
    }

    /**
     * Create exception for invalid tenant.
     */
    public static function invalidTenant(int $tenantId, int $userId): self
    {
        return new self(
            $tenantId,
            $userId,
            "Tenant {$tenantId} does not exist or is not accessible to user {$userId}."
        );
    }

    /**
     * Create exception for tenant access denied.
     */
    public static function accessDenied(int $tenantId, int $userId, string $reason = ''): self
    {
        $message = "Access denied to tenant {$tenantId} for user {$userId}";
        if (!empty($reason)) {
            $message .= ": {$reason}";
        }

        return new self($tenantId, $userId, $message);
    }
}