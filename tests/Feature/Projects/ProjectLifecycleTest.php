<?php

declare(strict_types=1);

use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Exceptions\ProjectApprovalRequiredException;
use App\Exceptions\ProjectDeletionBlockedException;
use App\Jobs\Projects\RescopeProjectChildrenJob;
use App\Models\Building;
use App\Models\Organization;
use App\Models\OrganizationSetting;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use App\Notifications\Projects\ProjectApprovedNotification;
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

it('allows emergency projects to start immediately and stamps the start date', function (): void {
    $organization = Organization::factory()->create();
    $actor = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $project = app(ProjectService::class)->create([
        'name' => 'Burst pipe response',
        'type' => ProjectType::EMERGENCY->value,
        'priority' => ProjectPriority::CRITICAL->value,
        'status' => ProjectStatus::IN_PROGRESS->value,
    ], $organization, $actor);

    expect($project->status)->toBe(ProjectStatus::IN_PROGRESS)
        ->and($project->actual_start_date?->toDateString())->toBe(today()->toDateString());
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

it('requires a cancellation reason and closes open tasks when cancelled', function (): void {
    $fixture = projectFixture(status: ProjectStatus::IN_PROGRESS);

    $task = Task::factory()->for($fixture['organization'])->for($fixture['project'])->create([
        'status' => 'pending',
        'completed_at' => null,
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
        ->and($task->fresh()->status)->toBe('cancelled');
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
