<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

function registerInvitationLocaleDestinationFixtures(): void
{
    if (! Route::has('filament.admin.pages.dashboard')) {
        Route::get('/app', fn () => 'dashboard')
            ->name('filament.admin.pages.dashboard');
    }

    if (! Route::has('filament.admin.pages.organization-dashboard')) {
        Route::get('/__test/organization-dashboard', fn () => 'organization dashboard')
            ->name('filament.admin.pages.organization-dashboard');
    }

    if (! Route::has('filament.admin.pages.tenant-dashboard')) {
        Route::get('/__test/tenant-dashboard', fn () => 'tenant dashboard')->name('filament.admin.pages.tenant-dashboard');
    }

    app('router')->getRoutes()->refreshNameLookups();
    app('router')->getRoutes()->refreshActionLookups();
}

beforeEach(function (): void {
    registerInvitationLocaleDestinationFixtures();
});

/**
 * @param  array<string, mixed>  $attributes
 * @return array{invitation: OrganizationInvitation, token: string}
 */
function localePersistenceInvitation(array $attributes = []): array
{
    $token = OrganizationInvitation::issueToken();
    $tokenHash = OrganizationInvitation::hashToken($token);

    $invitation = OrganizationInvitation::factory()->create([
        ...$attributes,
        'token' => $tokenHash,
        'token_hash' => $tokenHash,
    ]);

    return compact('invitation', 'token');
}

it('stores the active guest locale when accepting a new invitation', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
        'locale' => 'ru',
    ]);

    ['invitation' => $invitation, 'token' => $token] = localePersistenceInvitation([
        'organization_id' => $organization->id,
        'inviter_user_id' => $admin->id,
        'email' => 'manager@example.com',
        'role' => UserRole::MANAGER,
        'full_name' => 'Marta Manager',
    ]);

    $this->from(route('invitation.show', $token))
        ->post(route('locale.update'), [
            'locale' => 'ru',
        ])
        ->assertRedirect(route('invitation.show', $token));

    $this->post(route('invitation.store', $token), [
        'name' => 'Marta Manager',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertRedirect(route('filament.admin.pages.dashboard'));

    $user = User::query()->where('email', $invitation->email)->firstOrFail();

    expect($user->locale)->toBe('ru');
});

it('updates an invited tenant placeholder to the active guest locale on acceptance', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
        'locale' => 'ru',
    ]);

    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
        'email' => 'tenant@example.com',
        'name' => 'Pending Tenant',
        'locale' => 'lt',
        'status' => UserStatus::INACTIVE,
    ]);

    ['token' => $token] = localePersistenceInvitation([
        'organization_id' => $organization->id,
        'inviter_user_id' => $admin->id,
        'email' => $tenant->email,
        'full_name' => 'Pat Tenant',
        'role' => UserRole::TENANT,
    ]);

    $this->from(route('invitation.show', $token))
        ->post(route('locale.update'), [
            'locale' => 'ru',
        ])
        ->assertRedirect(route('invitation.show', $token));

    $this->post(route('invitation.store', $token), [
        'name' => 'Pat Tenant',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertRedirect(route('filament.admin.pages.dashboard'));

    expect($tenant->fresh()->locale)->toBe('ru');
});
