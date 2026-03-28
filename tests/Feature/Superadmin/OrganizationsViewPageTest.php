<?php

use App\Enums\OrganizationStatus;
use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Organizations\OrganizationResource;
use App\Filament\Resources\Organizations\Pages\ViewOrganization;
use App\Filament\Resources\Organizations\RelationManagers\ActivityLogsRelationManager;
use App\Filament\Resources\Organizations\RelationManagers\BuildingsRelationManager;
use App\Filament\Resources\Organizations\RelationManagers\ManagersRelationManager;
use App\Filament\Resources\Organizations\RelationManagers\PropertiesRelationManager;
use App\Filament\Resources\Organizations\RelationManagers\SubscriptionsRelationManager;
use App\Filament\Resources\Organizations\RelationManagers\UsersRelationManager;
use App\Jobs\Superadmin\Organizations\SendOrganizationAnnouncementJob;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\Organization;
use App\Models\OrganizationActivityLog;
use App\Models\Property;
use App\Models\SecurityViolation;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionRenewal;
use App\Models\User;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the organization view page overview, actions, and tabs', function () {
    [$organization, $owner, $subscription] = seedOrganizationViewFixture();

    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.organizations.view', $organization))
        ->assertSuccessful()
        ->assertSeeText('Northwind Towers')
        ->assertSeeText('northwind-towers')
        ->assertSeeText(__('superadmin.organizations.actions.edit'))
        ->assertSeeText(__('superadmin.organizations.actions.suspend'))
        ->assertSeeText(__('superadmin.organizations.actions.force_plan_change'))
        ->assertSeeText(__('superadmin.organizations.actions.transfer_ownership'))
        ->assertSeeText(__('superadmin.organizations.actions.override_limits'))
        ->assertSeeText(__('superadmin.organizations.actions.toggle_feature'))
        ->assertSeeText(__('superadmin.organizations.actions.send_notification'))
        ->assertSeeText(__('superadmin.organizations.actions.impersonate_admin'))
        ->assertSeeText(__('superadmin.organizations.actions.export_data'))
        ->assertSeeText(__('superadmin.organizations.pages.overview_tab'))
        ->assertSeeText(__('superadmin.organizations.relations.users.title'))
        ->assertSeeText(__('superadmin.organizations.relations.subscriptions.title'))
        ->assertSeeText(__('superadmin.organizations.relations.buildings.title'))
        ->assertSeeText(__('superadmin.organizations.relations.activity_logs.title'))
        ->assertSeeText(__('superadmin.organizations.overview.details_heading'))
        ->assertSeeText(__('superadmin.organizations.overview.subscription_heading'))
        ->assertSeeText(__('superadmin.organizations.overview.health_heading'))
        ->assertSeeText(__('superadmin.organizations.overview.fields.current_plan'))
        ->assertSeeText(__('superadmin.organizations.overview.fields.subscription_status'))
        ->assertSeeText(__('superadmin.organizations.overview.fields.subscription_expiry_date'))
        ->assertSeeText('7 of 10')
        ->assertSeeText('3 of 25')
        ->assertSeeText('4 of 12')
        ->assertSeeText('5 of 8');

    $this->actingAs($superadmin);

    Livewire::test(ViewOrganization::class, ['record' => $organization->getRouteKey()])
        ->assertActionExists('forcePlanChange')
        ->assertActionExists('transferOwnership')
        ->assertActionExists('overrideLimits')
        ->assertActionExists('toggleFeature')
        ->assertActionExists('sendNotification')
        ->assertActionExists('exportData');
});

it('shows organization health metrics and full subscription usage gauges', function () {
    [$organization, $owner, $subscription, $building, $activityLog] = seedOrganizationViewFixture();

    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.organizations.view', $organization))
        ->assertSuccessful()
        ->assertSeeText(__('superadmin.organizations.overview.health_heading'))
        ->assertSeeText(__('superadmin.organizations.overview.health_labels.access'))
        ->assertSeeText(__('superadmin.organizations.overview.health_labels.recent_activity'))
        ->assertSeeText(__('superadmin.organizations.overview.health_labels.security_violations'))
        ->assertSeeText(__('superadmin.organizations.overview.health_labels.last_activity'))
        ->assertSeeText(__('superadmin.organizations.overview.usage_labels.meters'))
        ->assertSeeText(__('superadmin.organizations.overview.usage_labels.invoices'))
        ->assertSeeText((string) $organization->activityLogs()->count())
        ->assertSeeText((string) $organization->securityViolations()->count())
        ->assertSeeText($activityLog->created_at?->locale(app()->getLocale())->isoFormat('ll') ?? '')
        ->assertSeeText('4 of 12')
        ->assertSeeText('5 of 8');
});

it('sends organization notifications from the view page action', function () {
    [$organization] = seedOrganizationViewFixture();
    $superadmin = User::factory()->superadmin()->create();

    Queue::fake();

    $this->actingAs($superadmin);

    Livewire::test(ViewOrganization::class, ['record' => $organization->getRouteKey()])
        ->callAction('sendNotification', data: [
            'title' => 'Water Shutdown',
            'body' => 'Water service will be offline tomorrow from 08:00 to 10:00.',
            'severity' => 'warning',
        ]);

    Queue::assertPushed(SendOrganizationAnnouncementJob::class, function (SendOrganizationAnnouncementJob $job) use ($organization): bool {
        return $job->organizationId === $organization->id
            && $job->title === 'Water Shutdown'
            && $job->body === 'Water service will be offline tomorrow from 08:00 to 10:00.'
            && $job->severity === 'warning';
    });
});

it('renders the users, subscriptions, buildings, managers, properties, and activity log relation manager contracts', function () {
    [$organization, $owner, $subscription, $building, $activityLog] = seedOrganizationViewFixture();
    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
        'role' => UserRole::MANAGER,
        'name' => 'Maya Manager',
        'email' => 'maya.manager@northwind.test',
    ]);
    $property = Property::query()->where('organization_id', $organization->id)->firstOrFail();
    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($superadmin);

    Livewire::test(UsersRelationManager::class, [
        'ownerRecord' => $organization,
        'pageClass' => ViewOrganization::class,
    ])
        ->assertTableColumnExists('name', fn ($column): bool => $column->getLabel() === __('superadmin.organizations.relations.users.columns.name'))
        ->assertTableColumnExists('email', fn ($column): bool => $column->getLabel() === __('superadmin.organizations.relations.users.columns.email'))
        ->assertTableColumnExists('role', fn ($column): bool => $column->getLabel() === __('superadmin.organizations.relations.users.columns.role'))
        ->assertTableColumnExists('last_login_at', fn ($column): bool => $column->getLabel() === __('superadmin.organizations.relations.users.columns.last_login'))
        ->assertTableColumnExists('status', fn ($column): bool => $column->getLabel() === __('superadmin.organizations.relations.users.columns.status'))
        ->assertTableActionExists('view', record: $owner)
        ->assertTableActionExists('toggleUserStatus', record: $owner)
        ->assertTableActionExists('resetPassword', record: $owner);

    Livewire::test(SubscriptionsRelationManager::class, [
        'ownerRecord' => $organization,
        'pageClass' => ViewOrganization::class,
    ])
        ->assertTableColumnExists('plan', fn ($column): bool => $column->getLabel() === __('superadmin.organizations.relations.subscriptions.columns.plan'))
        ->assertTableColumnExists('status', fn ($column): bool => $column->getLabel() === __('superadmin.organizations.relations.subscriptions.columns.status'))
        ->assertTableColumnExists('starts_at', fn ($column): bool => $column->getLabel() === __('superadmin.organizations.relations.subscriptions.columns.start_date'))
        ->assertTableColumnExists('expires_at', fn ($column): bool => $column->getLabel() === __('superadmin.organizations.relations.subscriptions.columns.expiry_date'))
        ->assertTableColumnExists('property_limit_snapshot', fn ($column): bool => $column->getLabel() === __('superadmin.organizations.relations.subscriptions.columns.property_limit'))
        ->assertTableColumnExists('tenant_limit_snapshot', fn ($column): bool => $column->getLabel() === __('superadmin.organizations.relations.subscriptions.columns.tenant_limit'))
        ->assertTableColumnExists('created_at', fn ($column): bool => $column->getLabel() === __('superadmin.organizations.relations.subscriptions.columns.date_created'))
        ->assertTableActionExists('viewHistory', record: $subscription);

    Livewire::test(BuildingsRelationManager::class, [
        'ownerRecord' => $organization,
        'pageClass' => ViewOrganization::class,
    ])
        ->assertTableColumnExists('name', fn ($column): bool => $column->getLabel() === __('superadmin.organizations.relations.buildings.columns.building_name'))
        ->assertTableColumnExists('address', fn ($column): bool => $column->getLabel() === __('superadmin.organizations.relations.buildings.columns.address'))
        ->assertTableColumnExists('properties_count', fn ($column): bool => $column->getLabel() === __('superadmin.organizations.relations.buildings.columns.properties_count'))
        ->assertTableColumnExists('meters_count', fn ($column): bool => $column->getLabel() === __('superadmin.organizations.relations.buildings.columns.meters_count'))
        ->assertTableColumnExists('created_at', fn ($column): bool => $column->getLabel() === __('superadmin.organizations.relations.buildings.columns.date_created'));

    Livewire::test(ManagersRelationManager::class, [
        'ownerRecord' => $organization,
        'pageClass' => ViewOrganization::class,
    ])
        ->assertTableColumnExists('name', fn ($column): bool => $column->getLabel() === __('superadmin.users.fields.name'))
        ->assertTableColumnExists('email', fn ($column): bool => $column->getLabel() === __('superadmin.users.fields.email'))
        ->assertTableColumnExists('status', fn ($column): bool => $column->getLabel() === __('superadmin.users.fields.status'))
        ->assertTableColumnExists('locale', fn ($column): bool => $column->getLabel() === __('superadmin.users.fields.locale'))
        ->assertTableActionExists('view', record: $manager)
        ->assertTableActionExists('edit', record: $manager)
        ->assertTableActionExists('delete', record: $manager);

    Livewire::test(PropertiesRelationManager::class, [
        'ownerRecord' => $organization,
        'pageClass' => ViewOrganization::class,
    ])
        ->assertTableColumnExists('name', fn ($column): bool => $column->getLabel() === __('admin.properties.fields.name'))
        ->assertTableColumnExists('unit_number', fn ($column): bool => $column->getLabel() === __('admin.properties.fields.unit_number'))
        ->assertTableColumnExists('building.name', fn ($column): bool => $column->getLabel() === __('admin.buildings.singular'))
        ->assertTableColumnExists('currentAssignment.tenant.name', fn ($column): bool => $column->getLabel() === __('admin.properties.fields.current_tenant'))
        ->assertTableColumnExists('type', fn ($column): bool => $column->getLabel() === __('admin.properties.fields.type'))
        ->assertTableActionExists('edit', record: $property)
        ->assertTableActionExists('delete', record: $property);

    Livewire::test(ActivityLogsRelationManager::class, [
        'ownerRecord' => $organization,
        'pageClass' => ViewOrganization::class,
    ])
        ->assertTableColumnExists('user.name', fn ($column): bool => $column->getLabel() === __('superadmin.organizations.relations.activity_logs.columns.actor'))
        ->assertTableColumnExists('action', fn ($column): bool => $column->getLabel() === __('superadmin.organizations.relations.activity_logs.columns.action'))
        ->assertTableColumnExists('resource_label', fn ($column): bool => $column->getLabel() === __('superadmin.organizations.relations.activity_logs.columns.record'))
        ->assertTableColumnExists('ip_address', fn ($column): bool => $column->getLabel() === __('superadmin.organizations.relations.activity_logs.columns.ip_address'))
        ->assertTableColumnExists('created_at', fn ($column): bool => $column->getLabel() === __('superadmin.organizations.relations.activity_logs.columns.when'))
        ->assertTableActionExists('viewChanges', record: $activityLog);
});

it('keeps organization relation tab badges deferred with counts across tab switches', function () {
    [$organization] = seedOrganizationViewFixture();
    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($superadmin);

    $component = Livewire::test(ViewOrganization::class, ['record' => $organization->getRouteKey()]);

    $assertRelationTabBadges = function ($page): void {
        $record = $page->getRecord();

        $tabComponents = collect($page->getDeferredRelationManagerTabs(
            $page->getRelationManagers(),
            $page->hasCombinedRelationManagerTabsWithContent(),
            ['ownerRecord' => $record, 'pageClass' => ViewOrganization::class],
            $record,
        ));
        $relationManagerKeys = array_keys(OrganizationResource::getRelations());

        foreach ($relationManagerKeys as $relationManagerKey) {
            /** @var Tab $tab */
            $tab = $tabComponents->get($relationManagerKey);

            expect($tab)->not->toBeNull()
                ->and($tab->isBadgeDeferred())->toBeTrue();
        }
    };

    $assertRelationTabBadges($component->invade());

    $component->set('activeRelationManager', 'users');

    $assertRelationTabBadges($component->invade());
});

function seedOrganizationViewFixture(): array
{
    $organization = Organization::factory()->create([
        'name' => 'Northwind Towers',
        'slug' => 'northwind-towers',
        'status' => OrganizationStatus::ACTIVE,
    ]);

    $owner = User::factory()->admin()->create([
        'organization_id' => $organization->id,
        'name' => 'Olivia Owner',
        'email' => 'owner@northwind.test',
        'last_login_at' => now()->subHour(),
    ]);

    $organization->forceFill([
        'owner_user_id' => $owner->id,
    ])->save();

    User::factory()->manager()->create([
        'organization_id' => $organization->id,
        'email' => 'manager@northwind.test',
        'last_login_at' => now()->subDay(),
    ]);

    User::factory()->admin()->create([
        'organization_id' => $organization->id,
        'email' => 'admin@northwind.test',
        'last_login_at' => now()->subHours(2),
    ]);

    User::factory()->count(3)->tenant()->create([
        'organization_id' => $organization->id,
    ]);

    $subscription = Subscription::factory()->for($organization)->create([
        'plan' => SubscriptionPlan::BASIC,
        'status' => SubscriptionStatus::ACTIVE,
        'is_trial' => false,
        'starts_at' => now()->subMonth()->startOfDay(),
        'expires_at' => now()->addMonth()->startOfDay(),
        'property_limit_snapshot' => 10,
        'tenant_limit_snapshot' => 25,
        'meter_limit_snapshot' => 12,
        'invoice_limit_snapshot' => 8,
    ]);

    SubscriptionPayment::factory()->for($subscription)->create([
        'organization_id' => $organization->id,
    ]);

    SubscriptionRenewal::factory()->for($subscription)->create();

    $building = Building::factory()->create([
        'organization_id' => $organization->id,
        'name' => 'Northwind Plaza',
        'address_line_1' => '123 Harbor Road',
        'city' => 'Vilnius',
    ]);

    Property::factory()->count(7)->create([
        'organization_id' => $organization->id,
        'building_id' => $building->id,
    ]);

    $property = Property::query()->where('organization_id', $organization->id)->firstOrFail();

    Meter::factory()->count(4)->create([
        'organization_id' => $organization->id,
        'property_id' => $property->id,
    ]);

    Invoice::factory()->count(5)->create([
        'organization_id' => $organization->id,
        'property_id' => $property->id,
    ]);

    $activityLog = OrganizationActivityLog::factory()->create([
        'organization_id' => $organization->id,
        'user_id' => $owner->id,
        'action' => 'updated_subscription',
        'resource_type' => Subscription::class,
        'resource_id' => $subscription->id,
        'metadata' => [
            'before' => [
                'status' => 'trialing',
            ],
            'after' => [
                'status' => 'active',
            ],
        ],
        'ip_address' => '203.0.113.10',
    ]);

    SecurityViolation::factory()->count(2)->create([
        'organization_id' => $organization->id,
        'user_id' => $owner->id,
    ]);

    return [$organization, $owner, $subscription, $building, $activityLog];
}
