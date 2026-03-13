<?php

declare(strict_types=1);

use App\Enums\DistributionMethod;
use App\Enums\PricingModel;
use App\Models\ServiceConfiguration;
use App\Services\UniversalBillingCalculator;
use App\ValueObjects\BillingPeriod;
use App\ValueObjects\UniversalConsumptionData;
use Carbon\Carbon;

test('UniversalBillingCalculator applies localized fixed charges surcharges minimum charge and tax', function () {
    $calculator = app(UniversalBillingCalculator::class);

    $serviceConfiguration = new ServiceConfiguration;
    $serviceConfiguration->pricing_model = PricingModel::CONSUMPTION_BASED;
    $serviceConfiguration->distribution_method = DistributionMethod::EQUAL;
    $serviceConfiguration->rate_schedule = [
        'unit_rate' => 0.20,
        'localization' => [
            'locale' => 'lt-LT',
            'minimum_charge' => 30,
            'tax_rate' => 20,
            'rounding_mode' => 'half_up',
            'money_precision' => 2,
            'fixed_charges' => [
                ['name' => 'Grid fee', 'amount' => 2],
            ],
            'surcharges' => [
                ['name' => 'Energy security levy', 'percentage' => 10],
            ],
        ],
    ];

    $consumption = UniversalConsumptionData::fromTotal(100.0);
    $period = new BillingPeriod(
        Carbon::parse('2026-02-01')->startOfDay(),
        Carbon::parse('2026-02-28')->endOfDay(),
    );

    $result = $calculator->calculateBill($serviceConfiguration, $consumption, $period);

    expect($result->baseAmount)->toBe(20.0)
        ->and($result->totalAmount)->toBe(36.0);

    $localizationDetails = $result->getCalculationDetail('localization');

    expect($localizationDetails)->toBeArray()
        ->and($localizationDetails['applied'])->toBeTrue()
        ->and($localizationDetails['locale'])->toBe('lt-LT')
        ->and($localizationDetails['money_precision'])->toBe(2);

    expect(collect($result->adjustments)->pluck('type')->toArray())
        ->toContain('localized_fixed_charge', 'localized_surcharge', 'localized_minimum_charge', 'localized_tax');
});

test('UniversalBillingCalculator applies localized rounding mode', function () {
    $calculator = app(UniversalBillingCalculator::class);
    $consumption = UniversalConsumptionData::fromTotal(1.0);
    $period = new BillingPeriod(
        Carbon::parse('2026-02-01')->startOfDay(),
        Carbon::parse('2026-02-28')->endOfDay(),
    );

    $baseSchedule = [
        'unit_rate' => 1.24,
        'localization' => [
            'surcharges' => [
                ['name' => 'Small adjustment', 'percentage' => 0.5],
            ],
            'money_precision' => 2,
        ],
    ];

    $down = new ServiceConfiguration;
    $down->pricing_model = PricingModel::CONSUMPTION_BASED;
    $down->distribution_method = DistributionMethod::EQUAL;
    $down->rate_schedule = array_replace_recursive($baseSchedule, [
        'localization' => ['rounding_mode' => 'down'],
    ]);

    $halfUp = new ServiceConfiguration;
    $halfUp->pricing_model = PricingModel::CONSUMPTION_BASED;
    $halfUp->distribution_method = DistributionMethod::EQUAL;
    $halfUp->rate_schedule = array_replace_recursive($baseSchedule, [
        'localization' => ['rounding_mode' => 'half_up'],
    ]);

    $downResult = $calculator->calculateBill($down, $consumption, $period);
    $halfUpResult = $calculator->calculateBill($halfUp, $consumption, $period);

    expect($downResult->totalAmount)->toBe(1.24)
        ->and($halfUpResult->totalAmount)->toBe(1.25);
});

test('UniversalBillingCalculator resolves time-of-use rates from time windows', function () {
    $calculator = app(UniversalBillingCalculator::class);

    $serviceConfiguration = new ServiceConfiguration;
    $serviceConfiguration->pricing_model = PricingModel::TIME_OF_USE;
    $serviceConfiguration->distribution_method = DistributionMethod::EQUAL;
    $serviceConfiguration->rate_schedule = [
        'time_windows' => [
            [
                'zone' => 'day',
                'start' => '07:00',
                'end' => '23:00',
                'rate' => 0.20,
                'day_types' => ['weekday'],
                'months' => [2],
            ],
            [
                'zone' => 'night',
                'start' => '23:00',
                'end' => '07:00',
                'rate' => 0.10,
                'day_types' => ['weekday'],
                'months' => [2],
            ],
        ],
        'default_rate' => 0.15,
    ];

    $consumption = UniversalConsumptionData::fromZones([
        'day' => 50,
        'night' => 50,
    ]);

    $period = new BillingPeriod(
        Carbon::parse('2026-02-16')->startOfDay(),
        Carbon::parse('2026-02-28')->endOfDay(),
    );

    $result = $calculator->calculateBill($serviceConfiguration, $consumption, $period);

    expect($result->totalAmount)->toBe(15.0)
        ->and($result->getCalculationDetail('rate_source'))->toBe('time_windows');
});
