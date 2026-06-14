<?php

declare(strict_types=1);

use App\Enums\ManagerMembershipStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Actions\Admin\OrganizationUsers\CreateManagerInvitationLinkAction;
use App\Filament\Actions\Admin\OrganizationUsers\CreateOrganizationManagerAction;
use App\Filament\Actions\Admin\OrganizationUsers\ResendManagerInvitationAction;
use App\Filament\Actions\Admin\OrganizationUsers\RevokeManagerInvitationAction;
use App\Filament\Actions\Admin\OrganizationUsers\ToggleManagerStatusAction;
use App\Filament\Actions\Auth\CreateOrganizationInvitationAction;
use App\Filament\Support\Admin\ManagerPermissions\ManagerPermissionService;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\OrganizationUser;
use App\Models\User;
use App\Notifications\Admin\ManagerInvitationAcceptedNotification;
use App\Notifications\Auth\OrganizationInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    adminManagerRegisterInvitationRoutes();
});

it('lets an organization admin invite a manager to their own organization', function (): void {
    Notification::fake();

    ['organization' => $organization, 'admin' => $admin] = createOrgWithAdmin();

    $membership = adminManagerInvite($organization, $admin, [
        'permissions_preset' => 'property_manager',
    ]);

    $manager = $membership->user;
    $invitation = adminManagerLatestInvitation($membership);

    expect($manager)->not->toBeNull()
        ->and($manager->organization_id)->toBe($organization->id)
        ->and($manager->role)->toBe(UserRole::MANAGER)
        ->and($manager->status)->toBe(UserStatus::INACTIVE)
        ->and($membership->organization_id)->toBe($organization->id)
        ->and($membership->role)->toBe(UserRole::MANAGER->value)
        ->and($membership->status)->toBe(ManagerMembershipStatus::INVITED)
        ->and($membership->is_active)->toBeFalse()
        ->and($membership->permissions_preset)->toBe('property_manager')
        ->and($membership->invited_by_user_id)->toBe($admin->id)
        ->and($invitation->token)->toBe($invitation->token_hash)
        ->and(OrganizationInvitation::isHashedToken($invitation->token))->toBeTrue();

    Notification::assertSentOnDemand(OrganizationInvitationNotification::class);

    adminManagerAssertAudit($organization, 'manager.invited');
});

it('blocks cross-organization manager invites and privileged role creation', function (): void {
    ['organization' => $organization, 'admin' => $admin] = createOrgWithAdmin();
    $otherOrganization = Organization::factory()->create();

    expect(fn () => adminManagerInvite($otherOrganization, $admin))
        ->toThrow(HttpException::class);

    expect(fn () => adminManagerInvite($organization, $admin, [
        'email' => $admin->email,
    ]))->toThrow(ValidationException::class);

    expect(fn () => app(CreateOrganizationInvitationAction::class)->handle($admin, [
        'email' => 'superadmin-invite@example.com',
        'role' => UserRole::SUPERADMIN,
        'full_name' => 'Bad Invite',
    ]))->toThrow(ValidationException::class);

    expect(fn () => app(CreateOrganizationInvitationAction::class)->handle($admin, [
        'email' => 'admin-invite@example.com',
        'role' => UserRole::ADMIN,
        'full_name' => 'Bad Invite',
    ]))->toThrow(ValidationException::class);
});

it('accepts a manager invitation once and activates the organization membership', function (): void {
    Notification::fake();

    ['organization' => $organization, 'admin' => $admin] = createOrgWithAdmin();
    $membership = adminManagerInvite($organization, $admin, [
        'send_invitation_email' => false,
    ]);
    $invitation = app(CreateManagerInvitationLinkAction::class)->handle($membership, $admin);
    $token = $invitation->routeToken();

    $this->post(route('invitation.store', $token), [
        'name' => 'Operations Manager',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertRedirect(route('filament.admin.pages.dashboard'));

    $manager = User::query()->where('email', 'operations.manager@example.com')->firstOrFail();
    $membership = $membership->fresh();

    expect($manager->status)->toBe(UserStatus::ACTIVE)
        ->and($membership->status)->toBe(ManagerMembershipStatus::ACTIVE)
        ->and($membership->is_active)->toBeTrue()
        ->and($membership->accepted_at)->not->toBeNull()
        ->and($invitation->fresh()->accepted_at)->not->toBeNull();

    Notification::assertSentTo($admin, ManagerInvitationAcceptedNotification::class);
    adminManagerAssertAudit($organization, 'manager.invitation_accepted');

    auth()->logout();

    $this->post(route('invitation.store', $token), [
        'name' => 'Operations Manager',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertRedirect(route('invitation.show', $token));
});

it('does not expose stored invitation hashes as usable tokens', function (): void {
    ['organization' => $organization, 'admin' => $admin] = createOrgWithAdmin();
    $membership = adminManagerInvite($organization, $admin, [
        'send_invitation_email' => false,
    ]);
    $invitation = app(CreateManagerInvitationLinkAction::class)->handle($membership, $admin);

    expect($invitation->routeToken())->not->toBe($invitation->token)
        ->and($invitation->routeToken())->not->toBe($invitation->token_hash);

    $this->get(route('invitation.show', $invitation->token))
        ->assertSuccessful()
        ->assertSeeText(__('auth.invitation_expired'));
});

it('rejects expired and revoked manager invitations', function (string $state): void {
    ['organization' => $organization, 'admin' => $admin] = createOrgWithAdmin();
    $membership = adminManagerInvite($organization, $admin, [
        'email' => "{$state}.manager@example.com",
        'send_invitation_email' => false,
    ]);
    $invitation = app(CreateManagerInvitationLinkAction::class)->handle($membership, $admin);
    $token = $invitation->routeToken();

    $invitation->forceFill(
        $state === 'expired'
            ? ['expires_at' => now()->subMinute()]
            : ['revoked_at' => now()],
    )->save();

    $this->post(route('invitation.store', $token), [
        'name' => 'Rejected Manager',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertRedirect(route('invitation.show', $token));

    expect($invitation->fresh()->accepted_at)->toBeNull()
        ->and(User::query()->where('email', "{$state}.manager@example.com")->first()?->status)->toBe(UserStatus::INACTIVE);
})->with(['expired', 'revoked']);

it('keeps managers isolated to their own organization and blocks disabled manager access', function (): void {
    ['organization' => $organization, 'admin' => $admin] = createOrgWithAdmin();
    ['organization' => $otherOrganization] = createOrgWithAdmin();
    $membership = adminManagerInvite($organization, $admin, [
        'send_invitation_email' => false,
    ]);
    $manager = $membership->user;

    $membership->forceFill([
        'status' => ManagerMembershipStatus::ACTIVE,
        'is_active' => true,
        'accepted_at' => now(),
    ])->save();
    $manager->forceFill([
        'status' => UserStatus::ACTIVE,
    ])->save();

    $service = app(ManagerPermissionService::class);

    expect($service->isManagerForOrganization($manager->fresh(), $organization))->toBeTrue()
        ->and($service->isManagerForOrganization($manager->fresh(), $otherOrganization))->toBeFalse();

    app(ToggleManagerStatusAction::class)->disable($membership, $admin);

    expect($service->isManagerForOrganization($manager->fresh(), $organization))->toBeFalse()
        ->and($membership->fresh()->status)->toBe(ManagerMembershipStatus::DISABLED)
        ->and($manager->fresh()->status)->toBe(UserStatus::SUSPENDED);
});

it('blocks tenants from team management and managers from editing admins', function (): void {
    ['organization' => $organization, 'admin' => $admin] = createOrgWithAdmin();
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);
    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
        'status' => UserStatus::ACTIVE,
    ]);
    $managerMembership = OrganizationUser::factory()->create([
        'organization_id' => $organization->id,
        'user_id' => $manager->id,
        'role' => UserRole::MANAGER->value,
        'status' => ManagerMembershipStatus::ACTIVE,
        'is_active' => true,
    ]);
    $adminMembership = OrganizationUser::factory()->create([
        'organization_id' => $organization->id,
        'user_id' => $admin->id,
        'role' => UserRole::ADMIN->value,
        'status' => ManagerMembershipStatus::ACTIVE,
        'is_active' => true,
    ]);

    $this->actingAs($tenant)
        ->get(route('filament.admin.resources.organization-users.index'))
        ->assertForbidden();

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.organization-users.edit', ['record' => $adminMembership]))
        ->assertNotFound();

    expect($managerMembership->fresh()->isActiveMembership())->toBeTrue();
});

it('audits manager lifecycle actions and forbidden manager access attempts', function (): void {
    Notification::fake();

    ['organization' => $organization, 'admin' => $admin] = createOrgWithAdmin();
    $membership = adminManagerInvite($organization, $admin, [
        'send_invitation_email' => false,
    ]);

    app(ResendManagerInvitationAction::class)->handle($membership, $admin);
    app(RevokeManagerInvitationAction::class)->handle($membership->fresh(), $admin);

    $membership->forceFill([
        'status' => ManagerMembershipStatus::ACTIVE,
        'is_active' => true,
        'left_at' => null,
        'accepted_at' => now(),
    ])->save();
    $membership->user->forceFill([
        'status' => UserStatus::ACTIVE,
    ])->save();

    expect(app(ManagerPermissionService::class)->isManagerForOrganization($membership->user->fresh(), $organization))
        ->toBeTrue();

    $this->actingAs($membership->user)
        ->getJson(route('test.admin-manager-denied-action'))
        ->assertForbidden();

    app(ToggleManagerStatusAction::class)->disable($membership->fresh(), $admin);
    app(ToggleManagerStatusAction::class)->reactivate($membership->fresh(), $admin);
    app(ManagerPermissionService::class)->saveMatrix(
        $membership->user,
        $organization,
        ['buildings' => ['can_create' => true]],
        $admin,
    );

    adminManagerAssertAudit($organization, 'manager.invitation_resent');
    adminManagerAssertAudit($organization, 'manager.invitation_revoked');
    adminManagerAssertAudit($organization, 'manager.disabled');
    adminManagerAssertAudit($organization, 'manager.reactivated');
    adminManagerAssertAudit($organization, 'manager_permissions.updated');
    adminManagerAssertAudit($organization, 'manager.forbidden_access_attempt');
});

function adminManagerRegisterInvitationRoutes(): void
{
    if (! Route::has('filament.admin.pages.dashboard')) {
        Route::get('/app', fn (): string => 'dashboard')
            ->name('filament.admin.pages.dashboard');
    }

    if (! Route::has('test.admin-manager-denied-action')) {
        Route::middleware(['web', 'auth', 'manager.permission:buildings,create'])
            ->get('/__test/admin-manager-denied-action', fn (): string => 'allowed')
            ->name('test.admin-manager-denied-action');
    }

    app('router')->getRoutes()->refreshNameLookups();
    app('router')->getRoutes()->refreshActionLookups();
}

/**
 * @param  array<string, mixed>  $overrides
 */
function adminManagerInvite(Organization $organization, User $admin, array $overrides = []): OrganizationUser
{
    return app(CreateOrganizationManagerAction::class)->handle($organization, $admin, [
        'name' => 'Operations Manager',
        'email' => 'operations.manager@example.com',
        'locale' => 'en',
        'permissions_preset' => 'read_only',
        'send_invitation_email' => true,
        ...$overrides,
    ]);
}

function adminManagerLatestInvitation(OrganizationUser $membership): OrganizationInvitation
{
    $membership->loadMissing('user');

    return OrganizationInvitation::query()
        ->where('organization_id', $membership->organization_id)
        ->where('email', $membership->user->email)
        ->where('role', UserRole::MANAGER)
        ->latest('id')
        ->firstOrFail();
}

function adminManagerAssertAudit(Organization $organization, string $mutation): void
{
    $auditLog = AuditLog::query()
        ->where('organization_id', $organization->id)
        ->latest('id')
        ->get()
        ->first(fn (AuditLog $log): bool => data_get($log->metadata, 'context.mutation') === $mutation);

    expect($auditLog)->not->toBeNull();
}
