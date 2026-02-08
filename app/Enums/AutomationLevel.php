<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Automation level for billing operations.
 */
enum AutomationLevel: string
{
    case MANUAL = 'manual';
    case SEMI_AUTOMATED = 'semi_automated';
    case FULLY_AUTOMATED = 'fully_automated';
    case APPROVAL_REQUIRED = 'approval_required';

    /**
     * Get the human-readable label for the automation level.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::MANUAL => __('enums.automation_level.manual'),
            self::SEMI_AUTOMATED => __('enums.automation_level.semi_automated'),
            self::FULLY_AUTOMATED => __('enums.automation_level.fully_automated'),
            self::APPROVAL_REQUIRED => __('enums.automation_level.approval_required'),
        };
    }

    /**
     * Get the description for the automation level.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::MANUAL => __('enums.automation_level.manual_description'),
            self::SEMI_AUTOMATED => __('enums.automation_level.semi_automated_description'),
            self::FULLY_AUTOMATED => __('enums.automation_level.fully_automated_description'),
            self::APPROVAL_REQUIRED => __('enums.automation_level.approval_required_description'),
        };
    }

    /**
     * Check if automation level requires human approval.
     */
    public function requiresApproval(): bool
    {
        return in_array($this, [self::MANUAL, self::APPROVAL_REQUIRED]);
    }

    /**
     * Check if automation level allows automatic processing.
     */
    public function allowsAutomation(): bool
    {
        return in_array($this, [self::SEMI_AUTOMATED, self::FULLY_AUTOMATED]);
    }

    /**
     * Check if automation level requires human intervention.
     */
    public function requiresHumanIntervention(): bool
    {
        return in_array($this, [self::MANUAL, self::APPROVAL_REQUIRED, self::SEMI_AUTOMATED]);
    }

    /**
     * Check if automation level is fully automated.
     */
    public function isFullyAutomated(): bool
    {
        return $this === self::FULLY_AUTOMATED;
    }

    /**
     * Check if automation level is semi-automated.
     */
    public function isSemiAutomated(): bool
    {
        return $this === self::SEMI_AUTOMATED;
    }

    /**
     * Get the icon for the automation level.
     */
    public function getIcon(): string
    {
        return match ($this) {
            self::MANUAL => 'heroicon-o-user',
            self::SEMI_AUTOMATED => 'heroicon-o-cog-6-tooth',
            self::FULLY_AUTOMATED => 'heroicon-o-bolt',
            self::APPROVAL_REQUIRED => 'heroicon-o-shield-check',
        };
    }

    /**
     * Get the color for the automation level badge.
     */
    public function getColor(): string
    {
        return match ($this) {
            self::MANUAL => 'gray',
            self::SEMI_AUTOMATED => 'warning',
            self::FULLY_AUTOMATED => 'success',
            self::APPROVAL_REQUIRED => 'info',
        };
    }
}