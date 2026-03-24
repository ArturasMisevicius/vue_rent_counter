<?php

use App\Contracts\BillingServiceInterface;
use App\Enums\DistributionMethod;
use App\Enums\InvoiceStatus;
use App\Enums\PricingModel;
use App\Enums\ServiceType;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Organization;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\Provider;
use App\Models\ServiceConfiguration;
use App\Models\Tariff;
use App\Models\User;
use App\Models\UtilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('calculates a flat rate charge exactly', function () {
    $billingService = app(BillingServiceInterface::class);

    expect($billingService->calculateFlatRateCharge('12.5', '1.20', '2.00'))
        ->toBe('17.00');
});

it('calculates time-of-use charges across peak and off-peak zones', function () {
    $billingService = app(BillingServiceInterface::class);

    $total = $billingService->calculateTimeOfUseCharge(
        [
            'day' => '10',
            'night' => '5',
        ],
        [
            ['id' => 'day', 'rate' => '0.18'],
            ['id' => 'night', 'rate' => '0.10'],
        ],
        '1.00',
    );

    expect($total)->toBe('3.30');
});

it('calculates fixed-daily charges across the full billing period', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);
    $building = Building::factory()->for($organization)->create();
    $provider = Provider::factory()->forOrganization($organization)->create([
        'service_type' => ServiceType::WATER,
    ]);
    $tariff = Tariff::factory()->for($provider)->flat()->create([
        'configuration' => [
            'type' => 'seasonal',
            'currency' => 'EUR',
            'rate' => 2.50,
        ],
    ]);
    $utilityService = UtilityService::factory()->for($organization)->create([
        'service_type_bridge' => ServiceType::WATER,
        'default_pricing_model' => PricingModel::FIXED_DAILY,
        'unit_of_measurement' => 'day',
    ]);
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);
    $property = Property::factory()->for($organization)->for($building)->create([
        'name' => 'A-10',
    ]);

    PropertyAssignment::factory()->for($organization)->for($property)->for($tenant, 'tenant')->create([
        'unit_area_sqm' => 50,
        'assigned_at' => '2025-12-01 00:00:00',
    ]);

    ServiceConfiguration::factory()->for($organization)->for($property)->for($utilityService)->for($provider)->for($tariff)->create([
        'pricing_model' => PricingModel::FIXED_DAILY,
        'distribution_method' => DistributionMethod::EQUAL,
        'rate_schedule' => [
            'unit_rate' => 2.50,
            'base_fee' => 5.00,
        ],
        'is_shared_service' => false,
        'effective_from' => '2025-12-01 00:00:00',
    ]);

    $result = app(BillingServiceInterface::class)->generateBulkInvoices($organization, [
        'billing_period_start' => '2026-01-01',
        'billing_period_end' => '2026-01-10',
        'due_date' => '2026-01-24',
    ], $admin);

    /** @var Invoice $invoice */
    $invoice = $result['created']->first();

    expect($invoice)->not->toBeNull()
        ->and($invoice->total_amount)->toBe('30.00')
        ->and((string) $invoice->items[0]['quantity'])->toBe('10.000')
        ->and((string) $invoice->items[0]['unit_price'])->toBe('2.5000');
});

dataset('shared-service-distribution-methods', [
    'equal' => [
        DistributionMethod::EQUAL,
        ['participant_count' => 3],
        '90.00',
        '30.00',
    ],
    'area' => [
        DistributionMethod::AREA,
        ['participant_area' => '45', 'total_area' => '180'],
        '100.00',
        '25.00',
    ],
    'consumption' => [
        DistributionMethod::BY_CONSUMPTION,
        ['participant_consumption' => '30', 'total_consumption' => '150'],
        '120.00',
        '24.00',
    ],
]);

it('distributes shared service costs using the configured distribution method', function (
    DistributionMethod $distributionMethod,
    array $context,
    string $totalCost,
    string $expectedShare,
) {
    $billingService = app(BillingServiceInterface::class);

    expect($billingService->distributeSharedServiceCost($totalCost, $distributionMethod, $context))
        ->toBe($expectedShare);
})->with('shared-service-distribution-methods');

it('keeps shared-service invoice totals equal to the source amount after allocation rounding', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);
    $building = Building::factory()->for($organization)->create();
    $provider = Provider::factory()->forOrganization($organization)->create([
        'service_type' => ServiceType::WATER,
    ]);
    $tariff = Tariff::factory()->for($provider)->flat()->create([
        'configuration' => [
            'type' => 'flat',
            'currency' => 'EUR',
            'rate' => 100.00,
        ],
    ]);
    $utilityService = UtilityService::factory()->for($organization)->create([
        'service_type_bridge' => ServiceType::WATER,
        'default_pricing_model' => PricingModel::FLAT,
        'unit_of_measurement' => 'month',
    ]);

    foreach (['A-1', 'A-2', 'A-3'] as $unitNumber) {
        $tenant = User::factory()->tenant()->create([
            'organization_id' => $organization->id,
        ]);
        $property = Property::factory()->for($organization)->for($building)->create([
            'name' => $unitNumber,
        ]);

        PropertyAssignment::factory()->for($organization)->for($property)->for($tenant, 'tenant')->create([
            'unit_area_sqm' => 50,
        ]);

        ServiceConfiguration::factory()->for($organization)->for($property)->for($utilityService)->for($provider)->for($tariff)->create([
            'pricing_model' => PricingModel::FLAT,
            'distribution_method' => DistributionMethod::EQUAL,
            'rate_schedule' => [
                'unit_rate' => 100.00,
            ],
            'is_shared_service' => true,
        ]);
    }

    $result = app(BillingServiceInterface::class)->generateBulkInvoices($organization, [
        'billing_period_start' => now()->startOfMonth()->toDateString(),
        'billing_period_end' => now()->endOfMonth()->toDateString(),
        'due_date' => now()->addDays(14)->toDateString(),
    ], $admin);

    $totals = $result['created']
        ->map(fn (Invoice $invoice): string => (string) $invoice->total_amount)
        ->all();

    expect($totals)->toBe([
        '33.34',
        '33.33',
        '33.33',
    ])->and(
        app(BillingServiceInterface::class)->calculateFlatRateCharge('1', '100.00')
    )->toBe('100.00');
});

it('bulk generates one invoice per active tenant assignment', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);
    $building = Building::factory()->for($organization)->create();
    $provider = Provider::factory()->forOrganization($organization)->create([
        'service_type' => ServiceType::WATER,
    ]);
    $tariff = Tariff::factory()->for($provider)->flat()->create([
        'configuration' => [
            'type' => 'flat',
            'currency' => 'EUR',
            'rate' => 25.00,
        ],
    ]);
    $utilityService = UtilityService::factory()->for($organization)->create([
        'service_type_bridge' => ServiceType::WATER,
        'default_pricing_model' => PricingModel::FLAT,
        'unit_of_measurement' => 'month',
    ]);

    foreach (['A-1', 'A-2'] as $unitNumber) {
        $tenant = User::factory()->tenant()->create([
            'organization_id' => $organization->id,
        ]);
        $property = Property::factory()->for($organization)->for($building)->create([
            'name' => $unitNumber,
        ]);

        PropertyAssignment::factory()->for($organization)->for($property)->for($tenant, 'tenant')->create([
            'unit_area_sqm' => 50,
        ]);

        ServiceConfiguration::factory()->for($organization)->for($property)->for($utilityService)->for($provider)->for($tariff)->create([
            'pricing_model' => PricingModel::FLAT,
            'distribution_method' => DistributionMethod::EQUAL,
            'rate_schedule' => [
                'unit_rate' => 25.00,
            ],
            'is_shared_service' => false,
        ]);
    }

    $result = app(BillingServiceInterface::class)->generateBulkInvoices($organization, [
        'billing_period_start' => now()->startOfMonth()->toDateString(),
        'billing_period_end' => now()->endOfMonth()->toDateString(),
        'due_date' => now()->addDays(14)->toDateString(),
    ], $admin);

    expect($result['created'])->toHaveCount(2)
        ->and($result['skipped'])->toBe([])
        ->and(Invoice::query()->count())->toBe(2)
        ->and(Invoice::query()->where('organization_id', $organization->id)->where('total_amount', '25.00')->count())->toBe(2);
});

it('applies a payment, updates invoice status, and records a payment entry', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);
    $invoice = Invoice::factory()->for($organization)->for($property)->for($tenant, 'tenant')->create([
        'status' => InvoiceStatus::FINALIZED,
        'total_amount' => '100.00',
        'amount_paid' => '0.00',
        'paid_amount' => '0.00',
        'items' => [
            ['description' => 'Water usage', 'amount' => 100.00],
        ],
    ]);

    $paidInvoice = app(BillingServiceInterface::class)->applyPayment($invoice, [
        'amount_paid' => '100.00',
        'payment_reference' => 'PAY-100',
        'paid_at' => now()->toDateTimeString(),
    ], $admin);

    expect($paidInvoice->status)->toBe(InvoiceStatus::PAID)
        ->and($paidInvoice->amount_paid)->toBe('100.00')
        ->and($paidInvoice->payment_reference)->toBe('PAY-100')
        ->and(InvoicePayment::query()->where('invoice_id', $invoice->id)->count())->toBe(1)
        ->and(InvoicePayment::query()->where('invoice_id', $invoice->id)->firstOrFail()->amount)->toBe('100.00')
        ->and(InvoicePayment::query()->where('invoice_id', $invoice->id)->firstOrFail()->reference)->toBe('PAY-100');
});

it('rounds fractional payment amounts through the shared money policy', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);
    $invoice = Invoice::factory()->for($organization)->for($property)->for($tenant, 'tenant')->create([
        'status' => InvoiceStatus::FINALIZED,
        'total_amount' => '20.00',
        'amount_paid' => '0.00',
        'paid_amount' => '0.00',
        'items' => [
            ['description' => 'Water usage', 'amount' => 20.00],
        ],
    ]);

    $paidInvoice = app(BillingServiceInterface::class)->applyPayment($invoice, [
        'amount_paid' => '10.005',
        'payment_reference' => 'PAY-ROUND',
        'paid_at' => now()->toDateTimeString(),
    ], $admin);

    expect($paidInvoice->status)->toBe(InvoiceStatus::PARTIALLY_PAID)
        ->and($paidInvoice->amount_paid)->toBe('10.01')
        ->and($paidInvoice->paid_amount)->toBe('10.01')
        ->and(InvoicePayment::query()->where('invoice_id', $invoice->id)->firstOrFail()->amount)->toBe('10.01');
});

it('normalizes draft line item amounts with exact two-decimal precision', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);
    $invoice = Invoice::factory()->for($organization)->for($property)->for($tenant, 'tenant')->create([
        'status' => InvoiceStatus::DRAFT,
        'total_amount' => '0.00',
        'amount_paid' => '0.00',
        'paid_amount' => '0.00',
        'finalized_at' => null,
        'items' => [],
    ]);

    $this->actingAs($admin);

    $draft = app(BillingServiceInterface::class)->saveDraft($invoice, [
        'items' => [
            [
                'description' => 'Shared heating adjustment',
                'quantity' => '1',
                'unit_price' => '10.005',
                'amount' => '10.005',
            ],
        ],
    ]);

    expect($draft->total_amount)->toBe('10.01')
        ->and($draft->items)->toBeArray()
        ->and((string) $draft->items[0]['amount'])->toBe('10.01')
        ->and($draft->invoiceItems()->firstOrFail()->total)->toBe('10.01');
});

it('rounds monetary calculations to two decimal places without floating point drift', function () {
    $billingService = app(BillingServiceInterface::class);

    expect($billingService->calculateFlatRateCharge('3', '0.10'))
        ->toBe('0.30')
        ->and($billingService->calculateFlatRateCharge('1', '10.005'))
        ->toBe('10.01')
        ->and($billingService->calculateTimeOfUseCharge([
            'day' => '1',
            'night' => '2',
        ], [
            ['id' => 'day', 'rate' => '0.10'],
            ['id' => 'night', 'rate' => '0.10'],
        ]))
        ->toBe('0.30');
});
