<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasTranslatableLabel;
use Filament\Support\Contracts\HasLabel;

/**
 * Pricing models for universal utility services.
 * Extends TariffType capabilities with additional models.
 */
enum PricingModel: string implements HasLabel
{
    use HasTranslatableLabel;

    case FIXED_MONTHLY = 'fixed_monthly';
    case CONSUMPTION_BASED = 'consumption_based';
    case TIERED_RATES = 'tiered_rates';
    case HYBRID = 'hybrid';
    case CUSTOM_FORMULA = 'custom_formula';
    
    // Legacy compatibility with TariffType
    case FLAT = 'flat';
    case TIME_OF_USE = 'time_of_use';

    /**
     * Get the description for this pricing model.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::FIXED_MONTHLY => __('enums.pricing_model.fixed_monthly_description'),
            self::CONSUMPTION_BASED => __('enums.pricing_model.consumption_based_description'),
            self::TIERED_RATES => __('enums.pricing_model.tiered_rates_description'),
            self::HYBRID => __('enums.pricing_model.hybrid_description'),
            self::CUSTOM_FORMULA => __('enums.pricing_model.custom_formula_description'),
            self::FLAT => __('enums.pricing_model.flat_description'),
            self::TIME_OF_USE => __('enums.pricing_model.time_of_use_description'),
        };
    }

    /**
     * Check if this model requires consumption data.
     */
    public function requiresConsumptionData(): bool
    {
        return in_array($this, [
            self::CONSUMPTION_BASED,
            self::TIERED_RATES,
            self::HYBRID,
            self::TIME_OF_USE,
        ]);
    }

    /**
     * Check if this model supports time-based pricing.
     */
    public function supportsTimeBasedPricing(): bool
    {
        return in_array($this, [
            self::TIME_OF_USE,
            self::HYBRID,
            self::CUSTOM_FORMULA,
        ]);
    }

    /**
     * Check if this model supports mathematical expressions.
     */
    public function supportsCustomFormulas(): bool
    {
        return $this === self::CUSTOM_FORMULA;
    }

    /**
     * Convert from legacy TariffType enum.
     */
    public static function fromTariffType(string $tariffType): self
    {
        return match ($tariffType) {
            'flat' => self::FLAT,
            'time_of_use' => self::TIME_OF_USE,
            default => self::CONSUMPTION_BASED,
        };
    }

    /**
     * Convert to legacy TariffType enum for backward compatibility.
     */
    public function toTariffType(): string
    {
        return match ($this) {
            self::FLAT => 'flat',
            self::TIME_OF_USE => 'time_of_use',
            default => 'consumption_based',
        };
    }
}