<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Types of gyvatukas calculations.
 */
enum GyvatukasCalculationType: string
{
    use HasLabel;

    case SUMMER = 'summer';
    case WINTER = 'winter';

    public function getLabel(): string
    {
        return match ($this) {
            self::SUMMER => __('enums.gyvatukas_calculation_type.summer'),
            self::WINTER => __('enums.gyvatukas_calculation_type.winter'),
        };
    }

    public function getCachePrefix(): string
    {
        return match ($this) {
            self::SUMMER => 'gyvatukas:summer',
            self::WINTER => 'gyvatukas:winter',
        };
    }
}