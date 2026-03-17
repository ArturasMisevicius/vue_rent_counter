<?php

use App\Enums\OrganizationStatus;
use App\Enums\SubscriptionDuration;
use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Filament\Resources\Organizations\Pages\CreateOrganization;
use App\Filament\Resources\Organizations\Pages\ListOrganizations;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('only allows superadmins to reach organizations control-plane pages', function () {
    $superadmin = User::factory()->superadmin()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => Organization::factory(),
    ]);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.organizations.index'))
        ->assertOk();

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.organizations.index'))
        ->assertForbidden();
});

it('lists organizations with platform filters and subscription columns', function () {
    $superadmin = User::factory()->superadmin()->create();

    $oakResidences = Organization::factory()->create([
        'name' => 'Oak Residences',
        'slug' => 'oak-residences',
        'status' => OrganizationStatus::ACTIVE,
    ]);
    $pineTower = Organization::factory()->create([
        'name' => 'Pine Tower',
        'slug' => 'pine-tower',
        'status' => OrganizationStatus::SUSPENDED,
    ]);

    $oakOwner = User::factory()->admin()->create([
        'name' => 'Oak Owner',
        'organization_id' => $oakResidences->id,
    ]);
    $pineOwner = User::factory()->admin()->create([
        'name' => 'Pine Owner',
        'organization_id' => $pineTower->id,
    ]);

    $oakResidences->forceFill(['owner_user_id' => $oakOwner->id])->save();
    $pineTower->forceFill(['owner_user_id' => $pineOwner->id])->save();

    Subscription::factory()->active()->create([
        'organization_id' => $oakResidences->id,
        'plan' => SubscriptionPlan::PROFESSIONAL,
        'plan_name_snapshot' => SubscriptionPlan::PROFESSIONAL->label(),
        'limits_snapshot' => SubscriptionPlan::PROFESSIONAL->limitsSnapshot(),
        'expires_at' => now()->addMonths(2),
    ]);
    Subscription::factory()->create([
        'organization_id' => $pineTower->id,
        'plan' => SubscriptionPlan::BASIC,
        'plan_name_snapshot' => SubscriptionPlan::BASIC->label(),
        'limits_snapshot' => SubscriptionPlan::BASIC->limitsSnapshot(),
        'status' => SubscriptionStatus::SUSPENDED,
        'is_trial' => false,
        'expires_at' => now()->addMonth(),
    ]);

    $this->actingAs($superadmin);

    Livewire::test(ListOrganizations::class)
        ->assertCanSeeTableRecords([$oakResidences, $pineTower])
        ->assertTableColumnExists('name')
        ->assertTableColumnExists('status')
        ->assertTableColumnExists('owner.name')
        ->assertTableColumnExists('currentSubscription.plan_name_snapshot')
        ->assertTableFilterExists('status')
        ->assertTableFilterExists('plan')
        ->assertTableFilterExists('owner');
});

it('creates an organization with an invited owner and subscription snapshot', function () {
    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($superadmin);

    Livewire::test(CreateOrganization::class)
        ->fillForm([
            'name' => 'Willow Court',
            'slug' => null,
            'owner_name' => 'Willow Owner',
            'owner_email' => 'willow.owner@example.test',
            'plan' => SubscriptionPlan::PROFESSIONAL->value,
            'duration' => SubscriptionDuration::YEARLY->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $organization = Organization::query()
        ->where('slug', 'willow-court')
        ->firstOrFail();

    expect($organization->owner_user_id)->toBeNull();

    $invitation = OrganizationInvitation::query()
        ->where('organization_id', $organization->id)
        ->where('email', 'willow.owner@example.test')
        ->first();

    expect($invitation)->not->toBeNull();

    $subscription = Subscription::query()
        ->where('organization_id', $organization->id)
        ->firstOrFail();

    expect($subscription->plan)->toBe(SubscriptionPlan::PROFESSIONAL)
        ->and($subscription->plan_name_snapshot)->toBe('Professional')
        ->and($subscription->limits_snapshot)->toBe(SubscriptionPlan::PROFESSIONAL->limitsSnapshot())
        ->and($subscription->status)->toBe(SubscriptionStatus::ACTIVE)
        ->and($subscription->expires_at?->isFuture())->toBeTrue();
});

it('reuses an existing unassigned admin account when the owner email already exists', function () {
    $superadmin = User::factory()->superadmin()->create();
    $existingOwner = User::factory()->admin()->create([
        'name' => 'Existing Owner',
        'email' => 'existing.owner@example.test',
        'organization_id' => null,
    ]);

    $this->actingAs($superadmin);

    Livewire::test(CreateOrganization::class)
        ->fillForm([
            'name' => 'Spruce Point',
            'slug' => null,
            'owner_name' => 'Existing Owner',
            'owner_email' => $existingOwner->email,
            'plan' => SubscriptionPlan::BASIC->value,
            'duration' => SubscriptionDuration::MONTHLY->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $organization = Organization::query()
        ->where('slug', 'spruce-point')
        ->firstOrFail();

    expect($existingOwner->refresh()->organization_id)->toBe($organization->id)
        ->and($organization->owner_user_id)->toBe($existingOwner->id);

    expect(OrganizationInvitation::query()->where('email', $existingOwner->email)->exists())->toBeFalse();
});

it('rejects creating an organization with an owner email that already belongs to another organization', function () {
    $superadmin = User::factory()->superadmin()->create();
    User::factory()->admin()->create([
        'email' => 'taken.owner@example.test',
        'organization_id' => Organization::factory(),
    ]);

    $this->actingAs($superadmin);

    Livewire::test(CreateOrganization::class)
        ->fillForm([
            'name' => 'Harbor Heights',
            'slug' => null,
            'owner_name' => 'Taken Owner',
            'owner_email' => 'taken.owner@example.test',
            'plan' => SubscriptionPlan::ENTERPRISE->value,
            'duration' => SubscriptionDuration::YEARLY->value,
        ])
        ->call('create')
        ->assertHasFormErrors(['owner_email']);
});
