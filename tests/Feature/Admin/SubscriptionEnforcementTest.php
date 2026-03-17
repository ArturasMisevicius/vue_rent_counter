<?php

use App\Enums\PropertyType;
use App\Enums\SubscriptionStatus;
use App\Filament\Actions\Admin\Properties\UpdatePropertyAction;
use App\Filament\Resources\Properties\Pages\ListProperties;
use App\Filament\Resources\Tenants\Pages\ListTenants;
use App\Models\Building;
use App\Models\Organization;
use App\Models\Property;
use App\Models\Subscription;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('shows an upgrade dialog instead of the property create form when the property limit is reached', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    Subscription::factory()->for($organization)->create([
        'status' => SubscriptionStatus::ACTIVE,
        'property_limit_snapshot' => 1,
        'tenant_limit_snapshot' => 5,
        'meter_limit_snapshot' => 10,
        'invoice_limit_snapshot' => 10,
    ]);

    Property::factory()->for($organization)->for($building)->create();

    $this->actingAs($admin);

    Livewire::test(ListProperties::class)
        ->assertActionVisible('create')
        ->mountAction('create')
        ->assertActionMounted('create')
        ->assertMountedActionModalSee(__('behavior.subscription.limit_blocked.properties.title'))
        ->assertMountedActionModalSee(__('behavior.subscription.limit_blocked.properties.body', [
            'used' => 1,
            'limit' => 1,
        ]))
        ->assertMountedActionModalSee(__('behavior.subscription.actions.manage'))
        ->assertMountedActionModalSeeHtml(route('filament.admin.pages.settings').'#subscription');
});

it('shows an upgrade dialog instead of the tenant create form when the tenant limit is reached', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    Subscription::factory()->for($organization)->create([
        'status' => SubscriptionStatus::ACTIVE,
        'property_limit_snapshot' => 5,
        'tenant_limit_snapshot' => 1,
        'meter_limit_snapshot' => 10,
        'invoice_limit_snapshot' => 10,
    ]);

    User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($admin);

    Livewire::test(ListTenants::class)
        ->assertActionVisible('create')
        ->mountAction('create')
        ->assertActionMounted('create')
        ->assertMountedActionModalSee(__('behavior.subscription.limit_blocked.tenants.title'))
        ->assertMountedActionModalSee(__('behavior.subscription.limit_blocked.tenants.body', [
            'used' => 1,
            'limit' => 1,
        ]))
        ->assertMountedActionModalSee(__('behavior.subscription.actions.manage'))
        ->assertMountedActionModalSeeHtml(route('filament.admin.pages.settings').'#subscription');
});

it('keeps create and edit affordances visible during the grace period but intercepts them with renewal messaging', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);
    $property = Property::factory()->for($organization)->for($building)->create([
        'name' => 'A-12',
    ]);

    $subscription = Subscription::factory()->for($organization)->create([
        'status' => SubscriptionStatus::EXPIRED,
        'expires_at' => now()->subDays(3),
        'property_limit_snapshot' => 5,
        'tenant_limit_snapshot' => 5,
        'meter_limit_snapshot' => 10,
        'invoice_limit_snapshot' => 10,
    ]);

    $this->actingAs($admin);

    $message = __('behavior.subscription.grace_read_only.body', [
        'grace_ends_at' => $subscription->expires_at?->copy()->addDays(7)->toDateString(),
    ]);

    Livewire::test(ListProperties::class)
        ->assertActionVisible('create')
        ->mountAction('create')
        ->assertActionMounted('create')
        ->assertMountedActionModalSee(__('behavior.subscription.grace_read_only.title'))
        ->assertMountedActionModalSee($message)
        ->assertMountedActionModalSee(__('behavior.subscription.actions.manage'));

    Livewire::test(ListProperties::class)
        ->assertActionVisible(TestAction::make('edit')->table($property))
        ->mountAction(TestAction::make('edit')->table($property))
        ->assertActionMounted(TestAction::make('edit')->table($property))
        ->assertMountedActionModalSee(__('behavior.subscription.grace_read_only.title'))
        ->assertMountedActionModalSee($message)
        ->assertMountedActionModalSee(__('behavior.subscription.actions.manage'));
});

it('hides write actions after the grace period while keeping subscription settings reachable', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);
    $property = Property::factory()->for($organization)->for($building)->create();
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);

    Subscription::factory()->for($organization)->create([
        'status' => SubscriptionStatus::EXPIRED,
        'expires_at' => now()->subDays(10),
        'property_limit_snapshot' => 5,
        'tenant_limit_snapshot' => 5,
        'meter_limit_snapshot' => 10,
        'invoice_limit_snapshot' => 10,
    ]);

    $this->actingAs($admin);

    Livewire::test(ListProperties::class)
        ->assertActionDoesNotExist('create')
        ->assertActionDoesNotExist(TestAction::make('edit')->table($property))
        ->assertActionVisible(TestAction::make('view')->table($property));

    Livewire::test(ListTenants::class)
        ->assertActionDoesNotExist('create')
        ->assertActionDoesNotExist(TestAction::make('edit')->table($tenant));

    $this->get(route('filament.admin.resources.tenants.view', $tenant))
        ->assertSuccessful()
        ->assertSeeText($tenant->name);

    $this->get(route('filament.admin.pages.settings'))
        ->assertSuccessful()
        ->assertSeeText(__('shell.settings.subscription.heading'));
});

it('blocks direct property updates during the subscription grace period', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create([
        'name' => 'Original Name',
        'unit_number' => '12',
        'type' => PropertyType::APARTMENT,
    ]);

    Subscription::factory()->for($organization)->create([
        'status' => SubscriptionStatus::EXPIRED,
        'expires_at' => now()->subDays(2),
        'property_limit_snapshot' => 5,
        'tenant_limit_snapshot' => 5,
        'meter_limit_snapshot' => 10,
        'invoice_limit_snapshot' => 10,
    ]);

    expect(fn () => app(UpdatePropertyAction::class)->handle($property, [
        'building_id' => $building->id,
        'name' => 'Blocked Update',
        'unit_number' => '12A',
        'type' => PropertyType::OFFICE,
        'floor_area_sqm' => 54.5,
    ]))->toThrow(ValidationException::class);

    expect($property->fresh()->name)->toBe('Original Name');
});
