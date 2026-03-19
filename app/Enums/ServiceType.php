<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum ServiceType: string implements HasLabel
{
    use HasTranslatedLabel;

    case ELECTRICITY = 'electricity';
    case WATER = 'water';
    case HOT_WATER = 'hot_water';
    case HEATING = 'heating';
    case GAS = 'gas';
    case SEWAGE = 'sewage';
    case COOLING = 'cooling';
    case STEAM = 'steam';
    case SOLAR = 'solar';
    case INTERNET = 'internet';
    case MAINTENANCE = 'maintenance';
    case WASTE = 'waste';

    public function defaultUnit(): string
    {
        return match ($this) {
            self::ELECTRICITY,
            self::HEATING,
            self::COOLING,
            self::SOLAR => 'kWh',
            self::STEAM => 'MWh',
            self::WATER,
            self::HOT_WATER,
            self::GAS,
            self::SEWAGE => 'm3',
            self::INTERNET,
            self::MAINTENANCE => 'month',
            self::WASTE => 'collection',
        };
    }

    /**
     * @return array<int, MeterType>
     */
    public function compatibleMeterTypes(): array
    {
        return match ($this) {
            self::ELECTRICITY => [MeterType::ELECTRICITY],
            self::WATER => [MeterType::WATER, MeterType::WATER_COLD],
            self::HOT_WATER => [MeterType::WATER_HOT],
            self::HEATING => [MeterType::HEATING],
            self::GAS => [MeterType::GAS],
            self::SEWAGE => [MeterType::WATER, MeterType::WATER_COLD, MeterType::WATER_HOT],
            self::COOLING => [MeterType::COOLING],
            self::STEAM => [MeterType::STEAM],
            self::SOLAR => [MeterType::SOLAR],
            self::INTERNET,
            self::MAINTENANCE,
            self::WASTE => [],
        };
    }
}
