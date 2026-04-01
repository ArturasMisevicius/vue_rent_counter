<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Models\CostRecord;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\OrganizationSetting;
use App\Models\Project;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use App\Notifications\Projects\ProjectOverBudgetNotification;
use App\Notifications\Projects\ProjectOverdueAlertNotification;
use App\Notifications\Projects\ProjectStalledAlertNotification;
use App\Notifications\Projects\ProjectUnapprovedEscalationNotification;
use App\Notifications\Projects\ProjectUnapprovedReminderNotification;
use App\Services\ProjectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\artisan;

uses(RefreshDatabase::class);

it('recalculates actual cost from time entries and direct cost records', function (): void {
    Notification::fake();

    $fixture = projectCostFixture();

    CostRecord::factory()->create([
        'organization_id' => $fixture['organization']->id,
        'project_id' => $fixture['project']->id,
        'amount' => 80.00,
    ]);

    TimeEntry::factory()->create([
        'user_id' => $fixture['manager']->id,
        'task_id' => $fixture['task']->id,
        'organization_id' => $fixture['organization']->id,
        'project_id' => $fixture['project']->id,
        'hours' => 2.0,
        'cost_amount' => 120.00,
    ]);

    app(ProjectService::class)->recalculateActualCost($fixture['project']->fresh());

    expect($fixture['project']->fresh()->actual_cost)->toBe('200.00');

    Notification::assertSentTo($fixture['admin'], ProjectOverBudgetNotification::class);
});

it('recalculates completion percentage from completed task ratio when automatic mode is enabled', function (): void {
    $fixture = projectCostFixture(completionMode: 'automatic_from_tasks');
    $fixture['task']->delete();

    Task::factory()->count(2)->for($fixture['organization'])->for($fixture['project'])->create([
        'status' => 'completed',
        'completed_at' => now(),
    ]);

    Task::factory()->count(2)->for($fixture['organization'])->for($fixture['project'])->create([
        'status' => 'pending',
        'completed_at' => null,
    ]);

    app(ProjectService::class)->recalculateCompletion($fixture['project']->fresh());

    expect($fixture['project']->fresh()->completion_percentage)->toBe(50);
});

it('creates draft invoice items for active tenants when cost passthrough is generated', function (): void {
    $fixture = projectCostFixture(status: ProjectStatus::COMPLETED);

    $invoice = Invoice::factory()->create([
        'organization_id' => $fixture['organization']->id,
        'property_id' => $fixture['property']->id,
        'tenant_user_id' => $fixture['tenant']->id,
        'status' => InvoiceStatus::DRAFT,
        'billing_period_start' => now()->startOfMonth()->toDateString(),
        'billing_period_end' => now()->endOfMonth()->toDateString(),
    ]);

    $items = app(ProjectService::class)->generateCostPassthrough(
        $fixture['project']->fresh()->forceFill([
            'actual_cost' => 240,
            'cost_passed_to_tenant' => true,
        ]),
        $fixture['admin']->fresh(),
    );

    expect($items)->toHaveCount(1)
        ->and($invoice->invoiceItems()->count())->toBe(1)
        ->and($invoice->invoiceItems()->first()?->project_id)->toBe($fixture['project']->id)
        ->and($invoice->invoiceItems()->first()?->description)->toContain($fixture['project']->name);
});

it('alerts only on hold projects whose recorded hold reason has gone stale when the stalled command runs', function (): void {
    Notification::fake();

    $stalled = projectCostFixture(status: ProjectStatus::ON_HOLD);
    $recent = projectCostFixture(status: ProjectStatus::ON_HOLD);

    $stalled['project']->forceFill([
        'metadata' => [
            'on_hold_reason' => 'Waiting for contractor availability',
            'on_hold_reason_updated_at' => now()->subDays(31)->toDateTimeString(),
        ],
        'updated_at' => now()->subDays(5),
    ])->saveQuietly();

    $recent['project']->forceFill([
        'metadata' => [
            'on_hold_reason' => 'Weather dependency',
            'on_hold_reason_updated_at' => now()->subDays(5)->toDateTimeString(),
        ],
        'updated_at' => now()->subDays(40),
    ])->saveQuietly();

    artisan('projects:alert-stalled')->assertSuccessful();

    Notification::assertSentTo($stalled['manager'], ProjectStalledAlertNotification::class);
    Notification::assertSentTo($stalled['admin'], ProjectStalledAlertNotification::class);
    Notification::assertNotSentTo($recent['manager'], ProjectStalledAlertNotification::class);
});

it('does not alert projects whose hold reason was updated exactly 30 days ago', function (): void {
    Notification::fake();

    Carbon::setTestNow(now()->startOfSecond());

    $boundary = projectCostFixture(status: ProjectStatus::ON_HOLD);

    $boundary['project']->forceFill([
        'metadata' => [
            'on_hold_reason' => 'Waiting for contractor availability',
            'on_hold_reason_updated_at' => now()->subDays(30)->toDateTimeString(),
        ],
    ])->saveQuietly();

    artisan('projects:alert-stalled')->assertSuccessful();

    Notification::assertNotSentTo($boundary['manager'], ProjectStalledAlertNotification::class);

    Carbon::setTestNow();
});

it('alerts overdue projects when the overdue command runs', function (): void {
    Notification::fake();

    $overdue = projectCostFixture(status: ProjectStatus::IN_PROGRESS);
    $healthy = projectCostFixture(status: ProjectStatus::IN_PROGRESS);

    $overdue['project']->update([
        'estimated_end_date' => now()->subDay()->toDateString(),
    ]);

    $healthy['project']->update([
        'estimated_end_date' => now()->addWeek()->toDateString(),
    ]);

    artisan('projects:alert-overdue')->assertSuccessful();

    Notification::assertSentTo($overdue['manager'], ProjectOverdueAlertNotification::class);
    Notification::assertSentTo($overdue['admin'], ProjectOverdueAlertNotification::class);
    Notification::assertNotSentTo($healthy['manager'], ProjectOverdueAlertNotification::class);
});

it('reminds approvers and escalates long unapproved projects', function (): void {
    Notification::fake();

    $fourteenDay = projectCostFixture(status: ProjectStatus::PLANNED, requiresApproval: true);
    $thirtyDay = projectCostFixture(status: ProjectStatus::PLANNED, requiresApproval: true);
    $superadmin = User::factory()->superadmin()->create();

    $fourteenDay['project']->forceFill(['created_at' => now()->subDays(15)])->saveQuietly();
    $thirtyDay['project']->forceFill(['created_at' => now()->subDays(31)])->saveQuietly();

    artisan('projects:alert-unapproved')->assertSuccessful();

    Notification::assertSentTo($fourteenDay['admin'], ProjectUnapprovedReminderNotification::class);
    Notification::assertSentTo($thirtyDay['admin'], ProjectUnapprovedReminderNotification::class);
    Notification::assertSentTo($superadmin, ProjectUnapprovedEscalationNotification::class);
});

/**
 * @return array{organization: Organization, admin: User, manager: User, tenant: User, property: Property, project: Project, task: Task}
 */
function projectCostFixture(
    ProjectStatus $status = ProjectStatus::IN_PROGRESS,
    string $completionMode = 'manual',
    bool $requiresApproval = false,
): array {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create(['organization_id' => $organization->id]);
    $manager = User::factory()->manager()->create(['organization_id' => $organization->id]);
    $tenant = User::factory()->tenant()->create(['organization_id' => $organization->id]);

    $organization->forceFill(['owner_user_id' => $admin->id])->save();

    OrganizationSetting::factory()->for($organization)->create([
        'project_reference_prefix' => 'PROJ-',
        'project_reference_sequence' => 0,
        'project_completion_mode' => $completionMode,
        'project_budget_alert_threshold_percent' => 10,
        'project_schedule_alert_threshold_days' => 30,
    ]);

    $property = Property::factory()->for($organization)->create();

    PropertyAssignment::factory()->create([
        'organization_id' => $organization->id,
        'property_id' => $property->id,
        'tenant_user_id' => $tenant->id,
        'assigned_at' => now()->subMonth(),
        'unassigned_at' => null,
    ]);

    $project = Project::factory()->for($organization)->create([
        'property_id' => $property->id,
        'manager_id' => $manager->id,
        'status' => $status === ProjectStatus::COMPLETED ? ProjectStatus::IN_PROGRESS->value : $status->value,
        'type' => ProjectType::RENOVATION->value,
        'priority' => ProjectPriority::HIGH->value,
        'budget_amount' => 150.00,
        'actual_cost' => 0,
        'cost_passed_to_tenant' => false,
        'requires_approval' => $requiresApproval,
        'completed_at' => null,
        'actual_end_date' => null,
        'metadata' => $status === ProjectStatus::ON_HOLD
            ? [
                'on_hold_reason' => 'Waiting for contractor availability',
                'on_hold_started_at' => now()->subDay()->toDateTimeString(),
                'on_hold_reason_updated_at' => now()->subDay()->toDateTimeString(),
            ]
            : null,
    ]);

    $project->teamMembers()->sync([
        $manager->id => [
            'role' => 'manager',
            'invited_at' => now(),
            'invited_by' => $admin->id,
        ],
    ]);

    $task = Task::factory()->for($organization)->for($project)->create([
        'status' => 'in_progress',
        'created_by_user_id' => $admin->id,
    ]);

    if ($status === ProjectStatus::COMPLETED) {
        $project->forceFill([
            'status' => ProjectStatus::COMPLETED,
            'completed_at' => now(),
            'actual_end_date' => today(),
            'completion_percentage' => 100,
        ])->saveQuietly();
    }

    return compact('organization', 'admin', 'manager', 'tenant', 'property', 'project', 'task');
}
