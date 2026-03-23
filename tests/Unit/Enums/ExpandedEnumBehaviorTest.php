<?php

use App\Contracts\BillingServiceInterface;
use App\Enums\AuditLogAction;
use App\Enums\DistributionMethod;
use App\Enums\IntegrationHealthStatus;
use App\Enums\MeterReadingSubmissionMethod;
use App\Enums\MeterStatus;
use App\Enums\MeterType;
use App\Enums\OrganizationStatus;
use App\Enums\PaymentMethod;
use App\Enums\PricingModel;
use App\Enums\PropertyType;
use App\Enums\SecurityViolationType;
use App\Enums\ServiceType;
use App\Enums\SubscriptionDuration;
use App\Enums\SubscriptionPlan;
use App\Enums\SystemSettingCategory;
use App\Enums\TariffType;
use App\Enums\TariffZone;
use App\Enums\WeekendLogic;
use Tests\TestCase;

uses(TestCase::class)->group('unit');

dataset('expanded-enum-values', [
    'audit log actions' => [
        AuditLogAction::class,
        ['created', 'updated', 'deleted', 'archived', 'exported', 'approved', 'rejected', 'restored', 'suspended', 'reinstated', 'impersonated', 'logged_in', 'logged_out', 'sent'],
    ],
    'distribution methods' => [
        DistributionMethod::class,
        ['equal', 'area', 'by_consumption', 'by_occupancy', 'fixed_share', 'weighted_share', 'custom_formula'],
    ],
    'integration health statuses' => [
        IntegrationHealthStatus::class,
        ['healthy', 'degraded', 'failed', 'maintenance', 'unknown'],
    ],
    'meter reading submission methods' => [
        MeterReadingSubmissionMethod::class,
        ['admin_manual', 'tenant_portal', 'mobile_app', 'api_integration', 'iot_gateway', 'estimated', 'import'],
    ],
    'meter statuses' => [
        MeterStatus::class,
        ['active', 'inactive', 'maintenance', 'faulty', 'retired'],
    ],
    'meter types' => [
        MeterType::class,
        ['water', 'water_cold', 'water_hot', 'electricity', 'gas', 'heating', 'cooling', 'steam', 'solar', 'custom'],
    ],
    'organization statuses' => [
        OrganizationStatus::class,
        ['active', 'pending', 'suspended', 'cancelled', 'archived'],
    ],
    'payment methods' => [
        PaymentMethod::class,
        ['bank_transfer', 'direct_debit', 'card', 'digital_wallet', 'cheque', 'cash', 'other'],
    ],
    'pricing models' => [
        PricingModel::class,
        ['fixed_monthly', 'fixed_daily', 'consumption_based', 'tiered_rates', 'hybrid', 'custom_formula', 'flat', 'time_of_use'],
    ],
    'property types' => [
        PropertyType::class,
        ['apartment', 'house', 'studio', 'office', 'retail', 'warehouse', 'commercial', 'industrial', 'mixed_use', 'garage', 'parking', 'storage'],
    ],
    'security violation types' => [
        SecurityViolationType::class,
        ['authentication', 'authorization', 'rate_limit', 'injection', 'csp', 'suspicious_ip', 'impersonation', 'tenant_isolation', 'data_access', 'data_export', 'session_hijack'],
    ],
    'service types' => [
        ServiceType::class,
        ['electricity', 'water', 'hot_water', 'heating', 'gas', 'sewage', 'cooling', 'steam', 'solar', 'internet', 'maintenance', 'waste'],
    ],
    'subscription durations' => [
        SubscriptionDuration::class,
        ['monthly', 'quarterly', 'semi_annual', 'yearly', 'biennial', 'triennial'],
    ],
    'subscription plans' => [
        SubscriptionPlan::class,
        ['starter', 'basic', 'professional', 'enterprise', 'custom'],
    ],
    'system setting categories' => [
        SystemSettingCategory::class,
        ['general', 'billing', 'localization', 'security', 'integrations', 'notifications', 'email', 'subscription', 'backups', 'maintenance', 'reporting', 'api', 'compliance'],
    ],
    'tariff types' => [
        TariffType::class,
        ['flat', 'time_of_use', 'seasonal'],
    ],
    'tariff zones' => [
        TariffZone::class,
        ['day', 'night', 'weekend', 'peak', 'off_peak', 'shoulder', 'holiday', 'super_off_peak'],
    ],
    'weekend logic' => [
        WeekendLogic::class,
        ['apply_night_rate', 'apply_day_rate', 'apply_weekend_rate', 'apply_off_peak_rate', 'apply_peak_rate', 'apply_shoulder_rate', 'apply_holiday_rate'],
    ],
]);

it('supports the expanded enum variants used across Tenanto', function (string $enumClass, array $expectedValues) {
    expect($enumClass::values())->toBe($expectedValues);
})->with('expanded-enum-values');

dataset('service-type-meter-compatibility', [
    'water' => [ServiceType::WATER, ['water', 'water_cold'], 'm3'],
    'hot water' => [ServiceType::HOT_WATER, ['water_hot'], 'm3'],
    'sewage' => [ServiceType::SEWAGE, ['water', 'water_cold', 'water_hot'], 'm3'],
    'cooling' => [ServiceType::COOLING, ['cooling'], 'kWh'],
    'steam' => [ServiceType::STEAM, ['steam'], 'MWh'],
    'solar' => [ServiceType::SOLAR, ['solar'], 'kWh'],
    'internet' => [ServiceType::INTERNET, [], 'month'],
    'maintenance' => [ServiceType::MAINTENANCE, [], 'month'],
    'waste' => [ServiceType::WASTE, [], 'collection'],
    'electricity' => [ServiceType::ELECTRICITY, ['electricity'], 'kWh'],
]);

it('maps service types to compatible meter types and units', function (
    ServiceType $serviceType,
    array $expectedMeterValues,
    string $expectedUnit,
) {
    expect(array_map(
        static fn (MeterType $meterType): string => $meterType->value,
        $serviceType->compatibleMeterTypes(),
    ))->toBe($expectedMeterValues)
        ->and($serviceType->defaultUnit())->toBe($expectedUnit);
})->with('service-type-meter-compatibility');

it('maps organization and meter lifecycle states to supported behavior', function () {
    expect(OrganizationStatus::ACTIVE->permitsAccess())->toBeTrue()
        ->and(OrganizationStatus::PENDING->permitsAccess())->toBeFalse()
        ->and(OrganizationStatus::ARCHIVED->badgeColor())->toBe('gray')
        ->and(MeterStatus::MAINTENANCE->badgeColor())->toBe('warning')
        ->and(MeterStatus::FAULTY->toggleTarget())->toBe(MeterStatus::ACTIVE)
        ->and(MeterStatus::RETIRED->toggleTarget())->toBe(MeterStatus::RETIRED);
});

it('supports occupancy-based shared service cost distribution', function () {
    $billingService = app(BillingServiceInterface::class);

    expect($billingService->distributeSharedServiceCost('120.00', DistributionMethod::BY_OCCUPANCY, [
        'participant_occupants' => 2,
        'total_occupants' => 8,
    ]))->toBe('30.00');
});

it('supports fixed-share and weighted-share shared service distribution', function () {
    $billingService = app(BillingServiceInterface::class);

    expect($billingService->distributeSharedServiceCost('120.00', DistributionMethod::FIXED_SHARE, [
        'fixed_share' => '18.75',
    ]))->toBe('18.75')
        ->and($billingService->distributeSharedServiceCost('120.00', DistributionMethod::WEIGHTED_SHARE, [
            'participant_weight' => 3,
            'total_weight' => 12,
        ]))->toBe('30.00');
});

it('marks fixed-daily pricing and seasonal tariffs with the right behavior flags', function () {
    expect(PricingModel::FIXED_DAILY->requiresBillingPeriodQuantity())->toBeTrue()
        ->and(PricingModel::FIXED_DAILY->requiresConsumptionData())->toBeFalse()
        ->and(TariffType::SEASONAL->requiresRate())->toBeTrue()
        ->and(TariffType::SEASONAL->supportsZones())->toBeFalse();
});

it('exposes expanded subscription durations in months', function () {
    expect(SubscriptionDuration::SEMI_ANNUAL->months())->toBe(6)
        ->and(SubscriptionDuration::BIENNIAL->months())->toBe(24)
        ->and(SubscriptionDuration::TRIENNIAL->months())->toBe(36);
});

it('exposes expanded subscription plan capacity snapshots', function () {
    expect(SubscriptionPlan::STARTER->limits())->toBe([
        'properties' => 3,
        'tenants' => 8,
        'meters' => 15,
        'invoices' => 30,
    ])->and(SubscriptionPlan::CUSTOM->limits())->toBe([
        'properties' => 2500,
        'tenants' => 10000,
        'meters' => 20000,
        'invoices' => 100000,
    ]);
});
