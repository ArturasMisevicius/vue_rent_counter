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

it('allows every supported role to request a password reset link', function (Closure $userFactory) {
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
    'superadmin' => fn () => User::factory()->superadmin()->create(),
    'admin' => fn () => User::factory()->admin()->create(),
    'manager' => fn () => User::factory()->manager()->create(),
    'tenant' => fn () => User::factory()->tenant()->create(),
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

    expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue();
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

it('keeps reset tokens valid inside the configured 60 minute window', function () {
    $user = User::factory()->create();
    $token = Password::broker()->createToken($user);

    expect(config('auth.passwords.users.expire'))->toBe(60);

    Carbon::setTestNow(
        now()->addMinutes(config('auth.passwords.users.expire'))->subSecond()
    );

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertRedirect(route('login'))
        ->assertSessionHas('status', __('passwords.reset'));

    Carbon::setTestNow();
});
