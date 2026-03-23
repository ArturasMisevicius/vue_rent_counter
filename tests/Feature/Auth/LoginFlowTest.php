<?php

use App\Models\Organization;
use App\Models\Property;
use App\Models\PropertyAssignment;
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
    'tenant without organization' => [
        fn () => User::factory()->tenant()->create([
            'organization_id' => null,
        ]),
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

it('restores the intended url after a frontend session-expiry prompt redirect', function () {
    registerLoginDestinationFixtures();

    $user = User::factory()->manager()->create();
    $intendedPath = route('test.intended', [], false);

    $this->get(route('login', [
        'session_expired' => 1,
        'intended' => $intendedPath,
    ]))
        ->assertSuccessful()
        ->assertSeeText(__('auth.session_expired'));

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('test.intended'));
});

it('ignores external intended targets on the login page', function () {
    registerLoginDestinationFixtures();

    $user = User::factory()->manager()->create();

    $this->get(route('login', [
        'intended' => 'https://malicious.test/steal-session',
    ]))->assertSuccessful();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('filament.admin.pages.dashboard'));
});

it('allows tenant users to enter the unified app panel', function () {
    registerLoginDestinationFixtures();

    $tenant = User::factory()->tenant()->create();

    $this->actingAs($tenant)
        ->get('/app')
        ->assertSuccessful();
});

it('allows tenant users without an organization assignment to access the unified app entrypoint', function () {
    registerLoginDestinationFixtures();

    $tenant = User::factory()->tenant()->create([
        'organization_id' => null,
    ]);

    $this->actingAs($tenant)
        ->get('/app')
        ->assertSuccessful()
        ->assertSeeText('Dashboard');
});

it('logs in tenant users without an organization assignment and redirects to tenant dashboard', function () {
    registerLoginDestinationFixtures();

    $tenant = User::factory()->tenant()->create([
        'organization_id' => null,
    ]);

    $this->post(route('login.store'), [
        'email' => $tenant->email,
        'password' => 'password',
    ])->assertRedirect(route('filament.admin.pages.tenant-dashboard'));

    $this->assertAuthenticatedAs($tenant);
});

it('shows tenant dashboard interface after successful tenant login', function () {
    registerLoginDestinationFixtures();

    $tenant = User::factory()->tenant()->create();

    $this->followingRedirects()
        ->post(route('login.store'), [
            'email' => $tenant->email,
            'password' => 'password',
        ])
        ->assertSuccessful()
        ->assertSeeText('Home')
        ->assertSeeText('Readings')
        ->assertSeeText('Invoices');
});

it('shows tenant empty dashboard state after login when tenant has no organization assignment', function () {
    registerLoginDestinationFixtures();

    $tenant = User::factory()->tenant()->create([
        'organization_id' => null,
    ]);

    $this->followingRedirects()
        ->post(route('login.store'), [
            'email' => $tenant->email,
            'password' => 'password',
        ])
        ->assertSuccessful()
        ->assertSeeText('Home')
        ->assertSeeText('No property assigned yet');
});

it('renders the tenant dashboard even when the latest assignment belongs to another organization', function () {
    registerLoginDestinationFixtures();

    $tenantOrganization = Organization::factory()->create();
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $tenantOrganization->id,
    ]);

    $foreignOrganization = Organization::factory()->create();
    $foreignProperty = Property::factory()->create([
        'organization_id' => $foreignOrganization->id,
    ]);

    PropertyAssignment::factory()
        ->for($foreignOrganization)
        ->for($foreignProperty)
        ->for($tenant, 'tenant')
        ->create([
            'assigned_at' => now()->subMonth(),
            'unassigned_at' => null,
        ]);

    $this->actingAs($tenant)
        ->get('/app')
        ->assertSuccessful()
        ->assertSeeText('Dashboard')
        ->assertSeeText('Tenant Summary');
});
