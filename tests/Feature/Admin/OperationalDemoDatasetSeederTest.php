<?php

use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\PlatformNotificationRecipient;
use App\Models\Project;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\Subscription;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\TimeEntry;
use App\Models\User;
use App\Support\Geography\BalticReferenceCatalog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds a 1000 plus logical baltic demo dataset without breaking organization or geography consistency', function () {
    $this->seed(DatabaseSeeder::class);

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
    $subscriptionCount = Subscription::query()->whereIn('organization_id', $demoOrganizationIds)->count();
    $projectCount = Project::query()->whereIn('organization_id', $demoOrganizationIds)->count();
    $taskAssignmentCount = TaskAssignment::query()->whereIn('task_id', $demoTaskIds)->count();
    $timeEntryCount = TimeEntry::query()->whereIn('task_id', $demoTaskIds)->count();
    $recipientCount = PlatformNotificationRecipient::query()->whereIn('organization_id', $demoOrganizationIds)->count();

    $datasetTotal = $demoOrganizations->count()
        + $demoUserCount
        + $subscriptionCount
        + $buildingCount
        + $propertyCount
        + $assignmentCount
        + $meterCount
        + $readingCount
        + $invoiceCount
        + $projectCount
        + $demoTasks->count()
        + $taskAssignmentCount
        + $timeEntryCount
        + $recipientCount;

    expect($demoOrganizations)->toHaveCount(10)
        ->and($demoUserCount)->toBe(101)
        ->and($subscriptionCount)->toBe(10)
        ->and($buildingCount)->toBe(30)
        ->and($propertyCount)->toBe(80)
        ->and($assignmentCount)->toBe(80)
        ->and($meterCount)->toBe(160)
        ->and($readingCount)->toBe(480)
        ->and($invoiceCount)->toBe(80)
        ->and($projectCount)->toBe(10)
        ->and($demoTasks->count())->toBe(20)
        ->and($taskAssignmentCount)->toBe(20)
        ->and($timeEntryCount)->toBe(20)
        ->and($recipientCount)->toBe(10)
        ->and($datasetTotal)->toBeGreaterThanOrEqual(1000)
        ->and(User::query()->where('email', 'like', '%@tenanto-demo.test')->pluck('locale')->unique()->sort()->values()->all())->toEqual(['en', 'lt', 'ru']);

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
});
