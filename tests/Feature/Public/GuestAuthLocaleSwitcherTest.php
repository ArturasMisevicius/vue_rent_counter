<?php

use App\Enums\LanguageStatus;
use App\Models\Language;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

const GUEST_LOCALE_CSRF_TOKEN = 'guest-locale-test-token';

it('renders the shared locale switcher on each guest auth page', function (Closure $urlFactory) {
    $this->get($urlFactory())
        ->assertSuccessful()
        ->assertSee('data-shell-locale="switcher"', false)
        ->assertSee("wire:click=\"changeLocale('en')\"", false)
        ->assertSee("wire:click=\"changeLocale('lt')\"", false)
        ->assertSee("wire:click=\"changeLocale('ru')\"", false);
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
        ->withSession(['_token' => GUEST_LOCALE_CSRF_TOKEN])
        ->post(route('locale.update'), [
            'locale' => 'ru',
            '_token' => GUEST_LOCALE_CSRF_TOKEN,
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
        ->withSession(['_token' => GUEST_LOCALE_CSRF_TOKEN])
        ->post(route('locale.update'), [
            'locale' => 'ru',
            '_token' => GUEST_LOCALE_CSRF_TOKEN,
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

it('applies the guest locale to the demo accounts block on the login page', function () {
    User::factory()->superadmin()->create([
        'email' => 'superadmin@example.com',
    ]);

    $this->withSession([
        'guest_locale' => 'ru',
    ])->get(route('login'))
        ->assertSuccessful()
        ->assertSeeText(__('auth.login_title', [], 'ru'))
        ->assertSeeText(__('auth.demo_accounts.heading', [], 'ru'))
        ->assertSeeText(__('auth.demo_accounts.description', [], 'ru'))
        ->assertSeeText(__('auth.demo_accounts.columns.username', [], 'ru'))
        ->assertSeeText(__('auth.demo_accounts.columns.password', [], 'ru'))
        ->assertSeeText(__('auth.demo_accounts.columns.role', [], 'ru'));
});

it('keeps the previous valid guest locale when an unsupported locale is submitted', function () {
    $this->withSession([
        '_token' => GUEST_LOCALE_CSRF_TOKEN,
        'guest_locale' => 'ru',
    ])->from(route('register'))
        ->post(route('locale.update'), [
            'locale' => 'de',
            '_token' => GUEST_LOCALE_CSRF_TOKEN,
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
    $this->withSession(['_token' => GUEST_LOCALE_CSRF_TOKEN])
        ->post(route('locale.update'), [
            'locale' => 'ru',
            '_token' => GUEST_LOCALE_CSRF_TOKEN,
        ])->assertRedirect(route('home'));
});

it('falls back to the public homepage when locale switching comes from a non-guest page', function () {
    if (! Route::has('test.private')) {
        Route::middleware('web')->get('/__test/private', fn () => 'private')->name('test.private');

        app('router')->getRoutes()->refreshNameLookups();
        app('router')->getRoutes()->refreshActionLookups();
    }

    $this->from(route('test.private'))
        ->withSession(['_token' => GUEST_LOCALE_CSRF_TOKEN])
        ->post(route('locale.update'), [
            'locale' => 'ru',
            '_token' => GUEST_LOCALE_CSRF_TOKEN,
        ])
        ->assertRedirect(route('home'));
});

it('falls back to the public homepage when locale switching comes from an external referrer', function () {
    $this->withHeader('referer', 'https://evil.example/phish')
        ->withSession(['_token' => GUEST_LOCALE_CSRF_TOKEN])
        ->post(route('locale.update'), [
            'locale' => 'ru',
            '_token' => GUEST_LOCALE_CSRF_TOKEN,
        ])
        ->assertRedirect(route('home'));
});

it('does not allow selecting a disabled locale for guests', function () {
    Language::factory()->create([
        'code' => 'en',
        'name' => 'English',
        'native_name' => 'English',
        'status' => LanguageStatus::ACTIVE,
        'is_default' => true,
    ]);

    Language::factory()->create([
        'code' => 'es',
        'name' => 'Spanish',
        'native_name' => 'Español',
        'status' => LanguageStatus::INACTIVE,
        'is_default' => false,
    ]);

    $this->get(route('login'))
        ->assertSuccessful()
        ->assertDontSee('value="es"', false);

    $this->withSession(['_token' => GUEST_LOCALE_CSRF_TOKEN])
        ->from(route('login'))
        ->post(route('locale.update'), [
            'locale' => 'es',
            '_token' => GUEST_LOCALE_CSRF_TOKEN,
        ])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors(['locale']);
});
