<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Threat Classification Types
 * 
 * Defines the classification categories for security threats
 * detected in the security monitoring system.
 */
enum ThreatClassification: string
{
    case FALSE_POSITIVE = 'false_positive';
    case SUSPICIOUS = 'suspicious';
    case MALICIOUS = 'malicious';
    case UNKNOWN = 'unknown';

    /**
     * Get human-readable label for the classification.
     */
    public function label(): string
    {
        return match ($this) {
            self::FALSE_POSITIVE => __('security.threat.false_positive'),
            self::SUSPICIOUS => __('security.threat.suspicious'),
            self::MALICIOUS => __('security.threat.malicious'),
            self::UNKNOWN => __('security.threat.unknown'),
        };
    }

    /**
     * Get color class for UI display.
     */
    public function colorClass(): string
    {
        return match ($this) {
            self::FALSE_POSITIVE => 'text-gray-600 bg-gray-50',
            self::SUSPICIOUS => 'text-yellow-600 bg-yellow-50',
            self::MALICIOUS => 'text-red-600 bg-red-50',
            self::UNKNOWN => 'text-blue-600 bg-blue-50',
        };
    }

    /**
     * Get risk level for automated response.
     */
    public function riskLevel(): int
    {
        return match ($this) {
            self::FALSE_POSITIVE => 0,
            self::UNKNOWN => 1,
            self::SUSPICIOUS => 2,
            self::MALICIOUS => 3,
        };
    }

    /**
     * Check if classification requires automated response.
     */
    public function requiresAutomatedResponse(): bool
    {
        return $this === self::MALICIOUS;
    }

    /**
     * Check if classification requires human review.
     */
    public function requiresHumanReview(): bool
    {
        return $this === self::SUSPICIOUS || $this === self::UNKNOWN;
    }
}