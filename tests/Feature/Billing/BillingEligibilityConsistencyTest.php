<?php

declare(strict_types=1);

use App\Enums\DistributionMethod;
use App\Enums\PricingModel;
use App\Enums\ServiceType;
use App\Filament\Actions\Admin\Invoices\GenerateBulkInvoicesAction;
use App\Filament\Support\Admin\Invoices\BulkInvoicePreviewBuilder;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\Provider;
use App\Models\ServiceConfiguration;
use App\Models\Tariff;
use App\Models\User;
use App\Models\UtilityService;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('applies the same assignment eligibility window to preview and generation', function (): void {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);
    $building = Building::factory()->for($organization)->create();
    $provider = Provider::factory()->for($organization)->create([
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
        'name' => 'Water',
        'unit_of_measurement' => 'month',
        'default_pricing_model' => PricingModel::FLAT,
        'service_type_bridge' => ServiceType::WATER,
    ]);

    $billingPeriodStart = now()->startOfMonth()->toDateString();
    $billingPeriodEnd = now()->endOfMonth()->toDateString();
    $dueDate = now()->endOfMonth()->addDays(14)->toDateString();

    $eligible = createBillingEligibilityAssignment(
        $organization,
        $building,
        $provider,
        $tariff,
        $utilityService,
        'A-1',
        now()->startOfMonth()->subMonth(),
        null,
    );

    createBillingEligibilityAssignment(
        $organization,
        $building,
        $provider,
        $tariff,
        $utilityService,
        'A-2',
        now()->addMonth(),
        null,
    );

    createBillingEligibilityAssignment(
        $organization,
        $building,
        $provider,
        $tariff,
        $utilityService,
        'A-3',
        now()->subMonths(3),
        now()->startOfMonth()->subDay(),
    );

    $attributes = [
        'billing_period_start' => $billingPeriodStart,
        'billing_period_end' => $billingPeriodEnd,
        'due_date' => $dueDate,
    ];

    $preview = app(BulkInvoicePreviewBuilder::class)->handle($organization, $attributes);
    $result = app(GenerateBulkInvoicesAction::class)->handle($organization, $attributes, $admin);

    $expectedAssignmentKey = $eligible->property_id.':'.$eligible->tenant_user_id;

    expect(collect($preview['valid'])->pluck('assignment_key')->all())->toBe([$expectedAssignmentKey])
        ->and($preview['skipped'])->toBe([])
        ->and($result['created'])->toHaveCount(1)
        ->and($result['skipped'])->toBe([])
        ->and($result['created']->sole()->property_id.':'.$result['created']->sole()->tenant_user_id)
        ->toBe($expectedAssignmentKey)
        ->and(Invoice::query()->forOrganization($organization->id)->count())->toBe(1);
});

function createBillingEligibilityAssignment(
    Organization $organization,
    Building $building,
    Provider $provider,
    Tariff $tariff,
    UtilityService $utilityService,
    string $propertyName,
    CarbonInterface $assignedAt,
    ?CarbonInterface $unassignedAt,
): PropertyAssignment {
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
        'name' => 'Tenant '.$propertyName,
        'email' => strtolower($propertyName).'@example.test',
    ]);
    $property = Property::factory()->for($organization)->for($building)->create([
        'name' => $propertyName,
    ]);

    ServiceConfiguration::factory()
        ->for($organization)
        ->for($property)
        ->for($utilityService)
        ->for($provider)
        ->for($tariff)
        ->create([
            'pricing_model' => PricingModel::FLAT,
            'distribution_method' => DistributionMethod::EQUAL,
            'rate_schedule' => [
                'unit_rate' => 25.00,
            ],
            'is_shared_service' => false,
        ]);

    return PropertyAssignment::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'assigned_at' => $assignedAt,
            'unassigned_at' => $unassignedAt,
        ]);
}
