<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Integration service health status enumeration.
 * 
 * Defines the possible health states for external service integrations
 * used by the health monitoring and circuit breaker systems.
 * 
 * @package App\Enums
 * @author Laravel Development Team
 * @since 1.0.0
 */
enum IntegrationStatus: string
{
    case HEALTHY = 'healthy';
    case DEGRADED = 'degraded';
    case UNHEALTHY = 'unhealthy';
    case CIRCUIT_OPEN = 'circuit_open';
    case MAINTENANCE = 'maintenance';
    case UNKNOWN = 'unknown';

    /**
     * Get human-readable label for the status.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::HEALTHY => __('integration.status.healthy'),
            self::DEGRADED => __('integration.status.degraded'),
            self::UNHEALTHY => __('integration.status.unhealthy'),
            self::CIRCUIT_OPEN => __('integration.status.circuit_open'),
            self::MAINTENANCE => __('integration.status.maintenance'),
            self::UNKNOWN => __('integration.status.unknown'),
        };
    }

    /**
     * Get color for UI display.
     */
    public function getColor(): string
    {
        return match ($this) {
            self::HEALTHY => 'success',
            self::DEGRADED => 'warning',
            self::UNHEALTHY => 'danger',
            self::CIRCUIT_OPEN => 'danger',
            self::MAINTENANCE => 'info',
            self::UNKNOWN => 'gray',
        };
    }

    /**
     * Get icon for UI display.
     */
    public function getIcon(): string
    {
        return match ($this) {
            self::HEALTHY => 'heroicon-o-check-circle',
            self::DEGRADED => 'heroicon-o-exclamation-triangle',
            self::UNHEALTHY => 'heroicon-o-x-circle',
            self::CIRCUIT_OPEN => 'heroicon-o-no-symbol',
            self::MAINTENANCE => 'heroicon-o-wrench-screwdriver',
            self::UNKNOWN => 'heroicon-o-question-mark-circle',
        };
    }

    /**
     * Check if status indicates service is available.
     */
    public function isAvailable(): bool
    {
        return match ($this) {
            self::HEALTHY, self::DEGRADED => true,
            self::UNHEALTHY, self::CIRCUIT_OPEN, self::MAINTENANCE, self::UNKNOWN => false,
        };
    }

    /**
     * Check if status requires attention.
     */
    public function requiresAttention(): bool
    {
        return match ($this) {
            self::HEALTHY => false,
            self::DEGRADED, self::UNHEALTHY, self::CIRCUIT_OPEN, self::MAINTENANCE, self::UNKNOWN => true,
        };
    }

    /**
     * Get priority level for alerting (1 = highest, 5 = lowest).
     */
    public function getAlertPriority(): int
    {
        return match ($this) {
            self::UNHEALTHY, self::CIRCUIT_OPEN => 1,
            self::DEGRADED => 2,
            self::MAINTENANCE => 3,
            self::UNKNOWN => 4,
            self::HEALTHY => 5,
        };
    }

    /**
     * Get all statuses that indicate problems.
     * 
     * @return array<self>
     */
    public static function problemStatuses(): array
    {
        return [
            self::DEGRADED,
            self::UNHEALTHY,
            self::CIRCUIT_OPEN,
            self::UNKNOWN,
        ];
    }

    /**
     * Get all statuses that allow operations.
     * 
     * @return array<self>
     */
    public static function operationalStatuses(): array
    {
        return [
            self::HEALTHY,
            self::DEGRADED,
        ];
    }
}