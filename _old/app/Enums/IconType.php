<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Icon type enum mapping legacy icon keys to Heroicons.
 *
 * This enum provides type-safe icon references and maps legacy
 * string keys to Heroicon identifiers from the blade-heroicons package.
 *
 * @see https://heroicons.com/ for available icons
 */
enum IconType: string
{
    case METER = 'heroicon-o-cpu-chip';
    case INVOICE = 'heroicon-o-document-text';
    case SHIELD = 'heroicon-o-shield-check';
    case CHART = 'heroicon-o-chart-bar';
    case ROCKET = 'heroicon-o-rocket-launch';
    case USERS = 'heroicon-o-user-group';
    case DEFAULT = 'heroicon-o-check-circle';

    /**
     * Get the Heroicon identifier for this icon type.
     */
    public function heroicon(): string
    {
        return $this->value;
    }

    /**
     * Resolve icon from legacy key for backward compatibility.
     *
     * Maps old string-based icon keys to typed enum cases.
     * Unknown keys default to the DEFAULT icon.
     */
    public static function fromLegacyKey(string $key): self
    {
        return match ($key) {
            'meter' => self::METER,
            'invoice' => self::INVOICE,
            'shield' => self::SHIELD,
            'chart' => self::CHART,
            'rocket' => self::ROCKET,
            'users' => self::USERS,
            default => self::DEFAULT,
        };
    }
}
