<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

it('renders the forgot password page', function () {
    $this->get(route('password.request'))
        ->assertSuccessful()
        ->assertSeeText('Reset your password')
        ->assertSeeText('Enter your email address and we will send you a link to reset your password.')
        ->assertSeeText('Email Address')
        ->assertSeeText('Send Reset Link');
});

it('always returns the generic reset-link confirmation copy', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'tenant@example.com',
    ]);

    $this->from(route('password.request'))
        ->post(route('password.email'), [
            'email' => $user->email,
        ])
        ->assertRedirect(route('password.request'))
        ->assertSessionHas('status', __('auth.reset_link_generic'));

    Notification::assertSentTo($user, ResetPassword::class);

    $this->from(route('password.request'))
        ->post(route('password.email'), [
            'email' => 'missing@example.com',
        ])
        ->assertRedirect(route('password.request'))
        ->assertSessionHas('status', __('auth.reset_link_generic'));
});

it('rate limits repeated password reset link requests', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'tenant@example.com',
    ]);

    foreach (range(1, 5) as $attempt) {
        $this->from(route('password.request'))
            ->post(route('password.email'), [
                'email' => $user->email,
            ])
            ->assertRedirect(route('password.request'));
    }

    $this->from(route('password.request'))
        ->post(route('password.email'), [
            'email' => $user->email,
        ])
        ->assertTooManyRequests();
});

it('shows the reset-link confirmation in the selected guest locale', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'tenant@example.com',
    ]);

    $forgotPasswordUrl = route('password.request');

    $this->from($forgotPasswordUrl)
        ->post(route('locale.update'), [
            'locale' => 'ru',
        ])
        ->assertRedirect($forgotPasswordUrl);

    $this->from($forgotPasswordUrl)
        ->post(route('password.email'), [
            'email' => $user->email,
        ])
        ->assertRedirect($forgotPasswordUrl)
        ->assertSessionHas('status', __('auth.reset_link_generic', [], 'ru'));

    $this->get($forgotPasswordUrl)
        ->assertSuccessful()
        ->assertSeeText(__('auth.forgot_password_title', [], 'ru'))
        ->assertSeeText(__('auth.reset_link_generic', [], 'ru'));

    Notification::assertSentTo($user, ResetPassword::class);
});

it('sends reset links for every supported signed-in role', function (Closure $userFactory) {
    Notification::fake();

    $user = $userFactory();

    $this->from(route('password.request'))
        ->post(route('password.email'), [
            'email' => $user->email,
        ])
        ->assertRedirect(route('password.request'))
        ->assertSessionHas('status', __('auth.reset_link_generic'));

    Notification::assertSentTo($user, ResetPassword::class);
})->with([
    'superadmin' => [fn () => User::factory()->superadmin()->create()],
    'admin' => [fn () => User::factory()->admin()->create()],
    'manager' => [fn () => User::factory()->manager()->create()],
    'tenant' => [fn () => User::factory()->tenant()->create()],
]);

it('resets the password with a valid token', function () {
    $user = User::factory()->create();
    $token = Password::broker()->createToken($user);

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertRedirect(route('login'))
        ->assertSessionHas('status', __('passwords.reset'));

    $this->get(route('login'))
        ->assertSuccessful()
        ->assertSeeText('Welcome back')
        ->assertSeeText(__('passwords.reset'));

    expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue();
});

it('shows the reset confirmation in the selected guest locale after a successful reset', function () {
    $user = User::factory()->create([
        'email' => 'reset@example.com',
    ]);

    $token = Password::broker()->createToken($user);

    $resetUrl = route('password.reset', [
        'token' => $token,
        'email' => $user->email,
    ]);

    $this->from($resetUrl)
        ->post(route('locale.update'), [
            'locale' => 'ru',
        ])
        ->assertRedirect($resetUrl);

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'nueva-password',
        'password_confirmation' => 'nueva-password',
    ])->assertRedirect(route('login'))
        ->assertSessionHas('status', __('passwords.reset', [], 'ru'));

    $this->get(route('login'))
        ->assertSuccessful()
        ->assertSeeText(__('auth.login_title', [], 'ru'))
        ->assertSeeText(__('passwords.reset', [], 'ru'));

    expect(Hash::check('nueva-password', $user->fresh()->password))->toBeTrue();
});

it('rejects an expired reset token', function () {
    $user = User::factory()->create();
    $token = Password::broker()->createToken($user);

    Carbon::setTestNow(now()->addMinutes(config('auth.passwords.users.expire') + 1));

    $this->from(route('password.reset', ['token' => $token, 'email' => $user->email]))
        ->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertRedirect(route('password.reset', ['token' => $token, 'email' => $user->email]))
        ->assertSessionHasErrors([
            'email' => __('passwords.token'),
        ]);

    Carbon::setTestNow();
});

it('keeps reset tokens valid for the configured 60 minute window', function () {
    $user = User::factory()->tenant()->create();
    $token = Password::broker()->createToken($user);

    Carbon::setTestNow(now()->addMinutes(config('auth.passwords.users.expire') - 1));

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'window-password',
        'password_confirmation' => 'window-password',
    ])->assertRedirect(route('login'))
        ->assertSessionHas('status', __('passwords.reset'));

    expect(Hash::check('window-password', $user->fresh()->password))->toBeTrue()
        ->and(config('auth.passwords.users.expire'))->toBe(60);

    Carbon::setTestNow();
});

it('rate limits repeated password reset submissions', function () {
    $user = User::factory()->create([
        'email' => 'reset@example.com',
    ]);

    foreach (range(1, 5) as $attempt) {
        $this->from(route('password.reset', ['token' => 'invalid-token', 'email' => $user->email]))
            ->post(route('password.update'), [
                'token' => 'invalid-token',
                'email' => $user->email,
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertRedirect(route('password.reset', ['token' => 'invalid-token', 'email' => $user->email]));
    }

    $this->from(route('password.reset', ['token' => 'invalid-token', 'email' => $user->email]))
        ->post(route('password.update'), [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertTooManyRequests();
});
