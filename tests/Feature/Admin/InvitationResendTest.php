<?php

use App\Actions\Auth\ResendOrganizationInvitationAction;
use App\Enums\UserRole;
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

    $invitation = OrganizationInvitation::factory()->create([
        'organization_id' => $organization->id,
        'inviter_user_id' => $admin->id,
        'role' => UserRole::TENANT,
        'accepted_at' => null,
        'expires_at' => now()->subDay(),
    ]);

    $resentInvitation = app(ResendOrganizationInvitationAction::class)
        ->handle($admin, $invitation);

    expect($resentInvitation->is($invitation))->toBeFalse()
        ->and($resentInvitation->token)->not->toBe($invitation->token)
        ->and($resentInvitation->expires_at->isFuture())->toBeTrue()
        ->and($resentInvitation->email)->toBe($invitation->email)
        ->and($resentInvitation->role)->toBe($invitation->role);

    Notification::assertSentOnDemand(
        OrganizationInvitationNotification::class,
        function (OrganizationInvitationNotification $notification, array $channels, object $notifiable) use ($resentInvitation): bool {
            return in_array('mail', $channels, true)
                && ($notifiable->routes['mail'] ?? null) === $resentInvitation->email
                && $notification->invitation->is($resentInvitation);
        },
    );
});

it('invalidates the previous invitation token when a new invitation is resent', function () {
    Notification::fake();

    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->for($organization)->create();

    $invitation = OrganizationInvitation::factory()->create([
        'organization_id' => $organization->id,
        'inviter_user_id' => $admin->id,
        'role' => UserRole::MANAGER,
    ]);

    $resentInvitation = app(ResendOrganizationInvitationAction::class)
        ->handle($admin, $invitation);

    expect($invitation->fresh()->isPending())->toBeFalse()
        ->and($resentInvitation->isPending())->toBeTrue();
});

it('rejects resending an invitation after the account has already been activated', function () {
    Notification::fake();

    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->for($organization)->create();

    $invitation = OrganizationInvitation::factory()->create([
        'organization_id' => $organization->id,
        'inviter_user_id' => $admin->id,
        'email' => 'tenant@example.com',
        'role' => UserRole::TENANT,
        'accepted_at' => now(),
    ]);

    User::factory()->tenant()->for($organization)->create([
        'email' => $invitation->email,
    ]);

    expect(fn () => app(ResendOrganizationInvitationAction::class)->handle($admin, $invitation))
        ->toThrow(ValidationException::class);

    Notification::assertNothingSent();
});
