<?php

use App\Actions\Auth\CreateOrganizationInvitationAction;
use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Notifications\Auth\OrganizationInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function registerInvitationDestinationFixtures(): void
{
    if (! Route::has('filament.admin.pages.organization-dashboard')) {
        Route::get('/__test/organization-dashboard', fn () => 'organization dashboard')
            ->name('filament.admin.pages.organization-dashboard');
    }

    if (! Route::has('tenant.home')) {
        Route::get('/__test/tenant-home', fn () => 'tenant home')->name('tenant.home');
    }

    app('router')->getRoutes()->refreshNameLookups();
    app('router')->getRoutes()->refreshActionLookups();
}

beforeEach(function (): void {
    registerInvitationDestinationFixtures();
});

it('creates and emails a pending invitation', function () {
    Notification::fake();

    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $organization->update([
        'owner_user_id' => $admin->id,
    ]);

    $invitation = app(CreateOrganizationInvitationAction::class)->handle($admin, [
        'email' => 'manager@example.com',
        'role' => UserRole::MANAGER,
        'full_name' => 'Marta Manager',
    ]);

    expect($invitation->organization_id)->toBe($organization->id)
        ->and($invitation->inviter_user_id)->toBe($admin->id)
        ->and($invitation->role)->toBe(UserRole::MANAGER)
        ->and($invitation->accepted_at)->toBeNull();

    Notification::assertSentOnDemand(
        OrganizationInvitationNotification::class,
        function (OrganizationInvitationNotification $notification, array $channels, object $notifiable) use ($invitation): bool {
            return in_array('mail', $channels, true)
                && ($notifiable->routes['mail'] ?? null) === $invitation->email
                && $notification->invitation->is($invitation);
        },
    );
});

it('renders the invitation acceptance page', function () {
    $organization = Organization::factory()->create([
        'name' => 'North Hall',
    ]);

    $invitation = OrganizationInvitation::factory()->create([
        'organization_id' => $organization->id,
        'full_name' => 'Marta Manager',
    ]);

    $this->get(route('invitation.show', $invitation->token))
        ->assertSuccessful()
        ->assertSeeText('You have been invited to join Tenanto by North Hall.')
        ->assertSeeText('Full Name')
        ->assertSeeText('Accept Invitation and Create Account')
        ->assertSee('value="Marta Manager"', false);
});

it('accepts a valid invitation and logs the user in', function (UserRole $role, string $expectedRoute) {
    $organization = Organization::factory()->create();
    $invitation = OrganizationInvitation::factory()->create([
        'organization_id' => $organization->id,
        'email' => match ($role) {
            UserRole::MANAGER => 'manager@example.com',
            UserRole::TENANT => 'tenant@example.com',
            default => 'invited@example.com',
        },
        'full_name' => 'Invited User',
        'role' => $role,
    ]);

    $this->post(route('invitation.store', $invitation->token), [
        'name' => 'Invited User',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertRedirect(route($expectedRoute));

    $user = User::query()->where('email', $invitation->email)->firstOrFail();

    $this->assertAuthenticatedAs($user);

    expect($user->name)->toBe('Invited User')
        ->and($user->role)->toBe($role)
        ->and($user->organization_id)->toBe($organization->id)
        ->and($invitation->fresh()->accepted_at)->not->toBeNull();
})->with([
    'manager' => [UserRole::MANAGER, 'filament.admin.pages.organization-dashboard'],
    'tenant' => [UserRole::TENANT, 'tenant.home'],
]);

it('shows the expired state for an expired invitation', function () {
    $invitation = OrganizationInvitation::factory()->create([
        'expires_at' => now()->subDay(),
    ]);

    $this->get(route('invitation.show', $invitation->token))
        ->assertSuccessful()
        ->assertSeeText('This invitation has expired. Please contact your administrator for a new invitation.');
});

it('rejects an already accepted invitation', function () {
    $invitation = OrganizationInvitation::factory()->create([
        'accepted_at' => now(),
    ]);

    $this->post(route('invitation.store', $invitation->token), [
        'name' => 'Used Invitation',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertRedirect(route('invitation.show', $invitation->token));

    expect(User::query()->where('email', $invitation->email)->exists())->toBeFalse();
});

it('rejects invitation creation for an existing user email', function () {
    Notification::fake();

    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $organization->update([
        'owner_user_id' => $admin->id,
    ]);

    User::factory()->create([
        'email' => 'tenant@example.com',
    ]);

    expect(fn () => app(CreateOrganizationInvitationAction::class)->handle($admin, [
        'email' => 'tenant@example.com',
        'role' => UserRole::TENANT,
        'full_name' => 'Existing Tenant',
    ]))->toThrow(ValidationException::class);

    expect(OrganizationInvitation::query()->count())->toBe(0);

    Notification::assertNothingSent();
});

it('rejects invitation creation when a pending invitation already exists for the email', function () {
    Notification::fake();

    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $organization->update([
        'owner_user_id' => $admin->id,
    ]);

    OrganizationInvitation::factory()->create([
        'organization_id' => $organization->id,
        'email' => 'pending@example.com',
        'accepted_at' => null,
        'expires_at' => now()->addDay(),
    ]);

    expect(fn () => app(CreateOrganizationInvitationAction::class)->handle($admin, [
        'email' => 'pending@example.com',
        'role' => UserRole::TENANT,
        'full_name' => 'Pending Tenant',
    ]))->toThrow(ValidationException::class);

    expect(OrganizationInvitation::query()->count())->toBe(1);

    Notification::assertNothingSent();
});
