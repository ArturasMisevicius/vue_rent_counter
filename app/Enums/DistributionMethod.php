<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Distribution methods for circulation cost allocation.
 */
enum DistributionMethod: string
{
    use HasLabel;

    case EQUAL = 'equal';
    case AREA = 'area';
    case BY_CONSUMPTION = 'by_consumption';
    case CUSTOM_FORMULA = 'custom_formula';

    public function getLabel(): string
    {
        return match ($this) {
            self::EQUAL => __('enums.distribution_method.equal'),
            self::AREA => __('enums.distribution_method.area'),
            self::BY_CONSUMPTION => __('enums.distribution_method.by_consumption'),
            self::CUSTOM_FORMULA => __('enums.distribution_method.custom_formula'),
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::EQUAL => __('enums.distribution_method.equal_description'),
            self::AREA => __('enums.distribution_method.area_description'),
            self::BY_CONSUMPTION => __('enums.distribution_method.by_consumption_description'),
            self::CUSTOM_FORMULA => __('enums.distribution_method.custom_formula_description'),
        };
    }

    /**
     * Check if this method requires area data.
     */
    public function requiresAreaData(): bool
    {
        return $this === self::AREA;
    }

    /**
     * Check if this method requires consumption data.
     */
    public function requiresConsumptionData(): bool
    {
        return $this === self::BY_CONSUMPTION;
    }

    /**
     * Check if this method supports custom formulas.
     */
    public function supportsCustomFormulas(): bool
    {
        return $this === self::CUSTOM_FORMULA;
    }

    /**
     * Get supported area types for area-based distribution.
     */
    public function getSupportedAreaTypes(): array
    {
        if (!$this->requiresAreaData()) {
            return [];
        }

        return [
            'total_area' => __('enums.area_type.total_area'),
            'heated_area' => __('enums.area_type.heated_area'),
            'commercial_area' => __('enums.area_type.commercial_area'),
        ];
    }
}