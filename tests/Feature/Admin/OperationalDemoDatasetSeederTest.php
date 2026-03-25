<?php

use App\Filament\Support\Geography\BalticReferenceCatalog;
use App\Models\BillingRecord;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Lease;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\Provider;
use App\Models\ServiceConfiguration;
use App\Models\Subscription;
use App\Models\Tariff;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\TimeEntry;
use App\Models\User;
use App\Models\UtilityService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

it('seeds a 1000 plus logical baltic demo dataset without breaking organization or geography consistency', function () {
    Artisan::call('db:seed', [
        '--class' => DatabaseSeeder::class,
        '--no-interaction' => true,
    ]);

    $demoOrganizations = Organization::query()
        ->where('slug', 'like', 'demo-baltic-%')
        ->get();

    $demoOrganizationIds = $demoOrganizations->modelKeys();
    $demoTasks = Task::query()->whereIn('organization_id', $demoOrganizationIds)->get();
    $demoTaskIds = $demoTasks->modelKeys();

    $demoUserCount = User::query()
        ->where('email', 'like', '%@tenanto-demo.test')
        ->count();

    $buildingCount = Building::query()->whereIn('organization_id', $demoOrganizationIds)->count();
    $propertyCount = Property::query()->whereIn('organization_id', $demoOrganizationIds)->count();
    $assignmentCount = PropertyAssignment::query()->whereIn('organization_id', $demoOrganizationIds)->count();
    $meterCount = Meter::query()->whereIn('organization_id', $demoOrganizationIds)->count();
    $readingCount = MeterReading::query()->whereIn('organization_id', $demoOrganizationIds)->count();
    $invoiceCount = Invoice::query()->whereIn('organization_id', $demoOrganizationIds)->count();
    $invoiceItemCount = InvoiceItem::query()->whereHas('invoice', fn ($query) => $query->whereIn('organization_id', $demoOrganizationIds))->count();
    $billingRecordCount = BillingRecord::query()->whereIn('organization_id', $demoOrganizationIds)->count();
    $leaseCount = Lease::query()->whereIn('organization_id', $demoOrganizationIds)->count();
    $providerCount = Provider::query()->whereIn('organization_id', $demoOrganizationIds)->count();
    $tariffCount = Tariff::query()
        ->whereHas('provider', fn ($query) => $query->whereIn('organization_id', $demoOrganizationIds))
        ->count();
    $serviceConfigurationCount = ServiceConfiguration::query()->whereIn('organization_id', $demoOrganizationIds)->count();
    $utilityServiceCount = UtilityService::query()->whereIn('organization_id', $demoOrganizationIds)->count();
    $subscriptionCount = Subscription::query()->whereIn('organization_id', $demoOrganizationIds)->count();
    $projectCount = Project::query()->whereIn('organization_id', $demoOrganizationIds)->count();
    $taskAssignmentCount = TaskAssignment::query()->whereIn('task_id', $demoTaskIds)->count();
    $timeEntryCount = TimeEntry::query()->whereIn('task_id', $demoTaskIds)->count();
    $datasetTotal = $demoOrganizations->count()
        + $demoUserCount
        + $subscriptionCount
        + $buildingCount
        + $propertyCount
        + $assignmentCount
        + $meterCount
        + $readingCount
        + $invoiceCount
        + $invoiceItemCount
        + $billingRecordCount
        + $leaseCount
        + $providerCount
        + $tariffCount
        + $serviceConfigurationCount
        + $utilityServiceCount
        + $projectCount
        + $demoTasks->count()
        + $taskAssignmentCount
        + $timeEntryCount;

    expect($demoOrganizations)->toHaveCount(10)
        ->and($demoUserCount)->toBe(101)
        ->and($subscriptionCount)->toBe(10)
        ->and($buildingCount)->toBe(30)
        ->and($propertyCount)->toBe(80)
        ->and($assignmentCount)->toBe(80)
        ->and($meterCount)->toBe(160)
        ->and($readingCount)->toBe(1920)
        ->and($invoiceCount)->toBe(240)
        ->and($invoiceItemCount)->toBe(720)
        ->and($billingRecordCount)->toBe(720)
        ->and($leaseCount)->toBe(80)
        ->and($providerCount)->toBe(30)
        ->and($tariffCount)->toBe(30)
        ->and($serviceConfigurationCount)->toBe(240)
        ->and($utilityServiceCount)->toBe(30)
        ->and($projectCount)->toBe(10)
        ->and($demoTasks->count())->toBe(20)
        ->and($taskAssignmentCount)->toBe(20)
        ->and($timeEntryCount)->toBe(20)
        ->and($datasetTotal)->toBeGreaterThanOrEqual(1000)
        ->and(User::query()->where('email', 'like', '%@tenanto-demo.test')->pluck('locale')->unique()->sort()->values()->all())->toEqual(['en', 'lt', 'ru']);

    $firstDemoMeter = Meter::query()
        ->whereIn('organization_id', $demoOrganizationIds)
        ->firstOrFail();

    expect(MeterReading::query()->forMeter($firstDemoMeter->id)->count())->toBe(12);

    $cityMap = collect(BalticReferenceCatalog::cities())
        ->mapWithKeys(fn (array $city): array => [$city['name'] => $city['country_code']]);

    Building::query()
        ->whereIn('organization_id', $demoOrganizationIds)
        ->get()
        ->each(function (Building $building) use ($cityMap): void {
            expect($cityMap->has($building->city))->toBeTrue()
                ->and($cityMap->get($building->city))->toBe($building->country_code);
        });

    Property::query()
        ->whereIn('organization_id', $demoOrganizationIds)
        ->with(['building:id,organization_id', 'currentAssignment:id,organization_id,property_id,tenant_user_id,unassigned_at', 'currentAssignment.tenant:id,organization_id'])
        ->get()
        ->each(function (Property $property): void {
            expect($property->building?->organization_id)->toBe($property->organization_id)
                ->and($property->currentAssignment?->organization_id)->toBe($property->organization_id)
                ->and($property->currentAssignment?->tenant?->organization_id)->toBe($property->organization_id);
        });

    ServiceConfiguration::query()
        ->whereIn('organization_id', $demoOrganizationIds)
        ->with(['provider:id,organization_id,service_type', 'tariff:id,provider_id'])
        ->get()
        ->each(function (ServiceConfiguration $serviceConfiguration): void {
            expect($serviceConfiguration->provider_id)->not->toBeNull()
                ->and($serviceConfiguration->tariff_id)->not->toBeNull()
                ->and($serviceConfiguration->provider?->organization_id)->toBe($serviceConfiguration->organization_id)
                ->and($serviceConfiguration->tariff?->provider_id)->toBe($serviceConfiguration->provider_id)
                ->and($serviceConfiguration->configuration_overrides)->not->toBeNull();
        });

    expect(ServiceConfiguration::query()->whereIn('organization_id', $demoOrganizationIds)->whereNotNull('effective_until')->exists())->toBeTrue()
        ->and(ServiceConfiguration::query()->whereIn('organization_id', $demoOrganizationIds)->whereNotNull('area_type')->exists())->toBeTrue()
        ->and(ServiceConfiguration::query()->whereIn('organization_id', $demoOrganizationIds)->whereNotNull('custom_formula')->exists())->toBeTrue();
});
