<?php

use App\Enums\OrganizationStatus;
use App\Enums\PlatformNotificationSeverity;
use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Filament\Resources\Organizations\Pages\ViewOrganization;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\PlatformNotification;
use App\Models\PlatformNotificationDelivery;
use App\Models\Subscription;
use App\Models\User;
use App\Support\Auth\ImpersonationManager;
use App\Support\Superadmin\Exports\NullOrganizationDataExportBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('shows governance actions on the organization view page', function () {
    $superadmin = User::factory()->superadmin()->create();
    $organization = Organization::factory()->create();

    $this->actingAs($superadmin);

    Livewire::test(ViewOrganization::class, ['record' => $organization->getRouteKey()])
        ->assertActionVisible('suspend')
        ->assertActionHidden('reinstate')
        ->assertActionVisible('impersonate')
        ->assertActionVisible('sendNotification')
        ->assertActionVisible('exportData');
});

it('suspends and reinstates an organization from the view page', function () {
    $superadmin = User::factory()->superadmin()->create();
    $organization = Organization::factory()->create([
        'status' => OrganizationStatus::ACTIVE,
    ]);

    Subscription::factory()->active()->create([
        'organization_id' => $organization->id,
        'plan' => SubscriptionPlan::PROFESSIONAL,
        'plan_name_snapshot' => SubscriptionPlan::PROFESSIONAL->label(),
        'limits_snapshot' => SubscriptionPlan::PROFESSIONAL->limitsSnapshot(),
    ]);

    $this->actingAs($superadmin);

    Livewire::test(ViewOrganization::class, ['record' => $organization->getRouteKey()])
        ->mountAction('suspend')
        ->assertMountedActionModalSee('Suspend organization')
        ->callMountedAction();

    expect($organization->refresh()->status)->toBe(OrganizationStatus::SUSPENDED)
        ->and($organization->subscriptions()->firstOrFail()->refresh()->status)->toBe(SubscriptionStatus::SUSPENDED);

    Livewire::test(ViewOrganization::class, ['record' => $organization->getRouteKey()])
        ->mountAction('reinstate')
        ->assertMountedActionModalSee('Reinstate organization')
        ->callMountedAction();

    expect($organization->refresh()->status)->toBe(OrganizationStatus::ACTIVE)
        ->and($organization->subscriptions()->firstOrFail()->refresh()->status)->toBe(SubscriptionStatus::ACTIVE)
        ->and(AuditLog::query()->count())->toBeGreaterThanOrEqual(2);
});

it('starts impersonation with the organization owner account', function () {
    $superadmin = User::factory()->superadmin()->create();
    $owner = User::factory()->admin()->create();
    $organization = Organization::factory()->create([
        'owner_user_id' => $owner->id,
    ]);

    $owner->forceFill([
        'organization_id' => $organization->id,
    ])->save();

    $this->actingAs($superadmin);

    Livewire::test(ViewOrganization::class, ['record' => $organization->getRouteKey()])
        ->callAction('impersonate');

    $this->assertAuthenticatedAs($owner);
    expect(session(ImpersonationManager::IMPERSONATOR_ID))->toBe($superadmin->id)
        ->and(session(ImpersonationManager::IMPERSONATOR_EMAIL))->toBe($superadmin->email)
        ->and(session(ImpersonationManager::IMPERSONATOR_NAME))->toBe($superadmin->name);
});

it('sends a platform notification to organization users from the view page', function () {
    $superadmin = User::factory()->superadmin()->create();
    $organization = Organization::factory()->create();
    $owner = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);
    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    $organization->forceFill([
        'owner_user_id' => $owner->id,
    ])->save();

    $this->actingAs($superadmin);

    Livewire::test(ViewOrganization::class, ['record' => $organization->getRouteKey()])
        ->mountAction('sendNotification')
        ->assertMountedActionModalSee('Send organization notification')
        ->setActionData([
            'title' => 'Scheduled maintenance',
            'body' => 'Meter imports will pause tonight.',
            'severity' => PlatformNotificationSeverity::WARNING->value,
        ])
        ->callMountedAction()
        ->assertHasNoFormErrors();

    $notification = PlatformNotification::query()->first();

    expect($notification)->not->toBeNull()
        ->and($notification?->title)->toBe('Scheduled maintenance')
        ->and($notification?->deliveries()->count())->toBe(2);

    expect(PlatformNotificationDelivery::query()->count())->toBe(2)
        ->and($owner->refresh()->notifications()->count())->toBe(1)
        ->and($manager->refresh()->notifications()->count())->toBe(1);
});

it('exports an organization zip through the null export builder', function () {
    $superadmin = User::factory()->superadmin()->create();
    $organization = Organization::factory()->create([
        'name' => 'Atlas Square',
        'slug' => 'atlas-square',
    ]);

    $this->actingAs($superadmin);

    $builder = app(NullOrganizationDataExportBuilder::class);
    $export = $builder->build($organization);

    expect($export['path'])->toBeFile()
        ->and($export['download_name'])->toBe('atlas-square-export.zip');
});
