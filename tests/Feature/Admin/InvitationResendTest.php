<?php

use App\Actions\Auth\ResendOrganizationInvitationAction;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Notifications\Auth\OrganizationInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('resends a fresh invitation for an inactive tenant account', function () {
    Notification::fake();

    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->for($organization)->create();
    $tenant = User::factory()->tenant()->for($organization)->create([
        'email' => 'tenant@example.com',
        'status' => UserStatus::INACTIVE,
    ]);

    $invitation = OrganizationInvitation::factory()->create([
        'organization_id' => $organization->id,
        'inviter_user_id' => $admin->id,
        'email' => $tenant->email,
        'role' => UserRole::TENANT,
        'full_name' => $tenant->name,
        'accepted_at' => null,
        'expires_at' => now()->subDay(),
    ]);

    $resentInvitation = app(ResendOrganizationInvitationAction::class)
        ->handle($admin, $invitation);

    expect($resentInvitation->is($invitation))->toBeFalse()
        ->and($resentInvitation->email)->toBe($invitation->email)
        ->and($resentInvitation->role)->toBe(UserRole::TENANT)
        ->and($resentInvitation->token)->not->toBe($invitation->token)
        ->and($resentInvitation->expires_at->isFuture())->toBeTrue()
        ->and($invitation->fresh()->accepted_at)->toBeNull();

    Notification::assertSentOnDemand(
        OrganizationInvitationNotification::class,
        fn (OrganizationInvitationNotification $notification, array $channels, object $notifiable): bool => in_array('mail', $channels, true)
            && ($notifiable->routes['mail'] ?? null) === $tenant->email
            && $notification->invitation->is($resentInvitation),
    );
});

it('resends a fresh invitation for an inactive manager invite', function () {
    Notification::fake();

    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->for($organization)->create();

    $invitation = OrganizationInvitation::factory()->create([
        'organization_id' => $organization->id,
        'inviter_user_id' => $admin->id,
        'email' => 'manager@example.com',
        'role' => UserRole::MANAGER,
        'full_name' => 'Marta Manager',
        'accepted_at' => null,
        'expires_at' => now()->subDay(),
    ]);

    $resentInvitation = app(ResendOrganizationInvitationAction::class)
        ->handle($admin, $invitation);

    expect($resentInvitation->is($invitation))->toBeFalse()
        ->and($resentInvitation->role)->toBe(UserRole::MANAGER)
        ->and($resentInvitation->token)->not->toBe($invitation->token)
        ->and($resentInvitation->expires_at->isFuture())->toBeTrue();

    Notification::assertSentOnDemand(OrganizationInvitationNotification::class);
});

it('does not allow resending an invitation for an already active tenant account', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->for($organization)->create();
    $tenant = User::factory()->tenant()->for($organization)->create([
        'email' => 'active-tenant@example.com',
        'status' => UserStatus::ACTIVE,
    ]);

    $invitation = OrganizationInvitation::factory()->create([
        'organization_id' => $organization->id,
        'inviter_user_id' => $admin->id,
        'email' => $tenant->email,
        'role' => UserRole::TENANT,
        'accepted_at' => null,
        'expires_at' => now()->subDay(),
    ]);

    expect(fn () => app(ResendOrganizationInvitationAction::class)
        ->handle($admin, $invitation))
        ->toThrow(ValidationException::class);
});

it('shows the resend invitation action only for inactive tenants', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->for($organization)->create();

    $inactiveTenant = User::factory()->tenant()->for($organization)->create([
        'status' => UserStatus::INACTIVE,
        'email' => 'inactive@example.com',
    ]);

    OrganizationInvitation::factory()->create([
        'organization_id' => $organization->id,
        'inviter_user_id' => $admin->id,
        'email' => $inactiveTenant->email,
        'role' => UserRole::TENANT,
        'accepted_at' => null,
        'expires_at' => now()->subDay(),
    ]);

    $activeTenant = User::factory()->tenant()->for($organization)->create([
        'status' => UserStatus::ACTIVE,
        'email' => 'active@example.com',
    ]);

    OrganizationInvitation::factory()->create([
        'organization_id' => $organization->id,
        'inviter_user_id' => $admin->id,
        'email' => $activeTenant->email,
        'role' => UserRole::TENANT,
        'accepted_at' => now(),
        'expires_at' => now()->addDay(),
    ]);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.tenants.view', $inactiveTenant))
        ->assertSuccessful()
        ->assertSeeText('Resend Invitation');

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.tenants.view', $activeTenant))
        ->assertSuccessful()
        ->assertDontSeeText('Resend Invitation');
});
