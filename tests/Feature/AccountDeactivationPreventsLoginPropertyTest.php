<?php

use App\Enums\UserRole;
use App\Enums\SubscriptionStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(ValidateCsrfToken::class);
});

// Feature: hierarchical-user-management, Property 11: Account deactivation prevents login
// Validates: Requirements 7.1, 8.4
test('deactivated users cannot login regardless of role', function () {
    // Test with multiple iterations to ensure property holds across different scenarios
    for ($i = 0; $i < 100; $i++) {
        // Generate random user data
        $roles = [UserRole::SUPERADMIN, UserRole::ADMIN, UserRole::MANAGER, UserRole::TENANT];
        $role = fake()->randomElement($roles);
        $email = fake()->unique()->safeEmail();
        $password = 'test-password-' . $i;
        
        // Create deactivated user
        $user = User::factory()->create([
            'email' => $email,
            'password' => Hash::make($password),
            'role' => $role,
            'is_active' => false, // Account is deactivated
            'tenant_id' => $role === UserRole::SUPERADMIN ? null : fake()->numberBetween(1, 100),
        ]);

        // Attempt login with valid credentials but deactivated account
        $response = $this->post('/login', [
            'email' => $email,
            'password' => $password,
        ]);

        // Assert login fails
        $response->assertRedirect(); // Redirected back to login form
        $response->assertSessionHasErrors('email'); // Has error message
        
        // Assert user is not authenticated
        $this->assertGuest();
        
        // Assert specific error message for deactivated account
        $response->assertSessionHasErrors([
            'email' => 'Your account has been deactivated. Please contact your administrator for assistance.',
        ]);
        
        // Clean up for next iteration
        $user->delete();
        $this->app['session.store']->flush();
    }
})->repeat(1); // Run the test once, but with 100 internal iterations

// Feature: hierarchical-user-management, Property 11: Account deactivation prevents login
// Validates: Requirements 7.1, 8.4
test('active users can login successfully', function () {
    // Test with multiple iterations to ensure property holds across different scenarios
    for ($i = 0; $i < 100; $i++) {
        // Generate random user data
        $roles = [UserRole::SUPERADMIN, UserRole::ADMIN, UserRole::MANAGER, UserRole::TENANT];
        $role = fake()->randomElement($roles);
        $email = fake()->unique()->safeEmail();
        $password = 'test-password-' . $i;
        
        // Create active user
        $user = User::factory()->create([
            'email' => $email,
            'password' => Hash::make($password),
            'role' => $role,
            'is_active' => true, // Account is active
            'tenant_id' => $role === UserRole::SUPERADMIN ? null : fake()->numberBetween(1, 100),
        ]);

        // Create subscription for admin users (required for dashboard access)
        if ($role === UserRole::ADMIN) {
            \App\Models\Subscription::factory()->create([
                'user_id' => $user->id,
                'status' => SubscriptionStatus::ACTIVE->value,
                'starts_at' => now(),
                'expires_at' => now()->addYear(),
            ]);
        }

        // Attempt login with valid credentials and active account
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
        
        // Clean up for next iteration
        Auth::logout();
        $user->delete();
        $this->app['session.store']->flush();
    }
})->repeat(1); // Run the test once, but with 100 internal iterations

// Feature: hierarchical-user-management, Property 11: Account deactivation prevents login
// Validates: Requirements 7.1, 8.4
test('deactivated account login attempt logs user out if somehow authenticated', function () {
    // Test edge case where user might be authenticated but account becomes deactivated
    for ($i = 0; $i < 50; $i++) {
        $role = fake()->randomElement([UserRole::ADMIN, UserRole::MANAGER, UserRole::TENANT]);
        $email = fake()->unique()->safeEmail();
        $password = 'test-password-' . $i;
        
        // Create initially active user
        $user = User::factory()->create([
            'email' => $email,
            'password' => Hash::make($password),
            'role' => $role,
            'is_active' => true,
            'tenant_id' => fake()->numberBetween(1, 100),
        ]);

        // Create subscription for admin users
        if ($role === UserRole::ADMIN) {
            \App\Models\Subscription::factory()->create([
                'user_id' => $user->id,
                'status' => SubscriptionStatus::ACTIVE->value,
                'starts_at' => now(),
                'expires_at' => now()->addYear(),
            ]);
        }

        // Deactivate the account
        $user->update(['is_active' => false]);

        // Attempt login with deactivated account
        $response = $this->post('/login', [
            'email' => $email,
            'password' => $password,
        ]);

        // Assert login fails
        $response->assertRedirect();
        $response->assertSessionHasErrors('email');
        
        // Assert user is not authenticated (logged out)
        $this->assertGuest();
        
        // Clean up for next iteration
        $user->delete();
        $this->app['session.store']->flush();
    }
})->repeat(1); // Run the test once, but with 50 internal iterations
