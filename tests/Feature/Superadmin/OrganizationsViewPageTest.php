<?php

use App\Enums\AuditLogAction;
use App\Enums\InvoiceStatus;
use App\Enums\OrganizationStatus;
use App\Enums\SubscriptionDuration;
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
use App\Filament\Support\Superadmin\Organizations\OrganizationDashboardData;
use App\Jobs\Superadmin\Organizations\SendOrganizationAnnouncementJob;
use App\Models\AuditLog;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\Organization;
use App\Models\OrganizationActivityLog;
use App\Models\Property;
use App\Models\PropertyAssignment;
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

it('shows portfolio, financial, usage, and subscription widgets on the org detail page', function () {
    [$organization, $owner, $subscription] = seedOrganizationViewFixture();
    $superadmin = User::factory()->superadmin()->create();

    $tenantUsers = User::query()
        ->where('organization_id', $organization->id)
        ->tenants()
        ->orderedByName()
        ->get();

    $properties = Property::query()
        ->where('organization_id', $organization->id)
        ->ordered()
        ->get();

    foreach ($tenantUsers->take(3)->values() as $index => $tenant) {
        PropertyAssignment::factory()->create([
            'organization_id' => $organization->id,
            'property_id' => $properties[$index]->id,
            'tenant_user_id' => $tenant->id,
            'assigned_at' => now()->subMonth(),
            'unassigned_at' => null,
        ]);
    }

    $subscription->payments()->delete();
    $subscription->renewals()->delete();

    SubscriptionPayment::factory()->for($subscription)->create([
        'organization_id' => $organization->id,
        'duration' => SubscriptionDuration::MONTHLY,
        'amount' => 129.00,
        'currency' => 'EUR',
        'paid_at' => now()->subDays(3),
    ]);

    SubscriptionRenewal::factory()->for($subscription)->create([
        'user_id' => null,
        'method' => 'automatic',
        'period' => 'monthly',
        'old_expires_at' => $subscription->expires_at?->copy()->subMonth(),
        'new_expires_at' => $subscription->expires_at,
        'duration_days' => 30,
        'notes' => null,
    ]);

    Invoice::query()->where('organization_id', $organization->id)->delete();

    Invoice::factory()->create([
        'organization_id' => $organization->id,
        'property_id' => $properties[0]->id,
        'tenant_user_id' => $tenantUsers[0]->id,
        'status' => InvoiceStatus::FINALIZED,
        'currency' => 'EUR',
        'total_amount' => 300.00,
        'amount_paid' => 0,
        'paid_amount' => 0,
        'finalized_at' => now()->subDays(20),
        'due_date' => now()->subDays(10)->toDateString(),
        'paid_at' => null,
    ]);

    Invoice::factory()->create([
        'organization_id' => $organization->id,
        'property_id' => $properties[1]->id,
        'tenant_user_id' => $tenantUsers[1]->id,
        'status' => InvoiceStatus::FINALIZED,
        'currency' => 'EUR',
        'total_amount' => 200.00,
        'amount_paid' => 0,
        'paid_amount' => 0,
        'finalized_at' => now()->subDays(10),
        'due_date' => now()->addDays(5)->toDateString(),
        'paid_at' => null,
    ]);

    Invoice::factory()->create([
        'organization_id' => $organization->id,
        'property_id' => $properties[2]->id,
        'tenant_user_id' => $tenantUsers[2]->id,
        'status' => InvoiceStatus::PAID,
        'currency' => 'EUR',
        'total_amount' => 150.00,
        'amount_paid' => 150.00,
        'paid_amount' => 150.00,
        'finalized_at' => now()->subDays(6),
        'due_date' => now()->addDays(7)->toDateString(),
        'paid_at' => now()->subDays(2),
    ]);

    Invoice::factory()->create([
        'organization_id' => $organization->id,
        'property_id' => $properties[0]->id,
        'tenant_user_id' => $tenantUsers[0]->id,
        'status' => InvoiceStatus::PAID,
        'currency' => 'EUR',
        'total_amount' => 90.00,
        'amount_paid' => 90.00,
        'paid_amount' => 90.00,
        'finalized_at' => now()->subDays(12),
        'due_date' => now()->subDays(2)->toDateString(),
        'paid_at' => now()->subDays(6),
    ]);

    Invoice::factory()->create([
        'organization_id' => $organization->id,
        'property_id' => $properties[1]->id,
        'tenant_user_id' => $tenantUsers[1]->id,
        'status' => InvoiceStatus::VOID,
        'currency' => 'EUR',
        'total_amount' => 50.00,
        'amount_paid' => 0,
        'paid_amount' => 0,
        'finalized_at' => now()->subDays(3),
        'due_date' => now()->addDays(3)->toDateString(),
        'paid_at' => null,
    ]);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.organizations.view', $organization))
        ->assertSuccessful()
        ->assertSeeText(__('superadmin.organizations.overview.portfolio_heading'))
        ->assertSeeText(__('superadmin.organizations.overview.financial_heading'))
        ->assertSeeText(__('superadmin.organizations.overview.plan_usage_heading'))
        ->assertSeeText(__('superadmin.organizations.overview.subscription_timeline_heading'))
        ->assertSeeText('1')
        ->assertSeeText('7')
        ->assertSeeText('3')
        ->assertSeeText('4')
        ->assertSeeText('43%')
        ->assertSeeText('EUR 129.00')
        ->assertSeeText('EUR 500.00')
        ->assertSeeText('EUR 300.00')
        ->assertSeeText('EUR 240.00')
        ->assertSeeText('5 days')
        ->assertSeeText('7 of 10')
        ->assertSeeText('3 of 25')
        ->assertSeeText('4 of 12')
        ->assertSeeText('5 of 8')
        ->assertSeeText(SubscriptionPlan::BASIC->label())
        ->assertSeeText(SubscriptionStatus::ACTIVE->label())
        ->assertSeeText(SubscriptionDuration::MONTHLY->label())
        ->assertSeeText($subscription->expires_at?->locale(app()->getLocale())->isoFormat('ll') ?? '')
        ->assertSeeText(__('superadmin.organizations.overview.payment_method_on_file'))
        ->assertSeeText(__('superadmin.organizations.overview.renewal_history'));
});

it('shows the latest ten org audit events on the detail page with deep links into the audit timeline', function () {
    [$organization, $owner] = seedOrganizationViewFixture();
    $superadmin = User::factory()->superadmin()->create();

    $planChangeLog = AuditLog::factory()->create([
        'organization_id' => $organization->id,
        'actor_user_id' => $owner->id,
        'action' => AuditLogAction::UPDATED,
        'subject_type' => Organization::class,
        'subject_id' => $organization->id,
        'description' => 'Organization plan changed',
        'metadata' => [
            'old_plan' => 'basic',
            'new_plan' => 'professional',
        ],
        'occurred_at' => now()->subSeconds(30),
    ]);

    foreach (range(0, 10) as $index) {
        AuditLog::factory()->create([
            'organization_id' => $organization->id,
            'actor_user_id' => $owner->id,
            'action' => AuditLogAction::UPDATED,
            'subject_type' => Organization::class,
            'subject_id' => $organization->id,
            'description' => sprintf('Feed event %02d', $index),
            'occurred_at' => now()->subMinutes($index + 1),
        ]);
    }

    $dashboardData = app(OrganizationDashboardData::class);
    $timelineUrl = htmlspecialchars($dashboardData->organizationAuditTimelineUrl($organization), ENT_QUOTES, 'UTF-8', false);
    $planChangeUrl = htmlspecialchars($dashboardData->auditTimelineUrlForAuditLog($organization, $planChangeLog), ENT_QUOTES, 'UTF-8', false);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.organizations.view', $organization))
        ->assertSuccessful()
        ->assertSeeText(__('superadmin.organizations.overview.activity_feed_heading'))
        ->assertSeeText('Organization plan changed')
        ->assertSeeText('Feed event 00')
        ->assertSeeText('Feed event 08')
        ->assertDontSeeText('Feed event 09')
        ->assertDontSeeText('Feed event 10')
        ->assertSee($timelineUrl, false)
        ->assertSee($planChangeUrl, false);
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
        ->assertTableActionExists('viewChanges', record: $activityLog)
        ->assertTableActionExists('openAuditTimeline', record: $activityLog);
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
