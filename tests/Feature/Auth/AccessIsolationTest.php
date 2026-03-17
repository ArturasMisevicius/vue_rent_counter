<?php

use App\Enums\OrganizationStatus;
use App\Enums\UserStatus;
use App\Models\Organization;
use App\Models\User;
use App\Support\Auth\LoginRedirector;
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

it('routes users to the correct starting page', function () {
    registerAuthRouteFixtures();

    $redirector = app(LoginRedirector::class);

    expect($redirector->for(User::factory()->superadmin()->make()))
        ->toBe(route('filament.admin.pages.platform-dashboard'))
        ->and($redirector->for(User::factory()->admin()->make(['organization_id' => null])))
        ->toBe(route('welcome.show'))
        ->and($redirector->for(User::factory()->manager()->make()))
        ->toBe(route('filament.admin.pages.organization-dashboard'))
        ->and($redirector->for(User::factory()->tenant()->make()))
        ->toBe(route('tenant.home'));
});
