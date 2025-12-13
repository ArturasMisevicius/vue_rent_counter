<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasTranslatableLabel;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use InvalidArgumentException;

/**
 * Pricing models for universal utility services.
 * 
 * Extends TariffType capabilities with additional models for flexible
 * utility billing scenarios. Supports consumption-based, tiered, hybrid,
 * and custom formula pricing models.
 * 
 * @see TariffType For legacy compatibility
 * @see DistributionMethod For cost distribution patterns
 */
enum PricingModel: string implements HasLabel, HasColor, HasIcon
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
     * Get the color for Filament components.
     */
    public function getColor(): string
    {
        return match ($this) {
            self::FIXED_MONTHLY => 'gray',
            self::CONSUMPTION_BASED => 'info',
            self::TIERED_RATES => 'warning',
            self::HYBRID => 'success',
            self::CUSTOM_FORMULA => 'danger',
            self::FLAT => 'gray',
            self::TIME_OF_USE => 'primary',
        };
    }

    /**
     * Get the icon for Filament components.
     */
    public function getIcon(): string
    {
        return match ($this) {
            self::FIXED_MONTHLY => 'heroicon-o-banknotes',
            self::CONSUMPTION_BASED => 'heroicon-o-chart-bar',
            self::TIERED_RATES => 'heroicon-o-bars-3-bottom-left',
            self::HYBRID => 'heroicon-o-puzzle-piece',
            self::CUSTOM_FORMULA => 'heroicon-o-calculator',
            self::FLAT => 'heroicon-o-minus',
            self::TIME_OF_USE => 'heroicon-o-clock',
        };
    }

    /**
     * Check if this model requires consumption data.
     * 
     * Performance: Uses match expression instead of in_array for O(1) lookup.
     */
    public function requiresConsumptionData(): bool
    {
        return match ($this) {
            self::CONSUMPTION_BASED,
            self::TIERED_RATES,
            self::HYBRID,
            self::TIME_OF_USE => true,
            default => false,
        };
    }

    /**
     * Check if this model supports time-based pricing.
     */
    public function supportsTimeBasedPricing(): bool
    {
        return match ($this) {
            self::TIME_OF_USE,
            self::HYBRID,
            self::CUSTOM_FORMULA => true,
            default => false,
        };
    }

    /**
     * Check if this model supports mathematical expressions.
     */
    public function supportsCustomFormulas(): bool
    {
        return $this === self::CUSTOM_FORMULA;
    }

    /**
     * Check if this model supports tiered rate structures.
     */
    public function supportsTieredRates(): bool
    {
        return match ($this) {
            self::TIERED_RATES,
            self::HYBRID => true,
            default => false,
        };
    }

    /**
     * Check if this model has fixed components.
     */
    public function hasFixedComponents(): bool
    {
        return match ($this) {
            self::FIXED_MONTHLY,
            self::HYBRID,
            self::FLAT => true,
            default => false,
        };
    }

    /**
     * Get the complexity level of this pricing model.
     * 
     * @return 'simple'|'moderate'|'complex'
     */
    public function getComplexityLevel(): string
    {
        return match ($this) {
            self::FIXED_MONTHLY, self::FLAT => 'simple',
            self::CONSUMPTION_BASED, self::TIME_OF_USE => 'moderate',
            self::TIERED_RATES, self::HYBRID, self::CUSTOM_FORMULA => 'complex',
        };
    }

    /**
     * Convert from legacy TariffType enum.
     * 
     * @throws InvalidArgumentException When tariffType is invalid
     */
    public static function fromTariffType(string $tariffType): self
    {
        return match ($tariffType) {
            'flat' => self::FLAT,
            'time_of_use' => self::TIME_OF_USE,
            default => throw new InvalidArgumentException("Invalid tariff type: {$tariffType}"),
        };
    }

    /**
     * Convert from TariffType enum instance.
     */
    public static function fromTariffTypeEnum(TariffType $tariffType): self
    {
        return match ($tariffType) {
            TariffType::FLAT => self::FLAT,
            TariffType::TIME_OF_USE => self::TIME_OF_USE,
        };
    }

    /**
     * Convert to legacy TariffType enum for backward compatibility.
     * 
     * @throws InvalidArgumentException When conversion is not possible
     */
    public function toTariffType(): string
    {
        return match ($this) {
            self::FLAT => 'flat',
            self::TIME_OF_USE => 'time_of_use',
            default => throw new InvalidArgumentException("Cannot convert {$this->value} to TariffType"),
        };
    }

    /**
     * Check if this pricing model can be converted to TariffType.
     */
    public function isLegacyCompatible(): bool
    {
        return match ($this) {
            self::FLAT, self::TIME_OF_USE => true,
            default => false,
        };
    }

    /**
     * Get all modern pricing models (excluding legacy ones).
     * 
     * @return array<self>
     */
    public static function modernModels(): array
    {
        return [
            self::FIXED_MONTHLY,
            self::CONSUMPTION_BASED,
            self::TIERED_RATES,
            self::HYBRID,
            self::CUSTOM_FORMULA,
        ];
    }

    /**
     * Get all legacy pricing models.
     * 
     * @return array<self>
     */
    public static function legacyModels(): array
    {
        return [
            self::FLAT,
            self::TIME_OF_USE,
        ];
    }

    /**
     * Get pricing models that require meter readings.
     * 
     * @return array<self>
     */
    public static function meterReadingModels(): array
    {
        return array_filter(
            self::cases(),
            fn (self $model) => $model->requiresConsumptionData()
        );
    }
}