<?php

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

it('keeps the session idle timeout at 120 minutes', function () {
    expect(config('session.lifetime'))->toBe(120);
});

function sessionTimeoutCookieName(): string
{
    return config('tenanto.auth.session_history_cookie_name', 'tenanto_authenticated_session');
}

beforeEach(function (): void {
    if (! Route::has('test.session-timeout.web')) {
        Route::middleware(['web', 'auth'])
            ->get('/__test/session-timeout', fn () => 'session timeout')
            ->name('test.session-timeout.web');
    }

    app('router')->getRoutes()->refreshNameLookups();
    app('router')->getRoutes()->refreshActionLookups();
});

it('redirects expired sessions on protected web routes to login with a timeout message', function () {
    $this->withCookie(sessionTimeoutCookieName(), '1')
        ->get(route('test.session-timeout.web'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('auth.session_expired', __('auth.session_expired'));
});

it('redirects expired sessions on admin routes to login with a timeout message', function () {
    $organization = Organization::factory()->create();
    User::factory()->admin()->for($organization)->create();

    $this->withCookie(sessionTimeoutCookieName(), '1')
        ->get(route('filament.admin.resources.buildings.index'))
        ->assertRedirect(route('filament.admin.auth.login'))
        ->assertSessionHas('auth.session_expired', __('auth.session_expired'));
});

it('restores the intended url after login when the previous session timed out', function () {
    $organization = Organization::factory()->create();
    $manager = User::factory()->manager()->for($organization)->create([
        'password' => 'password',
    ]);

    $this->withCookie(sessionTimeoutCookieName(), '1')
        ->get(route('test.session-timeout.web'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('auth.session_expired', __('auth.session_expired'));

    $this->post(route('login.store'), [
        'email' => $manager->email,
        'password' => 'password',
    ])->assertRedirect(route('test.session-timeout.web'));
});

it('does not show the timeout banner to a normal guest', function () {
    $this->followingRedirects()
        ->get(route('test.session-timeout.web'))
        ->assertSuccessful()
        ->assertDontSeeText(__('auth.session_expired'));
});
