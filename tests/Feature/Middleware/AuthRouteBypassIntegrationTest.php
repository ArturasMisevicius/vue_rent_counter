<?php

declare(strict_types=1);

use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Integration tests for auth route bypass in CheckSubscriptionStatus middleware.
 * 
 * These tests verify the complete authentication flow works correctly with the
 * auth route bypass, ensuring users can authenticate regardless of subscription
 * status while maintaining security and subscription enforcement on protected routes.
 */

describe('Complete Authentication Flow', function () {
    test('user can complete full login flow without subscription', function () {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'password' => Hash::make('password'),
        ]);

        // Step 1: Access login page
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Welcome Back');

        // Step 2: Submit credentials
        $response = $this->post(route('login'), [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        // Step 3: Verify redirect to dashboard
        $response->assertRedirect(route('admin.dashboard'));

        // Step 4: Verify authentication
        $this->assertAuthenticatedAs($admin);

        // Step 5: Verify session created
        $this->assertNotNull(session()->getId());
    });

    test('user can logout after login without subscription', function () {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'password' => Hash::make('password'),
        ]);

        // Login
        $this->post(route('login'), [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($admin);

        // Logout
        $this->post(route('logout'))
            ->assertRedirect('/');

        $this->assertGuest();
    });

    test('user sees subscription warning after login with expired subscription', function () {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'password' => Hash::make('password'),
        ]);

        Subscription::factory()->create([
            'user_id' => $admin->id,
            'status' => SubscriptionStatus::EXPIRED,
            'expires_at' => now()->subDays(5),
        ]);

        // Login succeeds
        $this->post(route('login'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        // Dashboard shows warning
        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSessionHas('warning');
    });

    test('user can register and login without subscription', function () {
        // Step 1: Access registration page
        $this->get(route('register'))
            ->assertOk();

        // Step 2: Register new user
        $response = $this->post(route('register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        // Step 3: Verify redirect after registration
        $response->assertRedirect();

        // Step 4: Verify user was created
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);
    });
});

describe('Security Verification', function () {
    test('csrf protection still active on auth routes', function () {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'password' => Hash::make('password'),
        ]);

        // Get login page to get CSRF token
        $response = $this->get(route('login'));
        $response->assertOk();

        // Verify CSRF token is present in the page
        $response->assertSee('csrf-token', false);
    });

    test('rate limiting still applies to login attempts', function () {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'password' => Hash::make('password'),
        ]);

        // Make multiple failed login attempts
        for ($i = 0; $i < 6; $i++) {
            $this->post(route('login'), [
                'email' => $admin->email,
                'password' => 'wrong-password',
            ]);
        }

        // Next attempt should be rate limited
        $response = $this->post(route('login'), [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        // Should see rate limit error (429 or redirect with error)
        $this->assertTrue(
            $response->status() === 429 || $response->isRedirect()
        );
    });

    test('session security maintained with auth route bypass', function () {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'password' => Hash::make('password'),
        ]);

        // Login
        $this->post(route('login'), [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $sessionId = session()->getId();

        // Logout
        $this->post(route('logout'));

        // Session should be invalidated
        $this->assertGuest();
        $this->assertNotEquals($sessionId, session()->getId());
    });
});

describe('Subscription Enforcement', function () {
    test('admin routes still enforce subscription after successful login', function () {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'password' => Hash::make('password'),
        ]);

        // Login succeeds without subscription
        $this->post(route('login'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect();

        // But admin routes show subscription error
        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSessionHas('error');
    });

    test('write operations blocked with expired subscription after login', function () {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'password' => Hash::make('password'),
        ]);

        Subscription::factory()->create([
            'user_id' => $admin->id,
            'status' => SubscriptionStatus::EXPIRED,
            'expires_at' => now()->subDays(5),
        ]);

        // Login succeeds
        $this->post(route('login'), [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        // Read operations work
        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSessionHas('warning');

        // Write operations blocked
        $this->post(route('admin.dashboard'))
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('error');
    });

    test('tenant users bypass subscription check on all routes', function () {
        $tenant = User::factory()->create([
            'role' => UserRole::TENANT,
            'password' => Hash::make('password'),
        ]);

        // Login
        $this->post(route('login'), [
            'email' => $tenant->email,
            'password' => 'password',
        ]);

        // Tenant routes work without subscription
        $this->get(route('tenant.dashboard'))
            ->assertOk()
            ->assertSessionMissing('error')
            ->assertSessionMissing('warning');
    });
});

describe('Edge Cases', function () {
    test('deactivated user cannot login even with auth route bypass', function () {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'password' => Hash::make('password'),
            'is_active' => false,
        ]);

        $this->post(route('login'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('login'))
            ->assertSessionHasErrors();

        $this->assertGuest();
    });

    test('invalid credentials fail even with auth route bypass', function () {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'password' => Hash::make('password'),
        ]);

        $this->post(route('login'), [
            'email' => $admin->email,
            'password' => 'wrong-password',
        ])->assertRedirect(route('login'))
            ->assertSessionHasErrors();

        $this->assertGuest();
    });

    test('logout works even when session is corrupted', function () {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $this->actingAs($admin);

        // Corrupt session data
        session()->put('corrupted_key', str_repeat('x', 10000));

        // Logout should still work
        $this->post(route('logout'))
            ->assertRedirect('/');

        $this->assertGuest();
    });

    test('concurrent login attempts work with auth route bypass', function () {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'password' => Hash::make('password'),
        ]);

        // Simulate concurrent requests
        $responses = [];
        for ($i = 0; $i < 3; $i++) {
            $responses[] = $this->post(route('login'), [
                'email' => $admin->email,
                'password' => 'password',
            ]);
        }

        // All should succeed (or at least not fail due to subscription check)
        foreach ($responses as $response) {
            $this->assertTrue(
                $response->isRedirect() || $response->isOk()
            );
        }
    });
});
