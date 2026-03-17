<?php

use App\Models\User;
use App\Support\Auth\AuthenticatedSessionMarker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

function registerSessionTimeoutFixtures(): void
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

    if (! Route::has('test.session-timeout')) {
        Route::middleware(['web', 'auth'])->get('/__test/session-timeout', fn () => 'protected')->name('test.session-timeout');
    }

    app('router')->getRoutes()->refreshNameLookups();
    app('router')->getRoutes()->refreshActionLookups();
}

it('shows the session-expired message when a previously authenticated web session lapses', function () {
    registerSessionTimeoutFixtures();

    $user = User::factory()->manager()->create();
    $cookie = app(AuthenticatedSessionMarker::class)->make($user);

    $this->followingRedirects()
        ->withCookie($cookie->getName(), $cookie->getValue())
        ->get(route('test.session-timeout'))
        ->assertSuccessful()
        ->assertSeeText(__('auth.session_expired'));
});

it('shows the session-expired message when a previously authenticated admin-panel session lapses', function () {
    registerSessionTimeoutFixtures();

    $user = User::factory()->admin()->create();
    $cookie = app(AuthenticatedSessionMarker::class)->make($user);

    $this->followingRedirects()
        ->withCookie($cookie->getName(), $cookie->getValue())
        ->get('/admin')
        ->assertSuccessful()
        ->assertSeeText(__('auth.session_expired'));
});

it('restores the intended url after login when the session expired first', function () {
    registerSessionTimeoutFixtures();

    $user = User::factory()->manager()->create();
    $cookie = app(AuthenticatedSessionMarker::class)->make($user);

    $this->withCookie($cookie->getName(), $cookie->getValue())
        ->get(route('test.session-timeout'))
        ->assertRedirect(route('login'));

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('test.session-timeout'));
});

it('does not show the session-expired message to a normal guest', function () {
    registerSessionTimeoutFixtures();

    $this->followingRedirects()
        ->get(route('test.session-timeout'))
        ->assertSuccessful()
        ->assertDontSeeText(__('auth.session_expired'));
});
