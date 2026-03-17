<?php

use App\Models\Activity;
use App\Models\Attachment;
use App\Models\Building;
use App\Models\Comment;
use App\Models\CommentReaction;
use App\Models\DashboardCustomization;
use App\Models\EnhancedTask;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Property;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('creates the legacy collaboration and content foundation tables', function () {
    expect(Schema::hasTable('projects'))->toBeTrue()
        ->and(Schema::hasTable('tasks'))->toBeTrue()
        ->and(Schema::hasTable('task_assignments'))->toBeTrue()
        ->and(Schema::hasTable('enhanced_tasks'))->toBeTrue()
        ->and(Schema::hasTable('comments'))->toBeTrue()
        ->and(Schema::hasTable('comment_reactions'))->toBeTrue()
        ->and(Schema::hasTable('attachments'))->toBeTrue()
        ->and(Schema::hasTable('tags'))->toBeTrue()
        ->and(Schema::hasTable('activities'))->toBeTrue()
        ->and(Schema::hasTable('dashboard_customizations'))->toBeTrue()
        ->and(Schema::hasTable('time_entries'))->toBeTrue()
        ->and(class_exists(Project::class))->toBeTrue()
        ->and(class_exists(Task::class))->toBeTrue()
        ->and(class_exists(TaskAssignment::class))->toBeTrue()
        ->and(class_exists(EnhancedTask::class))->toBeTrue()
        ->and(class_exists(Comment::class))->toBeTrue()
        ->and(class_exists(CommentReaction::class))->toBeTrue()
        ->and(class_exists(Attachment::class))->toBeTrue()
        ->and(class_exists(Tag::class))->toBeTrue()
        ->and(class_exists(Activity::class))->toBeTrue()
        ->and(class_exists(DashboardCustomization::class))->toBeTrue()
        ->and(class_exists(TimeEntry::class))->toBeTrue();
});

it('links legacy collaboration content through current organization and user models', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);

    $project = Project::factory()
        ->for($organization)
        ->for($property)
        ->for($building)
        ->for($admin, 'creator')
        ->create();

    $task = Task::factory()
        ->for($organization)
        ->for($project)
        ->for($admin, 'creator')
        ->create();

    $assignment = TaskAssignment::factory()
        ->for($task)
        ->for($tenant)
        ->create();

    $enhancedTask = EnhancedTask::factory()
        ->for($organization)
        ->for($project)
        ->for($property)
        ->for($admin, 'creator')
        ->create();

    $comment = Comment::factory()
        ->for($organization)
        ->for($admin)
        ->for($project, 'commentable')
        ->create();

    $reaction = CommentReaction::factory()
        ->for($comment)
        ->for($tenant)
        ->create();

    $attachment = Attachment::factory()
        ->for($organization)
        ->for($admin, 'uploader')
        ->for($project, 'attachable')
        ->create();

    $tag = Tag::factory()
        ->for($organization)
        ->create();

    $project->tags()->syncWithoutDetaching([
        $tag->id => ['tagged_by_user_id' => $admin->id],
    ]);

    $activity = Activity::factory()
        ->for($organization)
        ->for($project, 'subject')
        ->for($admin, 'causer')
        ->create();

    $dashboardCustomization = DashboardCustomization::factory()
        ->for($admin)
        ->create();

    $timeEntry = TimeEntry::factory()
        ->for($tenant)
        ->for($task)
        ->for($assignment, 'assignment')
        ->create();

    expect($organization->fresh()->projects->contains($project))->toBeTrue()
        ->and($project->fresh()->tasks->contains($task))->toBeTrue()
        ->and($task->fresh()->assignments->contains($assignment))->toBeTrue()
        ->and($enhancedTask->fresh()->project?->is($project))->toBeTrue()
        ->and($comment->fresh()->commentable?->is($project))->toBeTrue()
        ->and($comment->fresh()->reactions->contains($reaction))->toBeTrue()
        ->and($attachment->fresh()->attachable?->is($project))->toBeTrue()
        ->and($project->fresh()->tags->contains($tag))->toBeTrue()
        ->and($activity->fresh()->subject?->is($project))->toBeTrue()
        ->and($admin->fresh()->dashboardCustomization?->is($dashboardCustomization))->toBeTrue()
        ->and($task->fresh()->timeEntries->contains($timeEntry))->toBeTrue();
});
