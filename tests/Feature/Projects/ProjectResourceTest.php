<?php

declare(strict_types=1);

use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Filament\Resources\Projects\Pages\ViewProject;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

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

    actingAs($superadmin);

    get(route('filament.admin.resources.projects.index'))
        ->assertSuccessful()
        ->assertSeeText($organizationA->name)
        ->assertSeeText($organizationB->name)
        ->assertSeeText($projectA->name)
        ->assertSeeText($projectB->name);

    actingAs($admin);

    get(route('filament.admin.resources.projects.index'))
        ->assertSuccessful()
        ->assertSeeText($organizationA->name)
        ->assertSeeText($projectA->name)
        ->assertDontSeeText($organizationB->name)
        ->assertDontSeeText($projectB->name);
});

it('renders the projects list table columns and filters required for the superadmin view', function (): void {
    app()->setLocale('lt');

    $superadmin = User::factory()->superadmin()->create();

    actingAs($superadmin);

    Livewire::test(ListProjects::class)
        ->assertTableColumnExists('name', fn (TextColumn $column): bool => $column->getLabel() !== '')
        ->assertTableColumnExists('organization.name')
        ->assertTableColumnExists('reference_number')
        ->assertTableColumnExists('status', fn (TextColumn $column): bool => $column->getLabel() === __('admin.projects.columns.status'))
        ->assertTableColumnExists('priority', fn (TextColumn $column): bool => $column->getLabel() === __('admin.projects.columns.priority'))
        ->assertTableColumnExists('building.name', fn (TextColumn $column): bool => $column->getLabel() === __('admin.projects.columns.building'))
        ->assertTableColumnExists('property.name', fn (TextColumn $column): bool => $column->getLabel() === __('admin.projects.columns.property'))
        ->assertTableColumnExists('type', fn (TextColumn $column): bool => $column->getLabel() === __('admin.projects.columns.type'))
        ->assertTableColumnExists('budget_amount', fn (TextColumn $column): bool => $column->getLabel() === __('admin.projects.columns.budget_amount'))
        ->assertTableColumnExists('actual_cost', fn (TextColumn $column): bool => $column->getLabel() === __('admin.projects.columns.actual_cost'))
        ->assertTableColumnExists('created_at', fn (TextColumn $column): bool => $column->getLabel() === __('admin.projects.columns.created_at'))
        ->assertTableColumnExists('estimated_end_date')
        ->assertTableColumnExists('completion_percentage', fn (ViewColumn $column): bool => $column->getName() === 'completion_percentage')
        ->assertTableFilterExists('organization', fn (SelectFilter $filter): bool => $filter->getLabel() !== '')
        ->assertTableFilterExists('status')
        ->assertTableFilterExists('priority')
        ->assertTableFilterExists('type')
        ->assertTableFilterExists('manager')
        ->assertTableFilterExists('building')
        ->assertTableFilterExists('has_overdue_tasks', fn (TernaryFilter $filter): bool => $filter->getLabel() !== '')
        ->assertTableFilterExists('is_over_budget')
        ->assertTableFilterExists('is_behind_schedule')
        ->assertTableFilterExists('is_unassigned')
        ->assertTableFilterExists('created_between', fn (Filter $filter): bool => $filter->getLabel() !== '')
        ->assertTableFilterExists('estimated_end_between')
        ->assertTableFilterExists('needs_attention', fn (Filter $filter): bool => $filter->getLabel() !== '');
});

it('sorts the superadmin project table by priority and estimated end date by default', function (): void {
    $organization = Organization::factory()->create();
    $superadmin = User::factory()->superadmin()->create();

    $highLater = Project::factory()->for($organization)->create([
        'name' => 'High later',
        'priority' => ProjectPriority::HIGH,
        'estimated_end_date' => now()->addDays(4)->toDateString(),
    ]);

    $criticalLater = Project::factory()->for($organization)->create([
        'name' => 'Critical later',
        'priority' => ProjectPriority::CRITICAL,
        'estimated_end_date' => now()->addDays(7)->toDateString(),
    ]);

    $criticalSooner = Project::factory()->for($organization)->create([
        'name' => 'Critical sooner',
        'priority' => ProjectPriority::CRITICAL,
        'estimated_end_date' => now()->addDay()->toDateString(),
    ]);

    actingAs($superadmin);

    $component = Livewire::test(ListProjects::class);

    expect($component->instance()->getTableRecords()->pluck('id')->take(3)->all())
        ->toBe([
            $criticalSooner->id,
            $criticalLater->id,
            $highLater->id,
        ]);
});

it('applies the needs attention filter for high-risk projects', function (): void {
    $organization = Organization::factory()->create();
    $superadmin = User::factory()->superadmin()->create();

    $needsAttention = Project::factory()->for($organization)->create([
        'name' => 'Needs attention',
        'status' => ProjectStatus::IN_PROGRESS,
        'budget_amount' => 100,
        'actual_cost' => 180,
        'estimated_end_date' => now()->subDay()->toDateString(),
    ]);

    Task::factory()->for($organization)->for($needsAttention)->create([
        'priority' => 'urgent',
        'status' => 'pending',
    ]);

    $healthy = Project::factory()->for($organization)->create([
        'name' => 'Healthy project',
        'status' => ProjectStatus::PLANNED,
        'budget_amount' => 1000,
        'actual_cost' => 100,
        'estimated_end_date' => now()->addWeek()->toDateString(),
    ]);

    actingAs($superadmin);

    Livewire::test(ListProjects::class)
        ->filterTable('needs_attention')
        ->assertCanSeeTableRecords([$needsAttention])
        ->assertCanNotSeeTableRecords([$healthy]);
});

it('renders the project view header actions for the configured lifecycle', function (): void {
    $organization = Organization::factory()->create();
    $manager = User::factory()->manager()->create([
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

    actingAs($superadmin);

    get(route('filament.admin.resources.projects.view', $project))
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

it('shows the passthrough action only for completed passthrough projects', function (): void {
    $organization = Organization::factory()->create();
    $superadmin = User::factory()->superadmin()->create();

    $completedProject = Project::factory()->completed()->for($organization)->create([
        'cost_passed_to_tenant' => true,
    ]);

    $plannedProject = Project::factory()->for($organization)->create([
        'status' => ProjectStatus::PLANNED,
        'cost_passed_to_tenant' => true,
    ]);

    Livewire::actingAs($superadmin)
        ->test(ViewProject::class, ['record' => $completedProject->getRouteKey()])
        ->assertActionVisible('generateCostPassthrough');

    Livewire::actingAs($superadmin)
        ->test(ViewProject::class, ['record' => $plannedProject->getRouteKey()])
        ->assertActionHidden('generateCostPassthrough');
});

it('validates project reference numbers within the selected organization scope on edit', function (): void {
    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();
    $superadmin = User::factory()->superadmin()->create();

    Project::factory()->for($organization)->create([
        'reference_number' => 'PROJ-1001',
    ]);

    $editableProject = Project::factory()->for($organization)->create([
        'reference_number' => 'PROJ-1002',
    ]);

    Project::factory()->for($otherOrganization)->create([
        'reference_number' => 'PROJ-2001',
    ]);

    Livewire::actingAs($superadmin)
        ->test(EditProject::class, ['record' => $editableProject->getRouteKey()])
        ->fillForm([
            'organization_id' => $organization->id,
            'reference_number' => 'PROJ-1001',
        ])
        ->call('save')
        ->assertHasFormErrors(['reference_number']);

    Livewire::actingAs($superadmin)
        ->test(EditProject::class, ['record' => $editableProject->getRouteKey()])
        ->fillForm([
            'organization_id' => $otherOrganization->id,
            'reference_number' => 'PROJ-1001',
        ])
        ->call('save')
        ->assertHasNoFormErrors(['reference_number']);
});
