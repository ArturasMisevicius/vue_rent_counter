<?php

use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

it('renders the shared locale switcher on each guest auth page', function (Closure $urlFactory) {
    $this->get($urlFactory())
        ->assertSuccessful()
        ->assertSee('action="'.route('locale.update').'"', false)
        ->assertSee('value="en"', false)
        ->assertSee('value="lt"', false)
        ->assertSee('value="ru"', false);
})->with([
    'login' => [
        fn (): string => route('login'),
    ],
    'register' => [
        fn (): string => route('register'),
    ],
    'forgot password' => [
        fn (): string => route('password.request'),
    ],
    'reset password' => [
        function (): string {
            $user = User::factory()->create([
                'email' => 'reset@example.com',
            ]);

            $token = Password::broker()->createToken($user);

            return route('password.reset', [
                'token' => $token,
                'email' => $user->email,
            ]);
        },
    ],
    'invitation acceptance' => [
        function (): string {
            $invitation = OrganizationInvitation::factory()->create();

            return route('invitation.show', $invitation->token);
        },
    ],
]);

it('redirects back to the same reset password page after changing the locale', function () {
    $user = User::factory()->create([
        'email' => 'reset@example.com',
    ]);

    $token = Password::broker()->createToken($user);

    $resetPasswordUrl = route('password.reset', [
        'token' => $token,
        'email' => $user->email,
    ]);

    $this->from($resetPasswordUrl)
        ->post(route('locale.update'), [
            'locale' => 'ru',
        ])
        ->assertRedirect($resetPasswordUrl);

    $this->get($resetPasswordUrl)
        ->assertSuccessful()
        ->assertSeeText(__('auth.reset_password_title', [], 'ru'));
});

it('redirects back to the same invitation page after changing the locale', function () {
    $invitation = OrganizationInvitation::factory()->create();

    $invitationUrl = route('invitation.show', $invitation->token);

    $this->from($invitationUrl)
        ->post(route('locale.update'), [
            'locale' => 'ru',
        ])
        ->assertRedirect($invitationUrl);

    $this->get($invitationUrl)
        ->assertSuccessful()
        ->assertSeeText(__('auth.invitation_title', [], 'ru'));
});

it('applies the guest locale to the register page', function () {
    $this->withSession([
        'guest_locale' => 'ru',
    ])->get(route('register'))
        ->assertSuccessful()
        ->assertSeeText(__('auth.register_title', [], 'ru'));
});

it('applies the guest locale to the forgot password page', function () {
    $this->withSession([
        'guest_locale' => 'ru',
    ])->get(route('password.request'))
        ->assertSuccessful()
        ->assertSeeText(__('auth.forgot_password_title', [], 'ru'));
});

it('applies the guest locale to the reset password page', function () {
    $user = User::factory()->create([
        'email' => 'localized-reset@example.com',
    ]);

    $token = Password::broker()->createToken($user);

    $this->withSession([
        'guest_locale' => 'ru',
    ])->get(route('password.reset', [
        'token' => $token,
        'email' => $user->email,
    ]))
        ->assertSuccessful()
        ->assertSeeText(__('auth.reset_password_title', [], 'ru'));
});

it('applies the guest locale to the invitation page', function () {
    $invitation = OrganizationInvitation::factory()->create();

    $this->withSession([
        'guest_locale' => 'ru',
    ])->get(route('invitation.show', $invitation->token))
        ->assertSuccessful()
        ->assertSeeText(__('auth.invitation_title', [], 'ru'));
});

it('keeps the previous valid guest locale when an unsupported locale is submitted', function () {
    $this->withSession([
        'guest_locale' => 'ru',
    ])->from(route('register'))
        ->post(route('locale.update'), [
            'locale' => 'de',
        ])
        ->assertRedirect(route('register'))
        ->assertSessionHasErrors(['locale']);

    $this->withSession([
        'guest_locale' => 'ru',
    ])->get(route('register'))
        ->assertSuccessful()
        ->assertSeeText(__('auth.register_title', [], 'ru'));
});

it('falls back to the public homepage when locale switching has no guest referrer', function () {
    $this->post(route('locale.update'), [
        'locale' => 'ru',
    ])->assertRedirect(route('home'));
});

it('falls back to the public homepage when locale switching comes from a non-guest page', function () {
    if (! Route::has('test.private')) {
        Route::middleware('web')->get('/__test/private', fn () => 'private')->name('test.private');

        app('router')->getRoutes()->refreshNameLookups();
        app('router')->getRoutes()->refreshActionLookups();
    }

    $this->from(route('test.private'))
        ->post(route('locale.update'), [
            'locale' => 'ru',
        ])
        ->assertRedirect(route('home'));
});

it('falls back to the public homepage when locale switching comes from an external referrer', function () {
    $this->withHeader('referer', 'https://evil.example/phish')
        ->post(route('locale.update'), [
            'locale' => 'ru',
        ])
        ->assertRedirect(route('home'));
});
