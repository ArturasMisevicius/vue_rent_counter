<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Activity;
use App\Models\Attachment;
use App\Models\Building;
use App\Models\Comment;
use App\Models\CommentReaction;
use App\Models\DashboardCustomization;
use App\Models\EnhancedTask;
use App\Models\Organization;
use App\Models\OrganizationActivityLog;
use App\Models\OrganizationUser;
use App\Models\Project;
use App\Models\Property;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Database\Seeder;

class LegacyCollaborationFoundationSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()->select(['id'])->orderBy('id')->first();
        $admin = User::query()->select(['id', 'organization_id'])->where('role', UserRole::ADMIN->value)->orderBy('id')->first();
        $tenant = User::query()->select(['id', 'organization_id'])->where('role', UserRole::TENANT->value)->orderBy('id')->first();

        if ($organization === null || $admin === null || $tenant === null) {
            return;
        }

        $manager = User::query()
            ->select(['id', 'organization_id'])
            ->where('organization_id', $organization->id)
            ->where('role', UserRole::MANAGER->value)
            ->orderBy('id')
            ->first();

        if ($manager === null) {
            $manager = User::factory()->manager()->create([
                'organization_id' => $organization->id,
                'name' => 'Legacy Collaboration Manager',
                'email' => sprintf('legacy-collaboration-manager+%d@tenanto.test', $organization->id),
            ]);
        }

        $building = Building::query()
            ->select(['id', 'organization_id'])
            ->where('organization_id', $organization->id)
            ->orderBy('id')
            ->first();

        $property = Property::query()
            ->select(['id', 'organization_id', 'building_id'])
            ->where('organization_id', $organization->id)
            ->orderBy('id')
            ->first();

        if ($building === null || $property === null) {
            return;
        }

        $project = Project::query()->updateOrCreate(
            [
                'organization_id' => $organization->id,
                'name' => 'Legacy Collaboration Demo Project',
            ],
            [
                'property_id' => $property->id,
                'building_id' => $building->id,
                'created_by_user_id' => $admin->id,
                'assigned_to_user_id' => $tenant->id,
                'manager_id' => $manager->id,
                'description' => 'Imported collaboration foundation demo project.',
                'type' => 'maintenance',
                'status' => 'in_progress',
                'priority' => 'high',
                'start_date' => now()->subWeek()->toDateString(),
                'due_date' => now()->addWeek()->toDateString(),
                'completed_at' => null,
                'budget' => 1500,
                'actual_cost' => 0,
                'metadata' => ['seed' => 'legacy_collaboration_foundation'],
            ],
        );

        $task = Task::query()->updateOrCreate(
            [
                'organization_id' => $organization->id,
                'project_id' => $project->id,
                'title' => 'Inspect shared utility setup',
            ],
            [
                'description' => 'Review imported utility service configuration data.',
                'status' => 'in_progress',
                'priority' => 'medium',
                'created_by_user_id' => $admin->id,
                'due_date' => now()->addDays(5)->toDateString(),
                'completed_at' => null,
                'estimated_hours' => 3.5,
                'actual_hours' => 1.0,
                'checklist' => ['Inspect provider links', 'Validate rates'],
            ],
        );

        $assignment = TaskAssignment::query()->updateOrCreate(
            [
                'task_id' => $task->id,
                'user_id' => $tenant->id,
                'role' => 'assignee',
            ],
            [
                'assigned_at' => now(),
                'completed_at' => null,
                'notes' => 'Demo tenant assignment for collaboration foundation.',
            ],
        );

        EnhancedTask::query()->updateOrCreate(
            [
                'organization_id' => $organization->id,
                'title' => 'Review imported collaboration layer',
            ],
            [
                'project_id' => $project->id,
                'property_id' => $property->id,
                'meter_id' => null,
                'created_by_user_id' => $admin->id,
                'parent_enhanced_task_id' => null,
                'description' => 'Higher-fidelity task imported from legacy collaboration domain.',
                'type' => 'inspection',
                'status' => 'pending',
                'priority' => 'high',
                'estimated_hours' => 2.0,
                'actual_hours' => 0,
                'estimated_cost' => 150,
                'actual_cost' => 0,
                'due_date' => now()->addWeek(),
                'started_at' => null,
                'completed_at' => null,
                'metadata' => ['seed' => true],
            ],
        );

        $comment = Comment::query()->updateOrCreate(
            [
                'organization_id' => $organization->id,
                'commentable_type' => Project::class,
                'commentable_id' => $project->id,
                'user_id' => $admin->id,
                'body' => 'Legacy collaboration layer imported successfully.',
            ],
            [
                'parent_id' => null,
                'is_internal' => true,
                'is_pinned' => true,
                'edited_at' => null,
            ],
        );

        CommentReaction::query()->updateOrCreate(
            [
                'comment_id' => $comment->id,
                'user_id' => $tenant->id,
            ],
            [
                'type' => 'like',
            ],
        );

        Attachment::query()->updateOrCreate(
            [
                'organization_id' => $organization->id,
                'attachable_type' => Project::class,
                'attachable_id' => $project->id,
                'uploaded_by_user_id' => $admin->id,
                'path' => 'attachments/legacy-foundation/project-note.pdf',
            ],
            [
                'filename' => 'project-note.pdf',
                'original_filename' => 'legacy-foundation-project-note.pdf',
                'mime_type' => 'application/pdf',
                'size' => 24576,
                'disk' => 'local',
                'description' => 'Seeded demo collaboration attachment.',
                'metadata' => ['seed' => true],
            ],
        );

        $tag = Tag::query()->updateOrCreate(
            [
                'organization_id' => $organization->id,
                'slug' => 'legacy-foundation',
            ],
            [
                'name' => 'Legacy Foundation',
                'color' => '#2563eb',
                'description' => 'Imported from the legacy collaboration foundation.',
                'type' => 'project',
                'is_system' => true,
            ],
        );

        $project->tags()->syncWithoutDetaching([
            $tag->id => ['tagged_by_user_id' => $admin->id],
        ]);

        Activity::query()->updateOrCreate(
            [
                'organization_id' => $organization->id,
                'description' => 'Seeded collaboration foundation activity',
            ],
            [
                'log_name' => 'collaboration',
                'subject_type' => Project::class,
                'subject_id' => $project->id,
                'causer_type' => User::class,
                'causer_id' => $admin->id,
                'properties' => ['seed' => true],
                'event' => 'created',
                'batch_uuid' => null,
            ],
        );

        DashboardCustomization::query()->updateOrCreate(
            ['user_id' => $admin->id],
            [
                'widget_configuration' => [['widget' => 'activity', 'enabled' => true]],
                'layout_configuration' => ['columns' => 2],
                'refresh_intervals' => ['activity' => 60],
            ],
        );

        TimeEntry::query()->updateOrCreate(
            [
                'user_id' => $tenant->id,
                'task_id' => $task->id,
                'assignment_id' => $assignment->id,
            ],
            [
                'hours' => 1.25,
                'description' => 'Seeded demo time entry for imported collaboration task.',
                'metadata' => ['billable' => true],
                'logged_at' => now(),
            ],
        );

        OrganizationUser::query()->updateOrCreate(
            [
                'organization_id' => $organization->id,
                'user_id' => $manager->id,
            ],
            [
                'role' => UserRole::MANAGER->value,
                'permissions' => null,
                'joined_at' => now()->subMonths(2),
                'left_at' => null,
                'is_active' => true,
                'invited_by' => $admin->id,
            ],
        );

        OrganizationActivityLog::query()->updateOrCreate(
            [
                'organization_id' => $organization->id,
                'user_id' => $admin->id,
                'action' => 'legacy.collaboration.seeded',
            ],
            [
                'resource_type' => Project::class,
                'resource_id' => $project->id,
                'metadata' => ['seed' => true],
                'ip_address' => '127.0.0.1',
                'user_agent' => 'legacy-collaboration-seeder',
            ],
        );
    }
}
