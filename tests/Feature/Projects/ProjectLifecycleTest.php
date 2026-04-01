<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Exceptions\InvalidProjectTransitionException;
use App\Exceptions\ProjectApprovalRequiredException;
use App\Exceptions\ProjectDeletionBlockedException;
use App\Jobs\Projects\RescopeProjectChildrenJob;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\OrganizationSetting;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use App\Notifications\Projects\ProjectApprovedNotification;
use App\Notifications\Projects\ProjectCancelledNotification;
use App\Notifications\Projects\ProjectCompletedNotification;
use App\Services\ProjectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('generates org scoped reference numbers with the configured prefix', function (): void {
    $organization = Organization::factory()->create();
    $actor = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    OrganizationSetting::factory()->for($organization)->create([
        'project_reference_prefix' => 'MNT-',
        'project_reference_sequence' => 0,
    ]);

    $service = app(ProjectService::class);

    $first = $service->create([
        'name' => 'Replace lobby lights',
        'type' => ProjectType::MAINTENANCE->value,
        'priority' => ProjectPriority::MEDIUM->value,
        'status' => ProjectStatus::DRAFT->value,
    ], $organization, $actor);

    $second = $service->create([
        'name' => 'Roof membrane inspection',
        'type' => ProjectType::INSPECTION->value,
        'priority' => ProjectPriority::HIGH->value,
        'status' => ProjectStatus::DRAFT->value,
    ], $organization, $actor);

    expect($first->reference_number)->toBe('MNT-0001')
        ->and($second->reference_number)->toBe('MNT-0002');
});

it('rejects mismatched organization building and property scope on create', function (): void {
    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();
    $actor = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $foreignBuilding = Building::factory()->create([
        'organization_id' => $otherOrganization->id,
    ]);

    expect(fn () => app(ProjectService::class)->create([
        'name' => 'Foreign building repair',
        'type' => ProjectType::MAINTENANCE->value,
        'priority' => ProjectPriority::MEDIUM->value,
        'status' => ProjectStatus::DRAFT->value,
        'building_id' => $foreignBuilding->id,
    ], $organization, $actor))->toThrow(ValidationException::class);
});

it('requires newly created projects to start in draft regardless of submitted status', function (): void {
    $organization = Organization::factory()->create();
    $actor = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    expect(fn () => app(ProjectService::class)->create([
        'name' => 'Burst pipe response',
        'type' => ProjectType::EMERGENCY->value,
        'priority' => ProjectPriority::CRITICAL->value,
        'status' => ProjectStatus::IN_PROGRESS->value,
    ], $organization, $actor))->toThrow(ValidationException::class);
});

it('creates emergency projects in draft and does not stamp the start date before a transition', function (): void {
    $organization = Organization::factory()->create();
    $actor = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $project = app(ProjectService::class)->create([
        'name' => 'Burst pipe response',
        'type' => ProjectType::EMERGENCY->value,
        'priority' => ProjectPriority::CRITICAL->value,
        'status' => ProjectStatus::DRAFT->value,
    ], $organization, $actor);

    expect($project->status)->toBe(ProjectStatus::DRAFT)
        ->and($project->actual_start_date)->toBeNull();
});

it('blocks in progress transitions until approval is recorded when required', function (): void {
    $project = projectFixture(requiresApproval: true);

    expect(fn () => app(ProjectService::class)->transitionStatus(
        $project['project']->fresh(),
        ProjectStatus::IN_PROGRESS,
        $project['manager']->fresh(),
    ))->toThrow(ProjectApprovalRequiredException::class);
});

it('approves a project and notifies the manager', function (): void {
    Notification::fake();

    $project = projectFixture(requiresApproval: true);

    $approved = app(ProjectService::class)->approve(
        $project['project']->fresh(),
        $project['admin']->fresh(),
    );

    expect($approved->approved_at)->not->toBeNull()
        ->and($approved->approved_by)->toBe($project['admin']->id);

    Notification::assertSentTo($project['manager'], ProjectApprovedNotification::class);
});

it('stamps completion metadata and forces progress to 100 when completed', function (): void {
    $project = projectFixture(status: ProjectStatus::IN_PROGRESS);

    $completed = app(ProjectService::class)->transitionStatus(
        $project['project']->fresh(),
        ProjectStatus::COMPLETED,
        $project['manager']->fresh(),
        'Work finished',
    );

    expect($completed->status)->toBe(ProjectStatus::COMPLETED)
        ->and($completed->completion_percentage)->toBe(100)
        ->and($completed->actual_end_date?->toDateString())->toBe(today()->toDateString())
        ->and($completed->completed_at)->not->toBeNull();
});

it('requires a reason when moving a project on hold and records the hold metadata', function (): void {
    $fixture = projectFixture(status: ProjectStatus::IN_PROGRESS);

    expect(fn () => app(ProjectService::class)->transitionStatus(
        $fixture['project']->fresh(),
        ProjectStatus::ON_HOLD,
        $fixture['manager']->fresh(),
    ))->toThrow(ValidationException::class);

    $onHold = app(ProjectService::class)->transitionStatus(
        $fixture['project']->fresh(),
        ProjectStatus::ON_HOLD,
        $fixture['manager']->fresh(),
        'Waiting for permit approval',
    );

    expect($onHold->status)->toBe(ProjectStatus::ON_HOLD)
        ->and(data_get($onHold->metadata, 'on_hold_reason'))->toBe('Waiting for permit approval')
        ->and(data_get($onHold->metadata, 'on_hold_reason_updated_at'))->not->toBeNull()
        ->and(data_get($onHold->metadata, 'on_hold_started_at'))->not->toBeNull();
});

it('requires acknowledgment before completing projects with critical open tasks and sends a completion summary when acknowledged', function (): void {
    Notification::fake();

    $fixture = projectFixture(status: ProjectStatus::IN_PROGRESS);

    Task::factory()->for($fixture['organization'])->for($fixture['project'])->create([
        'status' => 'in_progress',
        'priority' => 'critical',
        'completed_at' => null,
    ]);

    expect(fn () => app(ProjectService::class)->transitionStatus(
        $fixture['project']->fresh(),
        ProjectStatus::COMPLETED,
        $fixture['manager']->fresh(),
        'Closing project',
    ))->toThrow(ValidationException::class);

    $completed = app(ProjectService::class)->transitionStatus(
        $fixture['project']->fresh(),
        ProjectStatus::COMPLETED,
        $fixture['manager']->fresh(),
        'Closing project',
        acknowledgeIncompleteWork: true,
    );

    expect($completed->status)->toBe(ProjectStatus::COMPLETED)
        ->and($completed->completion_percentage)->toBe(100);

    Notification::assertSentTo($fixture['manager'], ProjectCompletedNotification::class);
    Notification::assertSentTo($fixture['admin'], ProjectCompletedNotification::class);
});

it('does not require acknowledgment before completing projects with only urgent open tasks', function (): void {
    $fixture = projectFixture(status: ProjectStatus::IN_PROGRESS);

    Task::factory()->for($fixture['organization'])->for($fixture['project'])->create([
        'status' => 'in_progress',
        'priority' => 'urgent',
        'completed_at' => null,
    ]);

    $completed = app(ProjectService::class)->transitionStatus(
        $fixture['project']->fresh(),
        ProjectStatus::COMPLETED,
        $fixture['manager']->fresh(),
        'Closing project',
    );

    expect($completed->status)->toBe(ProjectStatus::COMPLETED)
        ->and($completed->completion_percentage)->toBe(100);
});

it('requires a cancellation reason and closes open tasks when cancelled', function (): void {
    Notification::fake();

    $fixture = projectFixture(status: ProjectStatus::IN_PROGRESS);

    $task = Task::factory()->for($fixture['organization'])->for($fixture['project'])->create([
        'status' => 'pending',
        'completed_at' => null,
    ]);

    $assignedUser = User::factory()->manager()->create([
        'organization_id' => $fixture['organization']->id,
    ]);

    $fixture['project']->teamMembers()->syncWithoutDetaching([
        $assignedUser->id => [
            'role' => 'assignee',
            'invited_at' => now(),
            'invited_by' => $fixture['admin']->id,
        ],
    ]);

    TimeEntry::factory()->create([
        'organization_id' => $fixture['organization']->id,
        'project_id' => $fixture['project']->id,
        'task_id' => $task->id,
        'user_id' => $fixture['manager']->id,
        'approval_status' => 'pending',
        'rejected_at' => null,
        'rejection_reason' => null,
    ]);

    $draftInvoice = Invoice::factory()->create([
        'organization_id' => $fixture['organization']->id,
        'property_id' => $fixture['project']->property_id,
        'tenant_user_id' => User::factory()->tenant()->create([
            'organization_id' => $fixture['organization']->id,
        ])->id,
        'status' => InvoiceStatus::DRAFT,
    ]);

    $finalizedInvoice = Invoice::factory()->create([
        'organization_id' => $fixture['organization']->id,
        'property_id' => $fixture['project']->property_id,
        'tenant_user_id' => User::factory()->tenant()->create([
            'organization_id' => $fixture['organization']->id,
        ])->id,
        'status' => InvoiceStatus::FINALIZED,
    ]);

    $fixture['project']->forceFill([
        'cost_passed_to_tenant' => true,
    ])->save();

    $draftItem = $draftInvoice->invoiceItems()->create([
        'project_id' => $fixture['project']->id,
        'description' => 'Queued project recovery',
        'quantity' => 1,
        'unit' => 'project',
        'unit_price' => 50,
        'total' => 50,
        'metadata' => [
            'source' => 'project_cost_passthrough',
        ],
    ]);

    $nonPassthroughDraftItem = $draftInvoice->invoiceItems()->create([
        'project_id' => $fixture['project']->id,
        'description' => 'Manual project line item',
        'quantity' => 1,
        'unit' => 'project',
        'unit_price' => 60,
        'total' => 60,
        'metadata' => [
            'source' => 'manual_adjustment',
        ],
    ]);

    $finalizedItem = $finalizedInvoice->invoiceItems()->create([
        'project_id' => $fixture['project']->id,
        'description' => 'Already billed project recovery',
        'quantity' => 1,
        'unit' => 'project',
        'unit_price' => 75,
        'total' => 75,
    ]);

    expect(fn () => app(ProjectService::class)->transitionStatus(
        $fixture['project']->fresh(),
        ProjectStatus::CANCELLED,
        $fixture['admin']->fresh(),
    ))->toThrow(ValidationException::class);

    $cancelled = app(ProjectService::class)->transitionStatus(
        $fixture['project']->fresh(),
        ProjectStatus::CANCELLED,
        $fixture['admin']->fresh(),
        'Tenant withdrew access',
    );

    expect($cancelled->cancelled_at)->not->toBeNull()
        ->and($cancelled->cancellation_reason)->toBe('Tenant withdrew access')
        ->and($task->fresh()->status)->toBe('cancelled')
        ->and(TimeEntry::query()->first()?->approval_status)->toBe('rejected')
        ->and($draftItem->fresh()->voided_at)->not->toBeNull()
        ->and($nonPassthroughDraftItem->fresh()->voided_at)->toBeNull()
        ->and($finalizedItem->fresh()->voided_at)->toBeNull();

    Notification::assertSentTo($fixture['manager'], ProjectCancelledNotification::class);
    Notification::assertSentTo($assignedUser, ProjectCancelledNotification::class);
});

it('does not allow superadmins to force transitions out of terminal states', function (): void {
    $fixture = projectFixture(status: ProjectStatus::COMPLETED);
    $superadmin = User::factory()->superadmin()->create();

    expect(fn () => app(ProjectService::class)->transitionStatus(
        $fixture['project']->fresh(),
        ProjectStatus::IN_PROGRESS,
        $superadmin,
        force: true,
    ))->toThrow(InvalidProjectTransitionException::class);
});

it('allows superadmins to force non-terminal transitions that bypass the normal graph', function (): void {
    $fixture = projectFixture(status: ProjectStatus::DRAFT);
    $superadmin = User::factory()->superadmin()->create();

    $forced = app(ProjectService::class)->transitionStatus(
        $fixture['project']->fresh(),
        ProjectStatus::ON_HOLD,
        $superadmin,
        'Paused by platform ops',
        force: true,
    );

    expect($forced->status)->toBe(ProjectStatus::ON_HOLD)
        ->and(data_get($forced->metadata, 'on_hold_reason'))->toBe('Paused by platform ops');
});

it('prevents adding tasks and logging time entries for completed projects', function (): void {
    $fixture = projectFixture(status: ProjectStatus::IN_PROGRESS);

    $task = Task::factory()->for($fixture['organization'])->for($fixture['project'])->create([
        'status' => 'in_progress',
        'created_by_user_id' => $fixture['admin']->id,
    ]);

    app(ProjectService::class)->transitionStatus(
        $fixture['project']->fresh(),
        ProjectStatus::COMPLETED,
        $fixture['manager']->fresh(),
        'Finished work',
        acknowledgeIncompleteWork: true,
    );

    expect(fn () => Task::factory()->for($fixture['organization'])->for($fixture['project'])->create([
        'created_by_user_id' => $fixture['admin']->id,
    ]))->toThrow(ValidationException::class);

    expect(fn () => TimeEntry::factory()->create([
        'organization_id' => $fixture['organization']->id,
        'project_id' => $fixture['project']->id,
        'task_id' => $task->id,
        'user_id' => $fixture['manager']->id,
    ]))->toThrow(ValidationException::class);
});

it('dispatches a child rescope job when the project organization changes', function (): void {
    Bus::fake();

    $fixture = projectFixture(status: ProjectStatus::PLANNED);
    $newOrganization = Organization::factory()->create();

    $fixture['project']->update([
        'organization_id' => $newOrganization->id,
    ]);

    Bus::assertDispatched(RescopeProjectChildrenJob::class, fn (RescopeProjectChildrenJob $job): bool => $job->projectId === $fixture['project']->id);
});

it('blocks deleting projects with logged time entries', function (): void {
    $fixture = projectFixture(status: ProjectStatus::IN_PROGRESS);

    $task = Task::factory()->for($fixture['organization'])->for($fixture['project'])->create();

    TimeEntry::factory()->create([
        'user_id' => $fixture['manager']->id,
        'task_id' => $task->id,
        'organization_id' => $fixture['organization']->id,
        'project_id' => $fixture['project']->id,
        'hours' => 2.5,
        'cost_amount' => 125,
    ]);

    expect(fn () => $fixture['project']->delete())->toThrow(ProjectDeletionBlockedException::class);
});

/**
 * @return array{organization: Organization, admin: User, manager: User, project: Project}
 */
function projectFixture(
    ProjectStatus $status = ProjectStatus::PLANNED,
    bool $requiresApproval = false,
): array {
    $organization = Organization::factory()->create();

    OrganizationSetting::factory()->for($organization)->create([
        'project_reference_prefix' => 'PROJ-',
        'project_reference_sequence' => 0,
        'project_completion_mode' => 'manual',
        'project_budget_alert_threshold_percent' => 10,
        'project_schedule_alert_threshold_days' => 30,
    ]);

    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    $organization->forceFill([
        'owner_user_id' => $admin->id,
    ])->save();

    $project = Project::factory()->for($organization)->create([
        'manager_id' => $manager->id,
        'status' => $status->value,
        'type' => ProjectType::RENOVATION->value,
        'priority' => ProjectPriority::HIGH->value,
        'requires_approval' => $requiresApproval,
        'approved_at' => null,
        'approved_by' => null,
        'actual_start_date' => $status === ProjectStatus::IN_PROGRESS ? today()->subDay() : null,
    ]);

    return [
        'organization' => $organization->fresh(),
        'admin' => $admin->fresh(),
        'manager' => $manager->fresh(),
        'project' => $project->fresh(),
    ];
}
