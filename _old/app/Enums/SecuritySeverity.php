<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Security Severity Levels
 * 
 * Defines the severity classification for security violations
 * and incidents in the multi-tenant utility billing platform.
 */
enum SecuritySeverity: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case CRITICAL = 'critical';

    /**
     * Get human-readable label for the severity level.
     */
    public function label(): string
    {
        return match ($this) {
            self::LOW => __('security.severity.low'),
            self::MEDIUM => __('security.severity.medium'),
            self::HIGH => __('security.severity.high'),
            self::CRITICAL => __('security.severity.critical'),
        };
    }

    /**
     * Get color class for UI display.
     */
    public function colorClass(): string
    {
        return match ($this) {
            self::LOW => 'text-blue-600 bg-blue-50',
            self::MEDIUM => 'text-yellow-600 bg-yellow-50',
            self::HIGH => 'text-orange-600 bg-orange-50',
            self::CRITICAL => 'text-red-600 bg-red-50',
        };
    }

    /**
     * Get numeric priority for sorting and comparison.
     */
    public function priority(): int
    {
        return match ($this) {
            self::LOW => 1,
            self::MEDIUM => 2,
            self::HIGH => 3,
            self::CRITICAL => 4,
        };
    }

    /**
     * Check if severity requires immediate attention.
     */
    public function requiresImmediateAttention(): bool
    {
        return $this === self::HIGH || $this === self::CRITICAL;
    }

    /**
     * Get all severity levels ordered by priority.
     */
    public static function orderedByPriority(): array
    {
        return [
            self::CRITICAL,
            self::HIGH,
            self::MEDIUM,
            self::LOW,
        ];
    }
}