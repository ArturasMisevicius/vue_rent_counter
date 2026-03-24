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
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('keeps preview and generation aligned for selected candidates and skipped assignments', function (): void {
    [
        'organization' => $organization,
        'admin' => $admin,
        'selected_assignment_key' => $selectedAssignmentKey,
        'skipped_assignment_key' => $skippedAssignmentKey,
        'unselected_property_id' => $unselectedPropertyId,
        'billing_period_start' => $billingPeriodStart,
        'billing_period_end' => $billingPeriodEnd,
        'due_date' => $dueDate,
    ] = seedBillingPreviewFinalizationParityWorkspace();

    $attributes = [
        'billing_period_start' => $billingPeriodStart,
        'billing_period_end' => $billingPeriodEnd,
        'due_date' => $dueDate,
        'selected_assignments' => [
            $selectedAssignmentKey,
            $skippedAssignmentKey,
        ],
    ];

    $preview = app(BulkInvoicePreviewBuilder::class)->handle($organization, $attributes);

    expect(Invoice::query()->forOrganization($organization->id)->count())->toBe(1)
        ->and($preview['valid'])->toHaveCount(1)
        ->and(collect($preview['valid'])->pluck('assignment_key')->all())->toBe([$selectedAssignmentKey])
        ->and($preview['skipped'])->toHaveCount(1)
        ->and(collect($preview['skipped'])->pluck('assignment_key')->all())->toBe([$skippedAssignmentKey]);

    $previewCandidate = $preview['valid'][0];
    $result = app(GenerateBulkInvoicesAction::class)->handle($organization, $attributes, $admin);

    /** @var Invoice $generatedInvoice */
    $generatedInvoice = $result['created']->sole();

    expect($generatedInvoice->property_id.':'.$generatedInvoice->tenant_user_id)->toBe($selectedAssignmentKey)
        ->and((string) $generatedInvoice->total_amount)->toBe((string) $previewCandidate['total'])
        ->and($generatedInvoice->due_date?->toDateString())->toBe($dueDate)
        ->and(count($generatedInvoice->items))->toBe(count($previewCandidate['items']))
        ->and($generatedInvoice->items[0]['description'] ?? null)->toBe($previewCandidate['items'][0]['description'] ?? null)
        ->and((string) ($generatedInvoice->items[0]['total'] ?? ''))->toBe((string) ($previewCandidate['items'][0]['total'] ?? ''))
        ->and(collect($result['skipped'])->pluck('assignment_key')->all())->toBe([$skippedAssignmentKey])
        ->and(Invoice::query()->forOrganization($organization->id)->count())->toBe(2)
        ->and(Invoice::query()
            ->forOrganization($organization->id)
            ->where('property_id', $unselectedPropertyId)
            ->exists())->toBeFalse();
});

function seedBillingPreviewFinalizationParityWorkspace(): array
{
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

    $assignments = collect([
        'skipped' => createPreviewParityAssignment($organization, $building, $provider, $tariff, $utilityService, 'A-1'),
        'selected' => createPreviewParityAssignment($organization, $building, $provider, $tariff, $utilityService, 'A-2'),
        'unselected' => createPreviewParityAssignment($organization, $building, $provider, $tariff, $utilityService, 'A-3'),
    ]);

    /** @var PropertyAssignment $skippedAssignment */
    $skippedAssignment = $assignments['skipped']['assignment'];

    Invoice::factory()
        ->for($organization)
        ->for($assignments['skipped']['property'])
        ->for($assignments['skipped']['tenant'], 'tenant')
        ->create([
            'billing_period_start' => $billingPeriodStart,
            'billing_period_end' => $billingPeriodEnd,
            'due_date' => $dueDate,
        ]);

    return [
        'organization' => $organization,
        'admin' => $admin,
        'selected_assignment_key' => previewParityAssignmentKey(
            $assignments['selected']['property']->id,
            $assignments['selected']['tenant']->id,
        ),
        'skipped_assignment_key' => previewParityAssignmentKey(
            $assignments['skipped']['property']->id,
            $assignments['skipped']['tenant']->id,
        ),
        'unselected_property_id' => $assignments['unselected']['property']->id,
        'billing_period_start' => $billingPeriodStart,
        'billing_period_end' => $billingPeriodEnd,
        'due_date' => $dueDate,
    ];
}

/**
 * @return array{assignment: PropertyAssignment, property: Property, tenant: User}
 */
function createPreviewParityAssignment(
    Organization $organization,
    Building $building,
    Provider $provider,
    Tariff $tariff,
    UtilityService $utilityService,
    string $propertyName,
): array {
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
        'name' => 'Tenant '.$propertyName,
        'email' => strtolower($propertyName).'@example.test',
    ]);
    $property = Property::factory()->for($organization)->for($building)->create([
        'name' => $propertyName,
    ]);
    $assignment = PropertyAssignment::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create();

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

    return [
        'assignment' => $assignment,
        'property' => $property,
        'tenant' => $tenant,
    ];
}

function previewParityAssignmentKey(int $propertyId, int $tenantId): string
{
    return $propertyId.':'.$tenantId;
}
