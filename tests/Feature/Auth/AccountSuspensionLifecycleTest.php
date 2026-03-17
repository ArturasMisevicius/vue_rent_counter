<?php

use App\Actions\Superadmin\Organizations\SuspendOrganizationAction;
use App\Enums\OrganizationStatus;
use App\Models\DatabaseSession;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

function registerAccountSuspensionFixtures(): void
{
    if (! Route::has('welcome.show')) {
        Route::get('/welcome', fn () => 'welcome')->name('welcome.show');
    }

    if (! Route::has('tenant.home')) {
        Route::get('/tenant/home', fn () => 'tenant home')->name('tenant.home');
    }

    if (! Route::has('filament.admin.pages.platform-dashboard')) {
        Route::get('/admin/platform-dashboard', fn () => 'platform')->name('filament.admin.pages.platform-dashboard');
    }

    if (! Route::has('filament.admin.pages.organization-dashboard')) {
        Route::get('/admin/organization-dashboard', fn () => 'organization')->name('filament.admin.pages.organization-dashboard');
    }

    app('router')->getRoutes()->refreshNameLookups();
    app('router')->getRoutes()->refreshActionLookups();
}

it('shows the suspended-account message on login when the organization is suspended', function () {
    registerAccountSuspensionFixtures();

    $organization = Organization::factory()->create([
        'status' => OrganizationStatus::SUSPENDED,
    ]);

    $admin = User::factory()->admin()->for($organization)->create();

    $this->from(route('login'))
        ->post(route('login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors([
            'email' => __('auth.account_suspended'),
        ]);

    $this->assertGuest();
});

it('terminates active database sessions when an organization is suspended', function () {
    registerAccountSuspensionFixtures();

    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->for($organization)->create();
    $manager = User::factory()->manager()->for($organization)->create();

    $adminSessionId = 'admin-session-id';
    $managerSessionId = 'manager-session-id';

    DatabaseSession::query()->create([
        'id' => $adminSessionId,
        'user_id' => $admin->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'PHPUnit',
        'payload' => 'payload',
        'last_activity' => now()->timestamp,
    ]);

    DatabaseSession::query()->create([
        'id' => $managerSessionId,
        'user_id' => $manager->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'PHPUnit',
        'payload' => 'payload',
        'last_activity' => now()->timestamp,
    ]);

    expect(DatabaseSession::query()->whereKey($adminSessionId)->exists())->toBeTrue()
        ->and(DatabaseSession::query()->whereKey($managerSessionId)->exists())->toBeTrue();

    app(SuspendOrganizationAction::class)->handle($organization);

    expect(DatabaseSession::query()->whereKey($adminSessionId)->exists())->toBeFalse()
        ->and(DatabaseSession::query()->whereKey($managerSessionId)->exists())->toBeFalse()
        ->and($organization->fresh()->status)->toBe(OrganizationStatus::SUSPENDED);
});
