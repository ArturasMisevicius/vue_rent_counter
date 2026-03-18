<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

function registerLoginDestinationFixtures(): void
{
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

    if (! Route::has('test.intended')) {
        Route::middleware(['web', 'auth'])->get('/__test/intended', fn () => 'intended')->name('test.intended');
    }

    app('router')->getRoutes()->refreshNameLookups();
    app('router')->getRoutes()->refreshActionLookups();
}

it('renders the login page', function () {
    registerLoginDestinationFixtures();

    $this->get(route('login'))
        ->assertSuccessful()
        ->assertSeeText('Welcome back')
        ->assertSeeText('Log in to your account')
        ->assertSeeText('Email Address')
        ->assertSeeText('Password')
        ->assertSeeText('Forgot your password?')
        ->assertSeeText("Don't have an account?")
        ->assertSeeText('Register');
});

it('keeps the email and shows a generic message when login fails', function () {
    registerLoginDestinationFixtures();

    User::factory()->create([
        'email' => 'asta@example.com',
    ]);

    $this->from(route('login'))
        ->post(route('login.store'), [
            'email' => 'asta@example.com',
            'password' => 'wrong-password',
        ])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors([
            'email' => __('auth.invalid_credentials'),
        ])
        ->assertSessionHasInput('email', 'asta@example.com');

    $this->assertGuest();
});

it('rate limits login after five failed attempts', function () {
    registerLoginDestinationFixtures();

    $user = User::factory()->create([
        'email' => 'asta@example.com',
    ]);

    foreach (range(1, 5) as $attempt) {
        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])
            ->assertRedirect(route('login'));
    }

    $this->from(route('login'))
        ->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])
        ->assertTooManyRequests();

    $this->assertGuest();
});

it('redirects unauthenticated admin panel access to the public login page', function () {
    registerLoginDestinationFixtures();

    $this->followingRedirects()
        ->get('/app')
        ->assertSuccessful()
        ->assertSeeText('Welcome back')
        ->assertSeeText('Log in to your account');
});

it('redirects users to the unified app entrypoint for their role context', function (Closure $userFactory, string $expectedRoute) {
    registerLoginDestinationFixtures();

    $user = $userFactory();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route($expectedRoute));

    $this->assertAuthenticatedAs($user);
})->with([
    'superadmin' => [
        fn () => User::factory()->superadmin()->create(),
        'filament.admin.pages.dashboard',
    ],
    'partially onboarded admin' => [
        fn () => User::factory()->admin()->create([
            'organization_id' => null,
        ]),
        'welcome.show',
    ],
    'manager' => [
        fn () => User::factory()->manager()->create(),
        'filament.admin.pages.dashboard',
    ],
    'tenant' => [
        fn () => User::factory()->tenant()->create(),
        'filament.admin.pages.tenant-dashboard',
    ],
]);

it('restores the intended url after login', function () {
    registerLoginDestinationFixtures();

    $user = User::factory()->manager()->create();

    $this->get(route('test.intended'))
        ->assertRedirect(route('login'));

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('test.intended'));
});

it('allows tenant users to enter the unified app panel', function () {
    registerLoginDestinationFixtures();

    $tenant = User::factory()->tenant()->create();

    $this->actingAs($tenant)
        ->get('/app')
        ->assertSuccessful();
});
