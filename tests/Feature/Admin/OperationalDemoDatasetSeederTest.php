<?php

use App\Enums\SubscriptionPlan;
use App\Filament\Support\Geography\BalticReferenceCatalog;
use App\Models\BillingRecord;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Lease;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\OrganizationSetting;
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
    $seededPlans = Subscription::query()
        ->whereIn('organization_id', $demoOrganizationIds)
        ->pluck('plan')
        ->map(fn ($plan) => $plan instanceof SubscriptionPlan ? $plan->value : (string) $plan)
        ->unique()
        ->sort()
        ->values()
        ->all();
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

    expect($demoOrganizations)->toHaveCount(5)
        ->and($demoOrganizations->pluck('slug')->sort()->values()->all())->toEqual([
            'demo-baltic-basic',
            'demo-baltic-custom',
            'demo-baltic-enterprise',
            'demo-baltic-professional',
            'demo-baltic-starter',
        ])
        ->and($demoUserCount)->toBeGreaterThanOrEqual(40)
        ->and($subscriptionCount)->toBe(5)
        ->and($seededPlans)->toEqual([
            SubscriptionPlan::BASIC->value,
            SubscriptionPlan::CUSTOM->value,
            SubscriptionPlan::ENTERPRISE->value,
            SubscriptionPlan::PROFESSIONAL->value,
            SubscriptionPlan::STARTER->value,
        ])
        ->and($buildingCount)->toBeGreaterThanOrEqual(5)
        ->and($propertyCount)->toBeGreaterThanOrEqual(20)
        ->and($assignmentCount)->toBe($propertyCount)
        ->and($meterCount)->toBeGreaterThanOrEqual($propertyCount * 2)
        ->and($readingCount)->toBeGreaterThanOrEqual($meterCount * 12)
        ->and($invoiceCount)->toBeGreaterThanOrEqual($propertyCount * 3)
        ->and($invoiceItemCount)->toBeGreaterThanOrEqual($invoiceCount * 3)
        ->and($billingRecordCount)->toBeGreaterThanOrEqual($invoiceCount * 3)
        ->and($leaseCount)->toBe($assignmentCount)
        ->and($providerCount)->toBeGreaterThanOrEqual(15)
        ->and($tariffCount)->toBeGreaterThanOrEqual(15)
        ->and($serviceConfigurationCount)->toBeGreaterThanOrEqual($propertyCount * 3)
        ->and($utilityServiceCount)->toBeGreaterThanOrEqual(15)
        ->and($projectCount)->toBe(5)
        ->and($demoTasks->count())->toBeGreaterThanOrEqual(5)
        ->and($taskAssignmentCount)->toBe($demoTasks->count())
        ->and($timeEntryCount)->toBe($demoTasks->count())
        ->and($datasetTotal)->toBeGreaterThanOrEqual(1000)
        ->and(User::query()->where('email', 'like', '%@tenanto-demo.test')->pluck('locale')->unique()->sort()->values()->all())->toEqual(['en', 'lt', 'ru']);

    $starterOrganization = $demoOrganizations->firstWhere('slug', 'demo-baltic-starter');
    $basicOrganization = $demoOrganizations->firstWhere('slug', 'demo-baltic-basic');
    $enterpriseOrganization = $demoOrganizations->firstWhere('slug', 'demo-baltic-enterprise');
    $customOrganization = $demoOrganizations->firstWhere('slug', 'demo-baltic-custom');

    expect($enterpriseOrganization?->buildings()->count())->toBeGreaterThan($starterOrganization?->buildings()->count() ?? 0)
        ->and($customOrganization?->properties()->count())->toBeGreaterThan($basicOrganization?->properties()->count() ?? 0)
        ->and($customOrganization?->users()->count())->toBeGreaterThan($starterOrganization?->users()->count() ?? 0);

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

it('reruns the database seeder without duplicating showcase organizations or subscriptions', function () {
    Artisan::call('db:seed', [
        '--class' => DatabaseSeeder::class,
        '--no-interaction' => true,
    ]);

    $firstShowcaseOrganizationIds = Organization::query()
        ->where('slug', 'like', 'demo-baltic-%')
        ->pluck('id')
        ->all();

    $firstSnapshot = [
        'showcase_organizations' => count($firstShowcaseOrganizationIds),
        'showcase_subscriptions' => Subscription::query()->whereIn('organization_id', $firstShowcaseOrganizationIds)->count(),
        'showcase_settings' => OrganizationSetting::query()->whereIn('organization_id', $firstShowcaseOrganizationIds)->count(),
        'login_organization_count' => Organization::query()->where('slug', 'tenanto-demo-organization')->count(),
        'login_subscription_count' => Subscription::query()
            ->where('organization_id', Organization::query()->where('slug', 'tenanto-demo-organization')->value('id'))
            ->count(),
    ];

    Artisan::call('db:seed', [
        '--class' => DatabaseSeeder::class,
        '--no-interaction' => true,
    ]);

    $showcaseOrganizations = Organization::query()
        ->where('slug', 'like', 'demo-baltic-%')
        ->orderBy('slug')
        ->get(['id', 'slug']);

    expect($showcaseOrganizations)->toHaveCount($firstSnapshot['showcase_organizations'])
        ->and($showcaseOrganizations->pluck('slug')->duplicates()->all())->toBeEmpty()
        ->and(Subscription::query()->whereIn('organization_id', $showcaseOrganizations->modelKeys())->count())->toBe($firstSnapshot['showcase_subscriptions'])
        ->and(OrganizationSetting::query()->whereIn('organization_id', $showcaseOrganizations->modelKeys())->count())->toBe($firstSnapshot['showcase_settings'])
        ->and(Organization::query()->where('slug', 'tenanto-demo-organization')->count())->toBe($firstSnapshot['login_organization_count'])
        ->and(Subscription::query()
            ->where('organization_id', Organization::query()->where('slug', 'tenanto-demo-organization')->value('id'))
            ->count())->toBe($firstSnapshot['login_subscription_count']);
});
