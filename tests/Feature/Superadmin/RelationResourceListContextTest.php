<?php

use App\Filament\Resources\Comments\Pages\CreateComment;
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
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select as FormSelect;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\IconColumn;
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
    app()->setLocale('lt');

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
        ->assertTableColumnExists('organization.name', fn (TextColumn $column): bool => $column->getLabel() === __('superadmin.comments_resource.fields.organization'))
        ->assertTableColumnExists('commentable_type', fn (TextColumn $column): bool => $column->getLabel() === __('superadmin.comments_resource.fields.commentable_type'))
        ->assertTableColumnExists('commentable_id', fn (TextColumn $column): bool => $column->getLabel() === __('superadmin.comments_resource.fields.commentable_id'))
        ->assertTableColumnExists('user.name', fn (TextColumn $column): bool => $column->getLabel() === __('superadmin.comments_resource.fields.user'))
        ->assertTableColumnExists('parent.body', fn (TextColumn $column): bool => $column->getLabel() === __('superadmin.comments_resource.fields.parent_comment'))
        ->assertTableColumnExists('body', fn (TextColumn $column): bool => $column->getLabel() === __('superadmin.comments_resource.fields.body'))
        ->assertTableColumnExists('is_internal', fn (IconColumn $column): bool => $column->getLabel() === __('superadmin.comments_resource.fields.is_internal'))
        ->assertTableColumnExists('is_pinned', fn (IconColumn $column): bool => $column->getLabel() === __('superadmin.comments_resource.fields.is_pinned'))
        ->assertTableColumnExists('edited_at', fn (TextColumn $column): bool => $column->getLabel() === __('superadmin.comments_resource.fields.edited_at'))
        ->assertTableFilterExists('organization', fn (SelectFilter $filter): bool => $filter->getLabel() === __('superadmin.comments_resource.fields.organization'))
        ->assertCanSeeTableRecords([$commentA, $commentB])
        ->filterTable('organization', (string) $organizationA->getKey())
        ->assertCanSeeTableRecords([$commentA])
        ->assertCanNotSeeTableRecords([$commentB]);
});

it('uses translated labels on the comments create form', function () {
    app()->setLocale('lt');

    $this->actingAs(User::factory()->superadmin()->create());

    Livewire::test(CreateComment::class)
        ->assertFormFieldExists('organization_id', fn (FormSelect $field): bool => $field->getLabel() === __('superadmin.comments_resource.fields.organization'))
        ->assertFormFieldExists('commentable_type', fn (FormSelect $field): bool => $field->getLabel() === __('superadmin.comments_resource.fields.commentable_type'))
        ->assertFormFieldExists('commentable_id', fn (FormSelect $field): bool => $field->getLabel() === __('superadmin.comments_resource.fields.commentable_id'))
        ->assertFormFieldExists('user_id', fn (FormSelect $field): bool => $field->getLabel() === __('superadmin.comments_resource.fields.user'))
        ->assertFormFieldExists('parent_id', fn (FormSelect $field): bool => $field->getLabel() === __('superadmin.comments_resource.fields.parent'))
        ->assertFormFieldExists('body', fn (Textarea $field): bool => $field->getLabel() === __('superadmin.comments_resource.fields.body'))
        ->assertFormFieldExists('is_internal', fn (Toggle $field): bool => $field->getLabel() === __('superadmin.comments_resource.fields.is_internal'))
        ->assertFormFieldExists('is_pinned', fn (Toggle $field): bool => $field->getLabel() === __('superadmin.comments_resource.fields.is_pinned'))
        ->assertFormFieldExists('edited_at', fn (DateTimePicker $field): bool => $field->getLabel() === __('superadmin.comments_resource.fields.edited_at'));
});
