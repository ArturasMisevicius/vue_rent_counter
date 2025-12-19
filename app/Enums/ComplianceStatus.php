<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Compliance Status Types
 * 
 * Defines the status categories for compliance checks
 * across various security frameworks.
 */
enum ComplianceStatus: string
{
    case COMPLIANT = 'compliant';
    case NON_COMPLIANT = 'non_compliant';
    case PARTIAL = 'partial';
    case PENDING = 'pending';

    /**
     * Get human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::COMPLIANT => __('compliance.status.compliant'),
            self::NON_COMPLIANT => __('compliance.status.non_compliant'),
            self::PARTIAL => __('compliance.status.partial'),
            self::PENDING => __('compliance.status.pending'),
        };
    }

    /**
     * Get color class for UI display.
     */
    public function colorClass(): string
    {
        return match ($this) {
            self::COMPLIANT => 'text-green-600 bg-green-50',
            self::NON_COMPLIANT => 'text-red-600 bg-red-50',
            self::PARTIAL => 'text-yellow-600 bg-yellow-50',
            self::PENDING => 'text-gray-600 bg-gray-50',
        };
    }

    /**
     * Get icon for UI display.
     */
    public function icon(): string
    {
        return match ($this) {
            self::COMPLIANT => 'heroicon-o-check-circle',
            self::NON_COMPLIANT => 'heroicon-o-x-circle',
            self::PARTIAL => 'heroicon-o-exclamation-triangle',
            self::PENDING => 'heroicon-o-clock',
        };
    }

    /**
     * Check if status indicates compliance issues.
     */
    public function hasIssues(): bool
    {
        return $this === self::NON_COMPLIANT || $this === self::PARTIAL;
    }

    /**
     * Check if status requires attention.
     */
    public function requiresAttention(): bool
    {
        return $this !== self::COMPLIANT;
    }
}