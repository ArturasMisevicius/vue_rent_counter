<?php

use App\Enums\OrganizationStatus;
use App\Enums\SubscriptionDuration;
use App\Enums\SubscriptionPlan;
use App\Filament\Actions\Superadmin\Organizations\CreateOrganizationAction;
use App\Filament\Actions\Superadmin\Organizations\ExportOrganizationDataAction;
use App\Filament\Actions\Superadmin\Organizations\ReinstateOrganizationAction;
use App\Filament\Actions\Superadmin\Organizations\StartOrganizationImpersonationAction;
use App\Filament\Actions\Superadmin\Organizations\SuspendOrganizationAction;
use App\Filament\Actions\Superadmin\Organizations\UpdateOrganizationAction;
use App\Models\Organization;
use App\Models\PlatformOrganizationInvitation;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('creates organizations with existing owners and subscription snapshots', function () {
    $superadmin = User::factory()->superadmin()->create();
    $availableOwner = User::factory()->admin()->create([
        'organization_id' => null,
        'email' => 'owner@example.com',
        'name' => 'Olivia Owner',
    ]);

    $organization = app(CreateOrganizationAction::class)->handle($superadmin, [
        'name' => 'Aurora Estates',
        'owner_email' => $availableOwner->email,
        'owner_name' => $availableOwner->name,
        'plan' => SubscriptionPlan::PROFESSIONAL,
        'duration' => SubscriptionDuration::QUARTERLY,
    ]);

    $subscription = Subscription::query()
        ->where('organization_id', $organization->id)
        ->firstOrFail();

    expect($organization->slug)->toBe('aurora-estates')
        ->and($organization->owner_user_id)->toBe($availableOwner->id)
        ->and($availableOwner->fresh()->organization_id)->toBe($organization->id)
        ->and($subscription->plan)->toBe(SubscriptionPlan::PROFESSIONAL)
        ->and($subscription->property_limit_snapshot)->toBe(SubscriptionPlan::PROFESSIONAL->limits()['properties'])
        ->and($subscription->tenant_limit_snapshot)->toBe(SubscriptionPlan::PROFESSIONAL->limits()['tenants']);
});

it('creates platform invitations for new owner emails and blocks ownership theft', function () {
    $superadmin = User::factory()->superadmin()->create();

    $organization = app(CreateOrganizationAction::class)->handle($superadmin, [
        'name' => 'Harbor Heights',
        'owner_email' => 'invite.owner@example.com',
        'owner_name' => 'Ivy Invite',
        'plan' => SubscriptionPlan::BASIC,
        'duration' => SubscriptionDuration::MONTHLY,
    ]);

    $invitation = PlatformOrganizationInvitation::query()
        ->where('organization_name', $organization->name)
        ->where('admin_email', 'invite.owner@example.com')
        ->first();

    expect($organization->owner_user_id)->toBeNull()
        ->and($invitation)->not->toBeNull()
        ->and($invitation?->invited_by)->toBe($superadmin->id);

    $claimedOwner = User::factory()->admin()->create([
        'organization_id' => Organization::factory()->create()->id,
        'email' => 'claimed.owner@example.com',
    ]);

    expect(fn () => app(CreateOrganizationAction::class)->handle($superadmin, [
        'name' => 'Claimed Harbor',
        'owner_email' => $claimedOwner->email,
        'owner_name' => $claimedOwner->name,
        'plan' => SubscriptionPlan::BASIC,
        'duration' => SubscriptionDuration::MONTHLY,
    ]))->toThrow(ValidationException::class);
});

it('rejects disposable owner emails when creating organizations', function () {
    $superadmin = User::factory()->superadmin()->create();

    expect(fn () => app(CreateOrganizationAction::class)->handle($superadmin, [
        'name' => 'Disposable Harbor',
        'owner_email' => 'owner@10minutemail.com',
        'owner_name' => 'Disposable Owner',
        'plan' => SubscriptionPlan::BASIC,
        'duration' => SubscriptionDuration::MONTHLY,
    ]))->toThrow(ValidationException::class);

    expect(Organization::query()->count())->toBe(0)
        ->and(PlatformOrganizationInvitation::query()->count())->toBe(0);
});

it('rejects disposable owner emails when updating organizations', function () {
    $organization = Organization::factory()->create([
        'name' => 'Northwind Towers',
    ]);

    expect(fn () => app(UpdateOrganizationAction::class)->handle($organization, [
        'name' => 'Northwind Towers Updated',
        'owner_email' => 'updated-owner@10minutemail.com',
        'owner_name' => 'Disposable Owner',
        'plan' => SubscriptionPlan::PROFESSIONAL,
    ]))->toThrow(ValidationException::class);

    expect($organization->fresh()?->name)->toBe('Northwind Towers');
});

it('suspends reinstates notifies impersonates and exports organizations', function () {
    $superadmin = User::factory()->superadmin()->create();
    $organization = Organization::factory()->create([
        'name' => 'Northwind Towers',
    ]);

    $organizationAdmin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
        'email' => 'admin@northwind.test',
    ]);

    $organization->forceFill([
        'owner_user_id' => $organizationAdmin->id,
    ])->save();

    User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    Subscription::factory()->for($organization)->active()->create();

    $suspended = app(SuspendOrganizationAction::class)->handle($organization);
    $reinstated = app(ReinstateOrganizationAction::class)->handle($suspended->fresh());

    expect($suspended->status)->toBe(OrganizationStatus::SUSPENDED)
        ->and($reinstated->status)->toBe(OrganizationStatus::ACTIVE);

    $this->actingAs($superadmin);

    app(StartOrganizationImpersonationAction::class)->handle($superadmin, $organizationAdmin);

    $this->assertAuthenticatedAs($organizationAdmin);

    expect(session('impersonator_id'))->toBe($superadmin->id)
        ->and(session('impersonator_email'))->toBe($superadmin->email);

    $zipPath = app(ExportOrganizationDataAction::class)->handle($organization->fresh());

    $archive = new ZipArchive;

    expect($archive->open($zipPath))->toBeTrue()
        ->and($archive->numFiles)->toBeGreaterThan(0);

    $archive->close();
    @unlink($zipPath);
});
