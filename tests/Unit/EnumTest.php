<?php

use App\Enums\TariffType;
use App\Enums\TariffZone;
use App\Enums\WeekendLogic;

test('TariffType enum has correct values', function () {
    expect(TariffType::FLAT->value)->toBe('flat')
        ->and(TariffType::TIME_OF_USE->value)->toBe('time_of_use');
});

test('TariffType enum provides labels', function () {
    expect(TariffType::FLAT->label())->toBe('Flat Rate')
        ->and(TariffType::TIME_OF_USE->label())->toBe('Time of Use');
});

test('WeekendLogic enum has correct values', function () {
    expect(WeekendLogic::APPLY_NIGHT_RATE->value)->toBe('apply_night_rate')
        ->and(WeekendLogic::APPLY_DAY_RATE->value)->toBe('apply_day_rate')
        ->and(WeekendLogic::APPLY_WEEKEND_RATE->value)->toBe('apply_weekend_rate');
});

test('WeekendLogic enum provides labels', function () {
    expect(WeekendLogic::APPLY_NIGHT_RATE->label())->toContain('Night Rate')
        ->and(WeekendLogic::APPLY_DAY_RATE->label())->toContain('Day Rate')
        ->and(WeekendLogic::APPLY_WEEKEND_RATE->label())->toContain('Weekend Rate');
});

test('TariffZone enum has correct values', function () {
    expect(TariffZone::DAY->value)->toBe('day')
        ->and(TariffZone::NIGHT->value)->toBe('night')
        ->and(TariffZone::WEEKEND->value)->toBe('weekend');
});

test('TariffZone enum provides labels', function () {
    expect(TariffZone::DAY->label())->toBe('Day Rate')
        ->and(TariffZone::NIGHT->label())->toBe('Night Rate')
        ->and(TariffZone::WEEKEND->label())->toBe('Weekend Rate');
});
