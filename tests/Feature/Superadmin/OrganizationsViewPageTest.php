<?php

use App\Enums\OrganizationStatus;
use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Filament\Resources\Organizations\Pages\ViewOrganization;
use App\Filament\Resources\Organizations\RelationManagers\ActivityLogsRelationManager;
use App\Filament\Resources\Organizations\RelationManagers\BuildingsRelationManager;
use App\Filament\Resources\Organizations\RelationManagers\SubscriptionsRelationManager;
use App\Filament\Resources\Organizations\RelationManagers\UsersRelationManager;
use App\Models\Building;
use App\Models\Organization;
use App\Models\OrganizationActivityLog;
use App\Models\Property;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionRenewal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
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
        ->assertSeeText('Edit')
        ->assertSeeText('Suspend Organization')
        ->assertSeeText('Send Notification')
        ->assertSeeText('Impersonate Admin')
        ->assertSeeText('Export Data')
        ->assertSeeText('Overview')
        ->assertSeeText('Users')
        ->assertSeeText('Subscriptions')
        ->assertSeeText('Buildings')
        ->assertSeeText('Activity Log')
        ->assertSeeText('Organization Details')
        ->assertSeeText('Subscription Summary')
        ->assertSeeText('Current Plan')
        ->assertSeeText('Subscription Status')
        ->assertSeeText('Subscription Expiry Date')
        ->assertSeeText('7 of 10')
        ->assertSeeText('3 of 25');

    $this->actingAs($superadmin);

    Livewire::test(ViewOrganization::class, ['record' => $organization->getRouteKey()])
        ->assertActionExists('sendNotification')
        ->assertActionExists('exportData');
});

it('sends organization notifications from the view page action', function () {
    [$organization] = seedOrganizationViewFixture();
    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($superadmin);

    Livewire::test(ViewOrganization::class, ['record' => $organization->getRouteKey()])
        ->callAction('sendNotification', data: [
            'title' => 'Water Shutdown',
            'body' => 'Water service will be offline tomorrow from 08:00 to 10:00.',
            'severity' => 'warning',
        ]);

    expect(DatabaseNotification::query()->count())->toBe(6)
        ->and(DatabaseNotification::query()->latest()->first()?->data)->toMatchArray([
            'title' => 'Water Shutdown',
            'severity' => 'warning',
        ]);
});

it('renders the users, subscriptions, buildings, and activity log relation manager contracts', function () {
    [$organization, $owner, $subscription, $building, $activityLog] = seedOrganizationViewFixture();
    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($superadmin);

    Livewire::test(UsersRelationManager::class, [
        'ownerRecord' => $organization,
        'pageClass' => ViewOrganization::class,
    ])
        ->assertTableColumnExists('name')
        ->assertTableColumnExists('email')
        ->assertTableColumnExists('role')
        ->assertTableColumnExists('last_login_at')
        ->assertTableColumnExists('status')
        ->assertTableActionExists('view', record: $owner)
        ->assertTableActionExists('toggleUserStatus', record: $owner)
        ->assertTableActionExists('resetPassword', record: $owner);

    Livewire::test(SubscriptionsRelationManager::class, [
        'ownerRecord' => $organization,
        'pageClass' => ViewOrganization::class,
    ])
        ->assertTableColumnExists('plan')
        ->assertTableColumnExists('status')
        ->assertTableColumnExists('starts_at')
        ->assertTableColumnExists('expires_at')
        ->assertTableColumnExists('property_limit_snapshot')
        ->assertTableColumnExists('tenant_limit_snapshot')
        ->assertTableColumnExists('created_at')
        ->assertTableActionExists('viewHistory', record: $subscription);

    Livewire::test(BuildingsRelationManager::class, [
        'ownerRecord' => $organization,
        'pageClass' => ViewOrganization::class,
    ])
        ->assertTableColumnExists('name')
        ->assertTableColumnExists('address')
        ->assertTableColumnExists('properties_count')
        ->assertTableColumnExists('meters_count')
        ->assertTableColumnExists('created_at');

    Livewire::test(ActivityLogsRelationManager::class, [
        'ownerRecord' => $organization,
        'pageClass' => ViewOrganization::class,
    ])
        ->assertTableColumnExists('user.name')
        ->assertTableColumnExists('action')
        ->assertTableColumnExists('resource_label')
        ->assertTableColumnExists('ip_address')
        ->assertTableColumnExists('created_at')
        ->assertTableActionExists('viewChanges', record: $activityLog);
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

    return [$organization, $owner, $subscription, $building, $activityLog];
}
