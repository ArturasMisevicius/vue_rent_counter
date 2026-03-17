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
    registerInvitationLocaleDestinationFixtures();
});

it('stores the active guest locale when accepting a new invitation', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
        'locale' => 'ru',
    ]);

    $invitation = OrganizationInvitation::factory()->create([
        'organization_id' => $organization->id,
        'inviter_user_id' => $admin->id,
        'email' => 'manager@example.com',
        'role' => UserRole::MANAGER,
        'full_name' => 'Marta Manager',
    ]);

    $this->from(route('invitation.show', $invitation->token))
        ->post(route('locale.update'), [
            'locale' => 'es',
        ])
        ->assertRedirect(route('invitation.show', $invitation->token));

    $this->post(route('invitation.store', $invitation->token), [
        'name' => 'Marta Manager',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertRedirect(route('filament.admin.pages.organization-dashboard'));

    $user = User::query()->where('email', $invitation->email)->firstOrFail();

    expect($user->locale)->toBe('es');
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

    $invitation = OrganizationInvitation::factory()->create([
        'organization_id' => $organization->id,
        'inviter_user_id' => $admin->id,
        'email' => $tenant->email,
        'full_name' => 'Pat Tenant',
        'role' => UserRole::TENANT,
    ]);

    $this->from(route('invitation.show', $invitation->token))
        ->post(route('locale.update'), [
            'locale' => 'es',
        ])
        ->assertRedirect(route('invitation.show', $invitation->token));

    $this->post(route('invitation.store', $invitation->token), [
        'name' => 'Pat Tenant',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertRedirect(route('tenant.home'));

    expect($tenant->fresh()->locale)->toBe('es');
});
