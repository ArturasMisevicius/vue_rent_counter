<?php

declare(strict_types=1);

use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Filament\Resources\Projects\Pages\ViewProject;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('lets superadmins see projects across organizations and admins only see their own organization', function (): void {
    $organizationA = Organization::factory()->create(['name' => 'Aurora Heights']);
    $organizationB = Organization::factory()->create(['name' => 'Boreal Court']);

    $projectA = Project::factory()->for($organizationA)->create([
        'name' => 'Lobby upgrade',
        'status' => ProjectStatus::IN_PROGRESS->value,
        'priority' => ProjectPriority::CRITICAL->value,
        'type' => ProjectType::RENOVATION->value,
    ]);

    $projectB = Project::factory()->for($organizationB)->create([
        'name' => 'Roof inspection',
        'status' => ProjectStatus::PLANNED->value,
        'priority' => ProjectPriority::HIGH->value,
        'type' => ProjectType::INSPECTION->value,
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
});

it('renders the projects list table columns filters and bulk actions', function (): void {
    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($superadmin);

    Livewire::test(ListProjects::class)
        ->assertTableColumnExists('name', fn (TextColumn $column): bool => $column->getLabel() !== '')
        ->assertTableColumnExists('organization.name')
        ->assertTableColumnExists('reference_number')
        ->assertTableColumnExists('status')
        ->assertTableColumnExists('priority')
        ->assertTableColumnExists('completion_percentage')
        ->assertTableFilterExists('organization', fn (SelectFilter $filter): bool => $filter->getLabel() !== '')
        ->assertTableFilterExists('status')
        ->assertTableFilterExists('priority')
        ->assertTableFilterExists('type')
        ->assertTableFilterExists('manager')
        ->assertTableFilterExists('needs_attention', fn (Filter $filter): bool => $filter->getLabel() !== '');
});

it('renders the project view header actions for the configured lifecycle', function (): void {
    $organization = Organization::factory()->create();
    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);
    $superadmin = User::factory()->superadmin()->create();

    $project = Project::factory()->for($organization)->create([
        'manager_id' => $manager->id,
        'status' => ProjectStatus::PLANNED->value,
        'priority' => ProjectPriority::CRITICAL->value,
        'type' => ProjectType::EMERGENCY->value,
        'requires_approval' => true,
        'approved_at' => null,
    ]);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.projects.view', $project))
        ->assertSuccessful()
        ->assertSeeText($project->name);

    Livewire::actingAs($superadmin)
        ->test(ViewProject::class, ['record' => $project->getRouteKey()])
        ->assertActionExists('edit')
        ->assertActionExists('changeStatus')
        ->assertActionExists('assignManager')
        ->assertActionExists('approveProject')
        ->assertActionExists('viewOrganization')
        ->assertActionExists('viewAuditLog');
});
