<?php

use App\Enums\OrganizationStatus;
use App\Enums\UserStatus;
use App\Filament\Support\Auth\LoginRedirector;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

function registerAuthRouteFixtures(): void
{
    if (! Route::has('login')) {
        Route::get('/login', fn () => 'login')->name('login');
    }

    if (! Route::has('welcome.show')) {
        Route::get('/welcome', fn () => 'welcome')->name('welcome.show');
    }

    if (! Route::has('filament.admin.pages.tenant-dashboard')) {
        Route::get('/__test/tenant-dashboard', fn () => 'tenant dashboard')->name('filament.admin.pages.tenant-dashboard');
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

it('redirects a partially onboarded admin to welcome', function () {
    registerAuthRouteFixtures();

    Route::middleware(['web', 'auth', 'ensure.onboarding.complete'])
        ->get('/__test/protected-onboarding', fn () => 'ok');

    $user = User::factory()->admin()->create([
        'organization_id' => null,
    ]);

    $this->actingAs($user)
        ->get('/__test/protected-onboarding')
        ->assertRedirect(route('welcome.show'));
});

it('blocks suspended users from protected routes', function () {
    registerAuthRouteFixtures();

    Route::middleware(['web', 'auth', 'ensure.account.accessible'])
        ->get('/__test/protected-account', fn () => 'ok');

    $user = User::factory()->admin()->create([
        'status' => UserStatus::SUSPENDED,
    ]);

    $this->actingAs($user)
        ->get('/__test/protected-account')
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

it('blocks suspended organizations from protected routes', function () {
    registerAuthRouteFixtures();

    $organization = Organization::factory()->create([
        'status' => OrganizationStatus::SUSPENDED,
    ]);

    Route::middleware(['web', 'auth', 'ensure.account.accessible'])
        ->get('/__test/protected-organization', fn () => 'ok');

    $user = User::factory()->admin()->for($organization)->create();

    $this->actingAs($user)
        ->get('/__test/protected-organization')
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

it('blocks non-active organizations from protected routes', function () {
    registerAuthRouteFixtures();

    $organization = Organization::factory()->create([
        'status' => OrganizationStatus::ARCHIVED,
    ]);

    Route::middleware(['web', 'auth', 'ensure.account.accessible'])
        ->get('/__test/protected-archived-organization', fn () => 'ok');

    $user = User::factory()->admin()->for($organization)->create();

    $this->actingAs($user)
        ->get('/__test/protected-archived-organization')
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

it('routes users to the correct starting page', function () {
    registerAuthRouteFixtures();

    $redirector = app(LoginRedirector::class);

    expect($redirector->for(User::factory()->superadmin()->make()))
        ->toBe(route('filament.admin.pages.dashboard'))
        ->and($redirector->for(User::factory()->admin()->make(['organization_id' => null])))
        ->toBe(route('welcome.show'))
        ->and($redirector->for(User::factory()->manager()->make()))
        ->toBe(route('filament.admin.pages.dashboard'))
        ->and($redirector->for(User::factory()->tenant()->make()))
        ->toBe(route('filament.admin.pages.tenant-dashboard'));
});

it('forbids non-tenant users from the tenant home route', function () {
    registerAuthRouteFixtures();

    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($admin)
        ->get(route('filament.admin.pages.tenant-dashboard'))
        ->assertForbidden();
});

it('forbids non-tenant users from the tenant invoice history route', function () {
    registerAuthRouteFixtures();

    if (! Route::has('filament.admin.pages.tenant-invoice-history')) {
        Route::get('/__test/tenant-invoices', fn () => 'tenant invoices')->name('filament.admin.pages.tenant-invoice-history');
    }

    app('router')->getRoutes()->refreshNameLookups();
    app('router')->getRoutes()->refreshActionLookups();

    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($admin)
        ->get(route('filament.admin.pages.tenant-invoice-history'))
        ->assertForbidden();
});
