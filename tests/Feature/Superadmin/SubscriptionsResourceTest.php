<?php

use App\Enums\SubscriptionDuration;
use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Filament\Resources\Subscriptions\Pages\ListSubscriptions;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('only allows superadmins to reach subscriptions control-plane pages', function () {
    $superadmin = User::factory()->superadmin()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => Organization::factory(),
    ]);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.subscriptions.index'))
        ->assertOk();

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.subscriptions.index'))
        ->assertForbidden();
});

it('lists subscriptions with filters, usage columns, and governance actions', function () {
    $superadmin = User::factory()->superadmin()->create();
    $atlas = Organization::factory()->create(['name' => 'Atlas Plaza']);
    $birch = Organization::factory()->create(['name' => 'Birch Court']);
    $cedar = Organization::factory()->create(['name' => 'Cedar Point']);

    $atlasSubscription = Subscription::factory()->active()->create([
        'organization_id' => $atlas->id,
        'plan' => SubscriptionPlan::PROFESSIONAL,
        'plan_name_snapshot' => SubscriptionPlan::PROFESSIONAL->label(),
        'limits_snapshot' => SubscriptionPlan::PROFESSIONAL->limitsSnapshot(),
        'expires_at' => now()->addDays(5),
    ]);
    $birchSubscription = Subscription::factory()->create([
        'organization_id' => $birch->id,
        'plan' => SubscriptionPlan::BASIC,
        'plan_name_snapshot' => SubscriptionPlan::BASIC->label(),
        'limits_snapshot' => SubscriptionPlan::BASIC->limitsSnapshot(),
        'status' => SubscriptionStatus::SUSPENDED,
        'is_trial' => false,
        'expires_at' => now()->addDays(45),
    ]);
    $cedarSubscription = Subscription::factory()->active()->create([
        'organization_id' => $cedar->id,
        'plan' => SubscriptionPlan::ENTERPRISE,
        'plan_name_snapshot' => SubscriptionPlan::ENTERPRISE->label(),
        'limits_snapshot' => SubscriptionPlan::ENTERPRISE->limitsSnapshot(),
        'expires_at' => now()->addDays(200),
    ]);

    $this->actingAs($superadmin);

    Livewire::test(ListSubscriptions::class)
        ->assertCanSeeTableRecords([$atlasSubscription, $birchSubscription, $cedarSubscription])
        ->assertTableColumnExists('organization.name')
        ->assertTableColumnExists('plan_name_snapshot')
        ->assertTableColumnExists('status')
        ->assertTableColumnExists('properties_used')
        ->assertTableColumnExists('tenants_used')
        ->assertTableFilterExists('organization')
        ->assertTableFilterExists('plan')
        ->assertTableFilterExists('status')
        ->assertTableFilterExists('expiring_within')
        ->assertTableActionVisible('extend', $atlasSubscription)
        ->assertTableActionVisible('upgradePlan', $atlasSubscription)
        ->assertTableActionVisible('suspend', $atlasSubscription)
        ->assertTableActionVisible('cancel', $atlasSubscription);

    Livewire::test(ListSubscriptions::class)
        ->filterTable('organization', $atlas)
        ->assertCanSeeTableRecords([$atlasSubscription])
        ->assertCanNotSeeTableRecords([$birchSubscription, $cedarSubscription]);

    Livewire::test(ListSubscriptions::class)
        ->filterTable('plan', SubscriptionPlan::BASIC)
        ->assertCanSeeTableRecords([$birchSubscription])
        ->assertCanNotSeeTableRecords([$atlasSubscription, $cedarSubscription]);

    Livewire::test(ListSubscriptions::class)
        ->filterTable('status', SubscriptionStatus::SUSPENDED)
        ->assertCanSeeTableRecords([$birchSubscription])
        ->assertCanNotSeeTableRecords([$atlasSubscription, $cedarSubscription]);

    Livewire::test(ListSubscriptions::class)
        ->filterTable('expiring_within', '7')
        ->assertCanSeeTableRecords([$atlasSubscription])
        ->assertCanNotSeeTableRecords([$birchSubscription, $cedarSubscription]);
});

it('extends expiry and upgrades the subscription plan from table actions', function () {
    $superadmin = User::factory()->superadmin()->create();
    $subscription = Subscription::factory()->active()->create([
        'plan' => SubscriptionPlan::PROFESSIONAL,
        'plan_name_snapshot' => SubscriptionPlan::PROFESSIONAL->label(),
        'limits_snapshot' => SubscriptionPlan::PROFESSIONAL->limitsSnapshot(),
        'expires_at' => now()->addDays(10),
    ]);

    $this->actingAs($superadmin);

    $originalExpiry = $subscription->expires_at?->copy();

    Livewire::test(ListSubscriptions::class)
        ->mountTableAction('extend', $subscription)
        ->setTableActionData([
            'duration' => SubscriptionDuration::MONTHLY->value,
        ])
        ->callMountedTableAction()
        ->assertHasNoFormErrors();

    expect($subscription->refresh()->expires_at?->gt($originalExpiry))->toBeTrue();

    Livewire::test(ListSubscriptions::class)
        ->mountTableAction('upgradePlan', $subscription)
        ->setTableActionData([
            'plan' => SubscriptionPlan::ENTERPRISE->value,
        ])
        ->callMountedTableAction()
        ->assertHasNoFormErrors();

    expect($subscription->refresh()->plan)->toBe(SubscriptionPlan::ENTERPRISE)
        ->and($subscription->plan_name_snapshot)->toBe(SubscriptionPlan::ENTERPRISE->label())
        ->and($subscription->limits_snapshot)->toBe(SubscriptionPlan::ENTERPRISE->limitsSnapshot());
});

it('suspends and cancels subscriptions from table actions', function () {
    $superadmin = User::factory()->superadmin()->create();
    $subscription = Subscription::factory()->active()->create();

    $this->actingAs($superadmin);

    Livewire::test(ListSubscriptions::class)
        ->mountTableAction('suspend', $subscription)
        ->assertMountedActionModalSee('Suspend subscription')
        ->callMountedTableAction();

    expect($subscription->refresh()->status)->toBe(SubscriptionStatus::SUSPENDED);

    Livewire::test(ListSubscriptions::class)
        ->mountTableAction('cancel', $subscription)
        ->assertMountedActionModalSee('Cancel subscription')
        ->callMountedTableAction();

    expect($subscription->refresh()->status)->toBe(SubscriptionStatus::CANCELLED);
});
