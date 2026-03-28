<?php

use App\Filament\Resources\Comments\Pages\ListComments;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Filament\Resources\Tags\Pages\ListTags;
use App\Filament\Resources\Tasks\Pages\ListTasks;
use App\Models\Comment;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('shows organization context on the projects list for superadmins and scopes admins to their organization', function () {
    $organizationA = Organization::factory()->create(['name' => 'Aurora Heights']);
    $organizationB = Organization::factory()->create(['name' => 'Boreal Court']);

    $projectA = Project::factory()->for($organizationA)->create([
        'name' => 'Lobby Upgrade',
    ]);

    $projectB = Project::factory()->for($organizationB)->create([
        'name' => 'Roof Inspection',
    ]);

    $superadmin = User::factory()->superadmin()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organizationA->id,
    ]);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.projects.index'))
        ->assertSuccessful()
        ->assertSeeText($organizationA->name)
        ->assertSeeText($organizationB->name)
        ->assertSeeText($projectA->name)
        ->assertSeeText($projectB->name);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.projects.index'))
        ->assertSuccessful()
        ->assertSeeText($organizationA->name)
        ->assertSeeText($projectA->name)
        ->assertDontSeeText($organizationB->name)
        ->assertDontSeeText($projectB->name);

    $this->actingAs($superadmin);

    Livewire::test(ListProjects::class)
        ->assertTableColumnExists('organization.name', fn (TextColumn $column): bool => $column->getLabel() === 'Organization')
        ->assertTableFilterExists('organization', fn (SelectFilter $filter): bool => $filter->getLabel() === 'Organization')
        ->assertCanSeeTableRecords([$projectA, $projectB])
        ->filterTable('organization', (string) $organizationA->getKey())
        ->assertCanSeeTableRecords([$projectA])
        ->assertCanNotSeeTableRecords([$projectB]);
});

it('shows organization context on the tasks list for superadmins', function () {
    $organizationA = Organization::factory()->create(['name' => 'Canopy Residences']);
    $organizationB = Organization::factory()->create(['name' => 'Delta Square']);

    $taskA = Task::factory()->for($organizationA)->create([
        'title' => 'Inspect heat exchanger',
    ]);

    $taskB = Task::factory()->for($organizationB)->create([
        'title' => 'Prepare turnover report',
    ]);

    $superadmin = User::factory()->superadmin()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organizationA->id,
    ]);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.tasks.index'))
        ->assertSuccessful()
        ->assertSeeText($organizationA->name)
        ->assertSeeText($organizationB->name)
        ->assertSeeText($taskA->title)
        ->assertSeeText($taskB->title);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.tasks.index'))
        ->assertForbidden();

    $this->actingAs($superadmin);

    Livewire::test(ListTasks::class)
        ->assertTableColumnExists('organization.name', fn (TextColumn $column): bool => $column->getLabel() === 'Organization')
        ->assertTableFilterExists('organization', fn (SelectFilter $filter): bool => $filter->getLabel() === 'Organization')
        ->assertCanSeeTableRecords([$taskA, $taskB])
        ->filterTable('organization', (string) $organizationA->getKey())
        ->assertCanSeeTableRecords([$taskA])
        ->assertCanNotSeeTableRecords([$taskB]);
});

it('shows organization context on the tags list for superadmins', function () {
    $organizationA = Organization::factory()->create(['name' => 'Elm Gardens']);
    $organizationB = Organization::factory()->create(['name' => 'Foundry Point']);

    $tagA = Tag::factory()->for($organizationA)->create([
        'name' => 'Urgent Service',
    ]);

    $tagB = Tag::factory()->for($organizationB)->create([
        'name' => 'Capital Works',
    ]);

    $superadmin = User::factory()->superadmin()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organizationA->id,
    ]);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.tags.index'))
        ->assertSuccessful()
        ->assertSeeText($organizationA->name)
        ->assertSeeText($organizationB->name)
        ->assertSeeText($tagA->name)
        ->assertSeeText($tagB->name);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.tags.index'))
        ->assertForbidden();

    $this->actingAs($superadmin);

    Livewire::test(ListTags::class)
        ->assertTableColumnExists('organization.name', fn (TextColumn $column): bool => $column->getLabel() === 'Organization')
        ->assertTableFilterExists('organization', fn (SelectFilter $filter): bool => $filter->getLabel() === 'Organization')
        ->assertCanSeeTableRecords([$tagA, $tagB])
        ->filterTable('organization', (string) $organizationA->getKey())
        ->assertCanSeeTableRecords([$tagA])
        ->assertCanNotSeeTableRecords([$tagB]);
});

it('shows organization context on the comments list for superadmins', function () {
    $organizationA = Organization::factory()->create(['name' => 'Granite Place']);
    $organizationB = Organization::factory()->create(['name' => 'Harbor Lofts']);

    $commentA = Comment::factory()->for($organizationA)->create([
        'body' => 'Aurora maintenance comment.',
    ]);

    $commentB = Comment::factory()->for($organizationB)->create([
        'body' => 'Harbor audit comment.',
    ]);

    $superadmin = User::factory()->superadmin()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organizationA->id,
    ]);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.comments.index'))
        ->assertSuccessful()
        ->assertSeeText($organizationA->name)
        ->assertSeeText($organizationB->name)
        ->assertSeeText($commentA->body)
        ->assertSeeText($commentB->body);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.comments.index'))
        ->assertForbidden();

    $this->actingAs($superadmin);

    Livewire::test(ListComments::class)
        ->assertTableColumnExists('organization.name', fn (TextColumn $column): bool => $column->getLabel() === 'Organization')
        ->assertTableFilterExists('organization', fn (SelectFilter $filter): bool => $filter->getLabel() === 'Organization')
        ->assertCanSeeTableRecords([$commentA, $commentB])
        ->filterTable('organization', (string) $organizationA->getKey())
        ->assertCanSeeTableRecords([$commentA])
        ->assertCanNotSeeTableRecords([$commentB]);
});
