<?php

use App\Enums\OrganizationStatus;
use App\Enums\SubscriptionDuration;
use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Filament\Resources\Organizations\Pages\ListOrganizations;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\Organization;
use App\Models\Property;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
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
        'property_limit_snapshot' => 10,
        'tenant_limit_snapshot' => 10,
        'meter_limit_snapshot' => 20,
        'invoice_limit_snapshot' => 25,
    ]);

    Building::factory()->count(3)->create([
        'organization_id' => $organization->id,
    ]);

    $property = Property::factory()->create([
        'organization_id' => $organization->id,
        'building_id' => Building::query()->whereBelongsTo($organization)->value('id'),
    ]);

    Meter::factory()->count(4)->create([
        'organization_id' => $organization->id,
        'property_id' => $property->id,
    ]);

    Invoice::factory()->count(5)->create([
        'organization_id' => $organization->id,
        'property_id' => $property->id,
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
        ->assertTableColumnExists('properties_count', fn (TextColumn $column): bool => $column->getLabel() === __('superadmin.organizations.overview.usage_labels.properties'))
        ->assertTableColumnExists('tenants_count', fn (TextColumn $column): bool => $column->getLabel() === __('superadmin.organizations.overview.usage_labels.tenants'))
        ->assertTableColumnExists('meters_count', fn (TextColumn $column): bool => $column->getLabel() === __('superadmin.organizations.overview.usage_labels.meters'))
        ->assertTableColumnExists('invoices_count', fn (TextColumn $column): bool => $column->getLabel() === __('superadmin.organizations.overview.usage_labels.invoices'))
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
        ->assertTableBulkActionExists('suspendSelected')
        ->assertTableBulkActionExists('reinstateSelected')
        ->assertTableBulkActionExists('deleteSelected')
        ->assertTableBulkActionExists('exportSelected')
        ->assertTableColumnStateSet('properties_count', 1, $organization)
        ->assertTableColumnStateSet('tenants_count', 2, $organization)
        ->assertTableColumnStateSet('meters_count', 4, $organization)
        ->assertTableColumnStateSet('invoices_count', 5, $organization);
});

it('supports superadmin status controls from the organizations list page', function () {
    $superadmin = User::factory()->superadmin()->create();

    $activeOrganization = Organization::factory()->create();
    $suspendedOrganization = Organization::factory()->create([
        'status' => OrganizationStatus::SUSPENDED,
    ]);

    $this->actingAs($superadmin);

    Livewire::test(ListOrganizations::class)
        ->assertTableActionExists('suspendOrganization', record: $activeOrganization)
        ->callAction(TestAction::make('suspendOrganization')->table($activeOrganization));

    expect($activeOrganization->fresh()->status)->toBe(OrganizationStatus::SUSPENDED);

    Livewire::test(ListOrganizations::class)
        ->assertTableActionExists('reinstateOrganization', record: $suspendedOrganization)
        ->callAction(TestAction::make('reinstateOrganization')->table($suspendedOrganization));

    expect($suspendedOrganization->fresh()->status)->toBe(OrganizationStatus::ACTIVE);

    $bulkActiveOrganization = Organization::factory()->create();
    $bulkSuspendedOrganization = Organization::factory()->create([
        'status' => OrganizationStatus::SUSPENDED,
    ]);

    Livewire::test(ListOrganizations::class)
        ->selectTableRecords([$bulkActiveOrganization->getKey()])
        ->callAction(TestAction::make('suspendSelected')->table()->bulk());

    expect($bulkActiveOrganization->fresh()->status)->toBe(OrganizationStatus::SUSPENDED);

    Livewire::test(ListOrganizations::class)
        ->selectTableRecords([$bulkSuspendedOrganization->getKey()])
        ->callAction(TestAction::make('reinstateSelected')->table()->bulk());

    expect($bulkSuspendedOrganization->fresh()->status)->toBe(OrganizationStatus::ACTIVE);
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

it('shows users count and monthly normalized mrr in the organizations list', function () {
    $superadmin = User::factory()->superadmin()->create();

    $organization = Organization::factory()->create([
        'name' => 'Helios Property Group',
    ]);

    $owner = User::factory()->admin()->create([
        'organization_id' => $organization->id,
        'email' => 'owner@helios.test',
    ]);

    $organization->forceFill([
        'owner_user_id' => $owner->id,
    ])->save();

    $subscription = Subscription::factory()->for($organization)->create([
        'plan' => SubscriptionPlan::PROFESSIONAL,
        'status' => SubscriptionStatus::ACTIVE,
        'is_trial' => false,
        'starts_at' => now()->subMonth(),
        'expires_at' => now()->addMonths(2),
    ]);

    SubscriptionPayment::factory()->create([
        'organization_id' => $organization->id,
        'subscription_id' => $subscription->id,
        'duration' => SubscriptionDuration::QUARTERLY,
        'amount' => 150.00,
        'currency' => 'EUR',
        'paid_at' => now()->subDay(),
    ]);

    User::factory()->count(2)->manager()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($superadmin);

    Livewire::test(ListOrganizations::class)
        ->assertTableColumnExists('users_count')
        ->assertTableColumnExists('mrr_display')
        ->assertTableColumnStateSet('users_count', 3, $organization)
        ->assertTableColumnStateSet('mrr_display', 'EUR 50.00', $organization);
});
