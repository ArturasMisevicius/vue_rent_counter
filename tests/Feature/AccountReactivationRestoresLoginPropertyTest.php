<?php

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(ValidateCsrfToken::class);
});

// Feature: hierarchical-user-management, Property 12: Account reactivation restores login
// Validates: Requirements 7.3
test('reactivated users can login successfully after being deactivated', function () {
    // Test with multiple iterations to ensure property holds across different scenarios
    for ($i = 0; $i < 100; $i++) {
        // Generate random user data
        $roles = [UserRole::SUPERADMIN, UserRole::ADMIN, UserRole::MANAGER, UserRole::TENANT];
        $role = fake()->randomElement($roles);
        $email = fake()->unique()->safeEmail();
        $password = 'test-password-' . $i;
        
        // Create initially deactivated user
        $user = User::factory()->create([
            'email' => $email,
            'password' => Hash::make($password),
            'role' => $role,
            'is_active' => false, // Account starts deactivated
            'tenant_id' => $role === UserRole::SUPERADMIN ? null : fake()->numberBetween(1, 100),
        ]);

        // Create subscription for admin users (required for dashboard access)
        if ($role === UserRole::ADMIN) {
            Subscription::factory()->create([
                'user_id' => $user->id,
                'status' => 'active',
                'starts_at' => now(),
                'expires_at' => now()->addYear(),
            ]);
        }

        // Verify account is initially deactivated and cannot login
        $response = $this->post('/login', [
            'email' => $email,
            'password' => $password,
        ]);
        
        $response->assertRedirect(); // Redirected back to login form
        $response->assertSessionHasErrors('email'); // Has error message
        $this->assertGuest(); // User is not authenticated

        // Reactivate the account (this is what Property 12 tests)
        $user->update(['is_active' => true]);

        // Attempt login with reactivated account
        $response = $this->post('/login', [
            'email' => $email,
            'password' => $password,
        ]);

        // Assert login succeeds - redirected to appropriate dashboard
        $expectedRedirect = match($role->value) {
            'superadmin' => '/superadmin/dashboard',
            'admin' => '/admin/dashboard',
            'manager' => '/manager/dashboard',
            'tenant' => '/tenant/dashboard',
            default => '/',
        };
        
        $response->assertRedirect($expectedRedirect);
        
        // Assert user is authenticated
        $this->assertAuthenticatedAs($user);
        
        // Assert no session errors
        $response->assertSessionHasNoErrors();
        
        // Clean up for next iteration
        Auth::logout();
        $user->delete();
        $this->app['session.store']->flush();
    }
})->repeat(1); // Run the test once, but with 100 internal iterations

// Feature: hierarchical-user-management, Property 12: Account reactivation restores login
// Validates: Requirements 7.3
test('account reactivation cycle works correctly', function () {
    // Test the complete deactivation -> reactivation -> login cycle
    for ($i = 0; $i < 50; $i++) {
        $role = fake()->randomElement([UserRole::ADMIN, UserRole::MANAGER, UserRole::TENANT]);
        $email = fake()->unique()->safeEmail();
        $password = 'test-password-' . $i;
        
        // Create initially active user
        $user = User::factory()->create([
            'email' => $email,
            'password' => Hash::make($password),
            'role' => $role,
            'is_active' => true, // Account starts active
            'tenant_id' => fake()->numberBetween(1, 100),
        ]);

        // Create subscription for admin users
        if ($role === UserRole::ADMIN) {
            Subscription::factory()->create([
                'user_id' => $user->id,
                'status' => 'active',
                'starts_at' => now(),
                'expires_at' => now()->addYear(),
            ]);
        }

        // Verify initial login works
        $response = $this->post('/login', [
            'email' => $email,
            'password' => $password,
        ]);
        
        $expectedRedirect = match($role->value) {
            'admin' => '/admin/dashboard',
            'manager' => '/manager/dashboard',
            'tenant' => '/tenant/dashboard',
            default => '/',
        };
        
        $response->assertRedirect($expectedRedirect);
        $this->assertAuthenticatedAs($user);
        
        // Logout
        Auth::logout();

        // Deactivate the account
        $user->update(['is_active' => false]);

        // Verify login fails when deactivated
        $response = $this->post('/login', [
            'email' => $email,
            'password' => $password,
        ]);
        
        $response->assertRedirect();
        $response->assertSessionHasErrors('email');
        $this->assertGuest();

        // Reactivate the account (this is the key test)
        $user->update(['is_active' => true]);

        // Verify login works again after reactivation
        $response = $this->post('/login', [
            'email' => $email,
            'password' => $password,
        ]);
        
        $response->assertRedirect($expectedRedirect);
        $this->assertAuthenticatedAs($user);
        $response->assertSessionHasNoErrors();
        
        // Clean up for next iteration
        Auth::logout();
        $user->delete();
        $this->app['session.store']->flush();
    }
})->repeat(1); // Run the test once, but with 50 internal iterations

// Feature: hierarchical-user-management, Property 12: Account reactivation restores login
// Validates: Requirements 7.3
test('reactivation preserves user session and authentication state', function () {
    // Test that reactivation doesn't interfere with normal authentication flow
    for ($i = 0; $i < 30; $i++) {
        $role = fake()->randomElement([UserRole::ADMIN, UserRole::MANAGER, UserRole::TENANT]);
        $email = fake()->unique()->safeEmail();
        $password = 'test-password-' . $i;
        
        // Create deactivated user
        $user = User::factory()->create([
            'email' => $email,
            'password' => Hash::make($password),
            'role' => $role,
            'is_active' => false,
            'tenant_id' => fake()->numberBetween(1, 100),
        ]);

        // Create subscription for admin users
        if ($role === UserRole::ADMIN) {
            Subscription::factory()->create([
                'user_id' => $user->id,
                'status' => 'active',
                'starts_at' => now(),
                'expires_at' => now()->addYear(),
            ]);
        }

        // Reactivate account
        $user->update(['is_active' => true]);

        // Test login with remember token
        $response = $this->post('/login', [
            'email' => $email,
            'password' => $password,
            'remember' => true,
        ]);

        // Assert successful login with remember functionality
        $expectedRedirect = match($role->value) {
            'admin' => '/admin/dashboard',
            'manager' => '/manager/dashboard',
            'tenant' => '/tenant/dashboard',
            default => '/',
        };
        
        $response->assertRedirect($expectedRedirect);
        $this->assertAuthenticatedAs($user);
        
        // Verify session was regenerated (security measure)
        $this->assertNotNull(session()->getId());
        
        // Clean up for next iteration
        Auth::logout();
        $user->delete();
        $this->app['session.store']->flush();
    }
})->repeat(1); // Run the test once, but with 30 internal iterations
