<?php

declare(strict_types=1);

use App\Enums\DistributionMethod;
use App\Enums\PricingModel;
use App\Models\ServiceConfiguration;
use App\Services\UniversalBillingCalculator;
use App\ValueObjects\BillingPeriod;
use App\ValueObjects\UniversalConsumptionData;
use Carbon\Carbon;

test('UniversalBillingCalculator evaluates custom formula pricing models', function () {
    $calculator = app(UniversalBillingCalculator::class);

    $serviceConfiguration = new ServiceConfiguration();
    $serviceConfiguration->pricing_model = PricingModel::CUSTOM_FORMULA;
    $serviceConfiguration->distribution_method = DistributionMethod::EQUAL;
    $serviceConfiguration->rate_schedule = [
        'formula' => 'consumption * rate + base_fee',
        'variables' => [
            'rate' => 0.2,
            'base_fee' => 10,
        ],
    ];

    $consumption = UniversalConsumptionData::fromTotal(100.0);
    $billingPeriod = new BillingPeriod(
        Carbon::parse('2025-06-01')->startOfDay(),
        Carbon::parse('2025-06-30')->endOfDay(),
    );

    $result = $calculator->calculateBill($serviceConfiguration, $consumption, $billingPeriod);

    expect($result->totalAmount)->toBe(30.0);
    expect($result->getCalculationDetail('pricing_model'))->toBe(PricingModel::CUSTOM_FORMULA->value);
    expect($result->getCalculationDetail('formula'))->toBe('consumption * rate + base_fee');

    $variables = $result->getCalculationDetail('variables');
    expect($variables)->toBeArray();
    expect($variables['consumption'])->toBe(100.0);
    expect($variables['rate'])->toBe(0.2);
    expect($variables['base_fee'])->toBe(10);
});

