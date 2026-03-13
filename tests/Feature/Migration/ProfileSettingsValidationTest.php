<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Session;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\put;
use function Pest\Laravel\patch;
use function Pest\Laravel\post;

test('profile update validation enforces required fields', function () {
    $user = \App\Models\User::factory()->admin()->create();
    actingAs($user);
    
    // Start session to generate CSRF token
    Session::start();
    $token = csrf_token();

    // Empty fields
    $response = patch(route('admin.profile.update'), [
        'name' => '',
        'email' => '',
        '_token' => $token,
    ]);
    
    if ($response->status() === 419) {
        dump('CSRF Token mismatch even with manual token');
    }

    $response->assertSessionHasErrors(['name', 'email']);

    // Invalid email
    patch(route('admin.profile.update'), [
        'name' => 'Valid Name',
        'email' => 'not-an-email',
        '_token' => $token,
    ])
    ->assertSessionHasErrors(['email']);
});

test('profile update validation enforces unique email', function () {
    $user = \App\Models\User::factory()->admin()->create();
    $otherUser = \App\Models\User::factory()->admin()->create(['email' => 'other@example.com']);
    actingAs($user);
    Session::start();
    $token = csrf_token();

    patch(route('admin.profile.update'), [
        'name' => 'New Name',
        'email' => 'other@example.com',
        'currency' => 'EUR',
        '_token' => $token,
    ])
    ->assertSessionHasErrors(['email']);
});

test('profile update allows ignoring own email for unique check', function () {
    $user = \App\Models\User::factory()->admin()->create(['email' => 'my@example.com']);
    actingAs($user);
    Session::start();
    $token = csrf_token();

    patch(route('admin.profile.update'), [
        'name' => 'Updated Name',
        'email' => 'my@example.com',
        'currency' => 'EUR',
        '_token' => $token,
    ])
    ->assertSessionHasNoErrors()
    ->assertRedirect();
});

test('password update validation requires current password', function () {
    $user = \App\Models\User::factory()->admin()->create();
    actingAs($user);
    Session::start();
    $token = csrf_token();

    patch(route('admin.profile.update-password'), [
        'current_password' => '',
        'password' => 'newpassword',
        'password_confirmation' => 'newpassword',
        '_token' => $token,
    ])
    ->assertSessionHasErrors(['current_password']);
});

test('password update validation requires password confirmation', function () {
    $user = \App\Models\User::factory()->admin()->create();
    actingAs($user);
    Session::start();
    $token = csrf_token();

    patch(route('admin.profile.update-password'), [
        'current_password' => 'password',
        'password' => 'newpassword',
        'password_confirmation' => 'mismatch',
        '_token' => $token,
    ])
    ->assertSessionHasErrors(['password']);
});

test('settings update validation enforces valid values', function () {
    $user = \App\Models\User::factory()->admin()->create();
    \App\Models\Subscription::factory()->create([
        'user_id' => $user->id,
        'status' => \App\Enums\SubscriptionStatus::ACTIVE,
        'expires_at' => now()->addYear(),
    ]);
    
    // Workaround for missing gate registration in test environment
    if (!\Illuminate\Support\Facades\Gate::has('updateSettings')) {
        \Illuminate\Support\Facades\Gate::define('updateSettings', [\App\Policies\SettingsPolicy::class, 'updateSettings']);
    }
    
    actingAs($user);
    Session::start();
    $token = csrf_token();

    // Invalid timezone
    put(route('admin.settings.update'), [
        'timezone' => 'Invalid/Timezone',
        '_token' => $token,
    ])
    ->assertSessionHasErrors(['timezone']);

    // Invalid currency
    put(route('admin.settings.update'), [
        'currency' => 'INVALID',
        '_token' => $token,
    ])
    ->assertSessionHasErrors(['currency']);
});

test('profile update success flashes message', function () {
    $user = \App\Models\User::factory()->admin()->create();
    actingAs($user);
    Session::start();
    $token = csrf_token();

    patch(route('admin.profile.update'), [
        'name' => 'New Name',
        'email' => 'new@example.com',
        'currency' => 'EUR',
        '_token' => $token,
    ])
    ->assertSessionHas('success')
    ->assertRedirect();
});
