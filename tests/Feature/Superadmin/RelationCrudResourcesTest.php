<?php

use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\Project;
use App\Models\PropertyAssignment;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('exposes the new relation CRUD resources to superadmins only', function () {
    $organization = Organization::factory()->create();

    $superadmin = User::factory()->superadmin()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $project = Project::factory()->for($organization)->create();
    $task = Task::factory()->for($organization)->for($project)->create();

    TaskAssignment::factory()->for($task)->create();
    PropertyAssignment::factory()->for($organization)->create();
    OrganizationUser::factory()->for($organization)->create();
    Tag::factory()->for($organization)->create();

    $indexRoutes = [
        'filament.admin.resources.projects.index',
        'filament.admin.resources.tasks.index',
        'filament.admin.resources.task-assignments.index',
        'filament.admin.resources.property-assignments.index',
        'filament.admin.resources.organization-users.index',
        'filament.admin.resources.tags.index',
    ];

    foreach ($indexRoutes as $routeName) {
        actingAs($superadmin);

        get(route($routeName))
            ->assertSuccessful();

        actingAs($admin);

        get(route($routeName))
            ->assertForbidden();
    }
});
