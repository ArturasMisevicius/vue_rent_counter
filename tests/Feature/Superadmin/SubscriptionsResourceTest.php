<?php

use App\Enums\SubscriptionDuration;
use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Filament\Actions\Superadmin\Subscriptions\CancelSubscriptionAction;
use App\Filament\Actions\Superadmin\Subscriptions\ExtendSubscriptionAction;
use App\Filament\Actions\Superadmin\Subscriptions\SuspendSubscriptionAction;
use App\Filament\Actions\Superadmin\Subscriptions\UpgradeSubscriptionPlanAction;
use App\Models\Organization;
use App\Models\Property;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows the superadmin subscriptions resource pages only to superadmins', function () {
    $superadmin = User::factory()->superadmin()->create();
    $organization = Organization::factory()->create([
        'name' => 'Northwind Towers',
    ]);

    Property::factory()->count(2)->for($organization)->create();
    User::factory()->count(3)->tenant()->create([
        'organization_id' => $organization->id,
    ]);

    $subscription = Subscription::factory()
        ->for($organization)
        ->active()
        ->create([
            'plan' => SubscriptionPlan::BASIC,
        ]);

    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.subscriptions.index'))
        ->assertSuccessful()
        ->assertSeeText('Subscriptions')
        ->assertSeeText($organization->name)
        ->assertSeeText('Basic')
        ->assertSeeText('Active');

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.subscriptions.create'))
        ->assertSuccessful()
        ->assertSeeText('Organization')
        ->assertSeeText('Plan')
        ->assertSeeText('Status');

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.subscriptions.view', $subscription))
        ->assertSuccessful()
        ->assertSeeText($organization->name)
        ->assertSeeText('Basic');

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.subscriptions.edit', $subscription))
        ->assertSuccessful()
        ->assertSeeText('Save changes');

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.subscriptions.index'))
        ->assertForbidden();
});

it('extends upgrades suspends and cancels subscriptions through actions', function () {
    $subscription = Subscription::factory()->active()->create([
        'plan' => SubscriptionPlan::BASIC,
        'expires_at' => now()->addMonth(),
    ]);

    $originalExpiry = $subscription->expires_at->copy();

    $extended = app(ExtendSubscriptionAction::class)->handle($subscription, SubscriptionDuration::QUARTERLY);
    $upgraded = app(UpgradeSubscriptionPlanAction::class)->handle($extended->fresh(), SubscriptionPlan::ENTERPRISE);
    $suspended = app(SuspendSubscriptionAction::class)->handle($upgraded->fresh());
    $cancelled = app(CancelSubscriptionAction::class)->handle($suspended->fresh());

    expect($extended->expires_at->greaterThan($originalExpiry))->toBeTrue()
        ->and($upgraded->plan)->toBe(SubscriptionPlan::ENTERPRISE)
        ->and($upgraded->property_limit_snapshot)->toBe(SubscriptionPlan::ENTERPRISE->limits()['properties'])
        ->and($suspended->status)->toBe(SubscriptionStatus::SUSPENDED)
        ->and($cancelled->status)->toBe(SubscriptionStatus::CANCELLED);
});
