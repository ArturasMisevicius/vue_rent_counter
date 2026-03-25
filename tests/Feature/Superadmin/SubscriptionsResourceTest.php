<?php

use App\Enums\SubscriptionDuration;
use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Filament\Actions\Superadmin\Subscriptions\CancelSubscriptionAction;
use App\Filament\Actions\Superadmin\Subscriptions\ExtendSubscriptionAction;
use App\Filament\Actions\Superadmin\Subscriptions\SuspendSubscriptionAction;
use App\Filament\Actions\Superadmin\Subscriptions\UpdateSubscriptionExpiryAction;
use App\Filament\Actions\Superadmin\Subscriptions\UpgradeSubscriptionPlanAction;
use App\Filament\Resources\Subscriptions\Pages\ListSubscriptions;
use App\Models\Organization;
use App\Models\Property;
use App\Models\Subscription;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the superadmin subscriptions list page contract', function () {
    $superadmin = User::factory()->superadmin()->create();
    $limits = SubscriptionPlan::BASIC->limits();

    $organization = Organization::factory()->create([
        'name' => 'Northwind Towers',
    ]);

    Property::factory()->count($limits['properties'])->for($organization)->create();
    User::factory()->count($limits['tenants'])->tenant()->create([
        'organization_id' => $organization->id,
    ]);

    $subscription = Subscription::factory()
        ->for($organization)
        ->active()
        ->create([
            'plan' => SubscriptionPlan::BASIC,
            'starts_at' => now()->subMonth()->startOfDay(),
            'expires_at' => now()->addDays(10)->startOfDay(),
            'property_limit_snapshot' => $limits['properties'],
            'tenant_limit_snapshot' => $limits['tenants'],
            'created_at' => now()->subDays(5)->startOfMinute(),
        ]);

    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.subscriptions.index'))
        ->assertSuccessful()
        ->assertSeeText(__('superadmin.subscriptions_resource.plural'))
        ->assertSeeText('New Subscription')
        ->assertSeeText(__('superadmin.subscriptions_resource.columns.organization'))
        ->assertSeeText(__('superadmin.subscriptions_resource.columns.plan'))
        ->assertSeeText(__('superadmin.subscriptions_resource.columns.status'))
        ->assertSeeText(__('superadmin.subscriptions_resource.filters.expiring_within'))
        ->assertSeeText(__('superadmin.subscriptions_resource.filters.days', ['count' => 7]))
        ->assertSeeText(__('superadmin.subscriptions_resource.filters.days', ['count' => 14]))
        ->assertSeeText(__('superadmin.subscriptions_resource.filters.days', ['count' => 30]))
        ->assertSeeText(__('superadmin.subscriptions_resource.filters.days', ['count' => 60]))
        ->assertSeeText(__('superadmin.subscriptions_resource.filters.any_time'))
        ->assertSeeText(__('superadmin.subscriptions_resource.columns.start_date'))
        ->assertSeeText(__('superadmin.subscriptions_resource.columns.expiry_date'))
        ->assertSeeText(__('superadmin.subscriptions_resource.columns.properties_used'))
        ->assertSeeText(__('superadmin.subscriptions_resource.columns.tenants_used'))
        ->assertSeeText(__('superadmin.subscriptions_resource.columns.date_created'))
        ->assertSeeText($organization->name)
        ->assertSeeText('Basic')
        ->assertSeeText('Active')
        ->assertSeeText('10 of 10')
        ->assertSeeText('25 of 25')
        ->assertSee(route('filament.admin.resources.subscriptions.view', $subscription), false)
        ->assertSee(route('filament.admin.resources.organizations.view', $organization), false);

    $this->actingAs($superadmin);

    Livewire::test(ListSubscriptions::class)
        ->assertTableColumnExists('organization.name', fn (TextColumn $column): bool => $column->getLabel() === __('superadmin.subscriptions_resource.columns.organization'))
        ->assertTableColumnExists('plan', fn (TextColumn $column): bool => $column->getLabel() === __('superadmin.subscriptions_resource.columns.plan'))
        ->assertTableColumnExists('status', fn (TextColumn $column): bool => $column->getLabel() === __('superadmin.subscriptions_resource.columns.status'))
        ->assertTableColumnExists('starts_at', fn (TextColumn $column): bool => $column->getLabel() === __('superadmin.subscriptions_resource.columns.start_date'))
        ->assertTableColumnExists('expires_at', fn (TextColumn $column): bool => $column->getLabel() === __('superadmin.subscriptions_resource.columns.expiry_date'))
        ->assertTableColumnExists('properties_used', fn (TextColumn $column): bool => $column->getLabel() === __('superadmin.subscriptions_resource.columns.properties_used'))
        ->assertTableColumnExists('tenants_used', fn (TextColumn $column): bool => $column->getLabel() === __('superadmin.subscriptions_resource.columns.tenants_used'))
        ->assertTableColumnExists('created_at', fn (TextColumn $column): bool => $column->getLabel() === __('superadmin.subscriptions_resource.columns.date_created'))
        ->assertTableFilterExists('organization', fn (Filter $filter): bool => $filter->getLabel() === __('superadmin.subscriptions_resource.filters.organization'))
        ->assertTableFilterExists('plan', fn (SelectFilter $filter): bool => $filter->getLabel() === __('superadmin.subscriptions_resource.filters.plan'))
        ->assertTableFilterExists('status', fn (SelectFilter $filter): bool => $filter->getLabel() === __('superadmin.subscriptions_resource.filters.status'))
        ->assertTableFilterExists('expiring_within', fn (SelectFilter $filter): bool => $filter->getLabel() === __('superadmin.subscriptions_resource.filters.expiring_within') && $filter->getPlaceholder() === __('superadmin.subscriptions_resource.filters.any_time'))
        ->assertTableActionHasLabel('view', __('superadmin.subscriptions_resource.actions.view'), record: $subscription)
        ->assertTableActionHasLabel('edit', __('superadmin.subscriptions_resource.actions.edit'), record: $subscription)
        ->assertTableActionHasLabel('extendExpiry', __('superadmin.subscriptions_resource.actions.extend_expiry'), record: $subscription)
        ->assertTableActionHasLabel('upgradePlan', __('superadmin.subscriptions_resource.actions.upgrade_plan'), record: $subscription)
        ->assertTableActionHasLabel('suspendSubscription', __('superadmin.subscriptions_resource.actions.suspend'), record: $subscription)
        ->assertTableActionHasLabel('cancelSubscription', __('superadmin.subscriptions_resource.actions.cancel'), record: $subscription)
        ->assertTableActionHasLabel('delete', __('superadmin.subscriptions_resource.actions.delete'), record: $subscription)
        ->assertTableColumnStateSet('organization.name', $organization->name, $subscription)
        ->assertTableColumnStateSet('plan', SubscriptionPlan::BASIC->label(), $subscription)
        ->assertTableColumnStateSet('status', SubscriptionStatus::ACTIVE->label(), $subscription)
        ->assertTableColumnStateSet('properties_used', '10 of 10', $subscription)
        ->assertTableColumnStateSet('tenants_used', '25 of 25', $subscription);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.subscriptions.create'))
        ->assertSuccessful()
        ->assertSeeText(__('superadmin.subscriptions_resource.fields.organization'))
        ->assertSeeText(__('superadmin.subscriptions_resource.fields.plan'))
        ->assertSeeText(__('superadmin.subscriptions_resource.fields.status'));

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

it('filters subscriptions by organization, plan, status, and expiry window', function () {
    $superadmin = User::factory()->superadmin()->create();

    $matchingOrganization = Organization::factory()->create([
        'name' => 'Aurora Estates',
    ]);
    $matchingSubscription = Subscription::factory()
        ->for($matchingOrganization)
        ->active()
        ->create([
            'plan' => SubscriptionPlan::BASIC,
            'status' => SubscriptionStatus::ACTIVE,
            'expires_at' => now()->addDays(10)->startOfDay(),
            'property_limit_snapshot' => SubscriptionPlan::BASIC->limits()['properties'],
            'tenant_limit_snapshot' => SubscriptionPlan::BASIC->limits()['tenants'],
        ]);

    $planMismatch = Subscription::factory()
        ->for(Organization::factory()->create(['name' => 'Beacon Holdings']))
        ->active()
        ->create([
            'plan' => SubscriptionPlan::ENTERPRISE,
            'status' => SubscriptionStatus::ACTIVE,
            'expires_at' => now()->addDays(10)->startOfDay(),
        ]);

    $statusMismatch = Subscription::factory()
        ->for(Organization::factory()->create(['name' => 'Cedar Group']))
        ->create([
            'plan' => SubscriptionPlan::BASIC,
            'status' => SubscriptionStatus::SUSPENDED,
            'is_trial' => false,
            'expires_at' => now()->addDays(10)->startOfDay(),
        ]);

    $expiryMismatch = Subscription::factory()
        ->for(Organization::factory()->create(['name' => 'Delta Group']))
        ->active()
        ->create([
            'plan' => SubscriptionPlan::BASIC,
            'status' => SubscriptionStatus::ACTIVE,
            'expires_at' => now()->addDays(45)->startOfDay(),
        ]);

    $this->actingAs($superadmin);

    Livewire::test(ListSubscriptions::class)
        ->filterTable('organization', ['query' => 'Aurora'])
        ->assertCanSeeTableRecords([$matchingSubscription])
        ->assertCanNotSeeTableRecords([$planMismatch, $statusMismatch, $expiryMismatch])
        ->resetTableFilters()
        ->filterTable('plan', SubscriptionPlan::BASIC->value)
        ->assertCanSeeTableRecords([$matchingSubscription, $statusMismatch, $expiryMismatch])
        ->assertCanNotSeeTableRecords([$planMismatch])
        ->resetTableFilters()
        ->filterTable('status', SubscriptionStatus::ACTIVE->value)
        ->assertCanSeeTableRecords([$matchingSubscription, $planMismatch, $expiryMismatch])
        ->assertCanNotSeeTableRecords([$statusMismatch])
        ->resetTableFilters()
        ->filterTable('expiring_within', 14)
        ->assertCanSeeTableRecords([$matchingSubscription, $planMismatch])
        ->assertCanNotSeeTableRecords([$statusMismatch, $expiryMismatch]);
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

it('rejects expiry updates that do not actually extend the subscription', function () {
    $subscription = Subscription::factory()->active()->create([
        'expires_at' => now()->addMonth()->startOfDay(),
    ]);

    $originalExpiryDate = $subscription->expires_at?->toDateString();

    expect(fn () => app(UpdateSubscriptionExpiryAction::class)->handle($subscription, [
        'expires_at' => $originalExpiryDate,
    ]))->toThrow(ValidationException::class);

    expect($subscription->fresh()->expires_at?->toDateString())->toBe($originalExpiryDate);
});
