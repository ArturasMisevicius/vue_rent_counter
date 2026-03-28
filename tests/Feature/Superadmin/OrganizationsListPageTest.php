<?php

use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Filament\Resources\Organizations\Pages\ListOrganizations;
use App\Models\Building;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the organizations index contract for superadmins', function () {
    $superadmin = User::factory()->superadmin()->create();
    $organization = Organization::factory()->create([
        'name' => 'Northwind Towers',
    ]);

    $owner = User::factory()->admin()->create([
        'organization_id' => $organization->id,
        'email' => 'owner@northwind.test',
    ]);

    $organization->forceFill([
        'owner_user_id' => $owner->id,
    ])->save();

    Subscription::factory()->for($organization)->create([
        'plan' => SubscriptionPlan::PROFESSIONAL,
        'status' => SubscriptionStatus::ACTIVE,
        'is_trial' => false,
        'starts_at' => now()->subMonth(),
        'expires_at' => now()->addMonths(2),
    ]);

    Building::factory()->count(3)->create([
        'organization_id' => $organization->id,
    ]);

    User::factory()->count(2)->tenant()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.organizations.index'))
        ->assertSuccessful()
        ->assertSeeText('Organizations')
        ->assertSeeText(__('superadmin.organizations.actions.new'));

    $this->actingAs($superadmin);

    Livewire::test(ListOrganizations::class)
        ->assertTableColumnExists('name', fn (TextColumn $column): bool => $column->getLabel() === __('superadmin.organizations.columns.name'))
        ->assertTableColumnExists('owner.email', fn (TextColumn $column): bool => $column->getLabel() === __('superadmin.organizations.columns.owner_email'))
        ->assertTableColumnExists('currentSubscription.plan', fn (TextColumn $column): bool => $column->getLabel() === __('superadmin.organizations.overview.fields.current_plan'))
        ->assertTableColumnExists('currentSubscription.status', fn (TextColumn $column): bool => $column->getLabel() === __('superadmin.organizations.overview.fields.subscription_status'))
        ->assertTableColumnExists('buildings_count', fn (TextColumn $column): bool => $column->getLabel() === __('superadmin.organizations.overview.usage_labels.properties'))
        ->assertTableColumnExists('tenants_count', fn (TextColumn $column): bool => $column->getLabel() === __('superadmin.organizations.overview.usage_labels.tenants'))
        ->assertTableColumnExists('created_at', fn (TextColumn $column): bool => $column->getLabel() === __('superadmin.organizations.columns.created_at'))
        ->assertTableFilterExists('subscription_status', fn (SelectFilter $filter): bool => $filter->getLabel() === __('superadmin.organizations.overview.fields.subscription_status'))
        ->assertTableFilterExists('plan', fn (SelectFilter $filter): bool => $filter->getLabel() === __('superadmin.organizations.overview.fields.current_plan'))
        ->assertTableFilterExists('created_between', fn (Filter $filter): bool => $filter->getLabel() === __('superadmin.organizations.columns.created_at'))
        ->assertTableActionExists('view', record: $organization)
        ->assertTableActionExists('edit', record: $organization)
        ->assertTableActionExists('suspendOrganization', record: $organization)
        ->assertTableActionExists('sendNotification', record: $organization)
        ->assertTableActionExists('impersonateAdmin', record: $organization)
        ->assertTableActionExists('exportData', record: $organization)
        ->assertTableActionExists('deleteOrganization', record: $organization)
        ->assertTableBulkActionExists('deleteSelected')
        ->assertTableBulkActionExists('exportSelected')
        ->assertTableColumnStateSet('buildings_count', 3, $organization)
        ->assertTableColumnStateSet('tenants_count', 2, $organization);
});

it('searches and filters organizations by owner email, subscription, plan, and creation date', function () {
    $superadmin = User::factory()->superadmin()->create();

    $matchingOrganization = Organization::factory()->create([
        'name' => 'Aurora Estates',
        'created_at' => now()->subDays(2),
    ]);

    $matchingOwner = User::factory()->admin()->create([
        'organization_id' => $matchingOrganization->id,
        'email' => 'owner@aurora.test',
    ]);

    $matchingOrganization->forceFill([
        'owner_user_id' => $matchingOwner->id,
    ])->save();

    Subscription::factory()->for($matchingOrganization)->create([
        'plan' => SubscriptionPlan::BASIC,
        'status' => SubscriptionStatus::ACTIVE,
        'is_trial' => false,
        'starts_at' => now()->subMonth(),
        'expires_at' => now()->addMonth(),
    ]);

    $filteredOutOrganization = Organization::factory()->create([
        'name' => 'Beacon Holdings',
        'created_at' => now()->subMonths(2),
    ]);

    $filteredOutOwner = User::factory()->admin()->create([
        'organization_id' => $filteredOutOrganization->id,
        'email' => 'owner@beacon.test',
    ]);

    $filteredOutOrganization->forceFill([
        'owner_user_id' => $filteredOutOwner->id,
    ])->save();

    Subscription::factory()->for($filteredOutOrganization)->create([
        'plan' => SubscriptionPlan::ENTERPRISE,
        'status' => SubscriptionStatus::SUSPENDED,
        'is_trial' => false,
        'starts_at' => now()->subMonths(3),
        'expires_at' => now()->addWeeks(2),
    ]);

    $this->actingAs($superadmin);

    Livewire::test(ListOrganizations::class)
        ->searchTable('owner@aurora.test')
        ->assertCanSeeTableRecords([$matchingOrganization])
        ->assertCanNotSeeTableRecords([$filteredOutOrganization])
        ->searchTable()
        ->filterTable('subscription_status', SubscriptionStatus::ACTIVE)
        ->assertCanSeeTableRecords([$matchingOrganization])
        ->assertCanNotSeeTableRecords([$filteredOutOrganization])
        ->resetTableFilters()
        ->filterTable('plan', SubscriptionPlan::BASIC)
        ->assertCanSeeTableRecords([$matchingOrganization])
        ->assertCanNotSeeTableRecords([$filteredOutOrganization])
        ->resetTableFilters()
        ->filterTable('created_between', [
            'created_from' => now()->subWeek()->toDateString(),
            'created_to' => now()->toDateString(),
        ])
        ->assertCanSeeTableRecords([$matchingOrganization])
        ->assertCanNotSeeTableRecords([$filteredOutOrganization]);
});

it('paginates organizations at twenty rows per page', function () {
    $superadmin = User::factory()->superadmin()->create();

    Organization::factory()->count(24)->create();

    $this->actingAs($superadmin);

    $component = Livewire::test(ListOrganizations::class);

    expect($component->instance()->getTableRecordsPerPage())->toBe(20)
        ->and($component->instance()->getTable()->getPaginationPageOptions())->toBe([20]);
});
