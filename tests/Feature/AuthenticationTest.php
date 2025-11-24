<?php

use App\Enums\UserRole;
use App\Enums\SubscriptionStatus;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Authentication Tests
 * 
 * Tests login functionality for each role, invalid credentials handling,
 * and logout functionality.
 * 
 * Requirements: 2.1, 2.2, 2.3, 2.4, 2.5
 */

test('admin can login with valid credentials and redirects to admin dashboard', function () {
    // Create admin user
    $admin = User::factory()->create([
        'email' => 'admin@test.com',
        'password' => Hash::make('password'),
        'role' => UserRole::ADMIN,
    ]);

    // Attempt login
    $response = $this->post('/login', [
        'email' => 'admin@test.com',
        'password' => 'password',
    ]);

    // Assert redirected to admin dashboard
    $response->assertRedirect('/admin/dashboard');
    
    // Assert user is authenticated
    $this->assertAuthenticatedAs($admin);
});

test('manager can login with valid credentials and redirects to manager dashboard', function () {
    // Create manager user
    $manager = User::factory()->create([
        'email' => 'manager@test.com',
        'password' => Hash::make('password'),
        'role' => UserRole::MANAGER,
    ]);

    // Attempt login
    $response = $this->post('/login', [
        'email' => 'manager@test.com',
        'password' => 'password',
    ]);

    // Assert redirected to manager dashboard
    $response->assertRedirect('/manager/dashboard');
    
    // Assert user is authenticated
    $this->assertAuthenticatedAs($manager);
});

test('tenant can login with valid credentials and redirects to tenant dashboard', function () {
    // Create tenant user
    $tenant = User::factory()->create([
        'email' => 'tenant@test.com',
        'password' => Hash::make('password'),
        'role' => UserRole::TENANT,
    ]);

    // Attempt login
    $response = $this->post('/login', [
        'email' => 'tenant@test.com',
        'password' => 'password',
    ]);

    // Assert redirected to tenant dashboard
    $response->assertRedirect('/tenant/dashboard');
    
    // Assert user is authenticated
    $this->assertAuthenticatedAs($tenant);
});

test('login fails with invalid email', function () {
    // Create user
    User::factory()->create([
        'email' => 'valid@test.com',
        'password' => Hash::make('password'),
        'role' => UserRole::MANAGER,
    ]);

    // Attempt login with wrong email
    $response = $this->post('/login', [
        'email' => 'invalid@test.com',
        'password' => 'password',
    ]);

    // Assert redirected back with error
    $response->assertRedirect();
    $response->assertSessionHasErrors('email');
    
    // Assert user is not authenticated
    $this->assertGuest();
});

test('login fails with invalid password', function () {
    // Create user
    User::factory()->create([
        'email' => 'user@test.com',
        'password' => Hash::make('correct-password'),
        'role' => UserRole::MANAGER,
    ]);

    // Attempt login with wrong password
    $response = $this->post('/login', [
        'email' => 'user@test.com',
        'password' => 'wrong-password',
    ]);

    // Assert redirected back with error
    $response->assertRedirect();
    $response->assertSessionHasErrors('email');
    
    // Assert user is not authenticated
    $this->assertGuest();
});

test('login fails with missing credentials', function () {
    // Attempt login without credentials
    $response = $this->post('/login', [
        'email' => '',
        'password' => '',
    ]);

    // Assert validation errors
    $response->assertSessionHasErrors(['email', 'password']);
    
    // Assert user is not authenticated
    $this->assertGuest();
});

test('login returns error message for invalid credentials', function () {
    // Create user
    User::factory()->create([
        'email' => 'user@test.com',
        'password' => Hash::make('password'),
        'role' => UserRole::MANAGER,
    ]);

    // Attempt login with wrong password
    $response = $this->post('/login', [
        'email' => 'user@test.com',
        'password' => 'wrong-password',
    ]);

    // Assert error message is present
    $response->assertSessionHasErrors([
        'email' => 'The provided credentials do not match our records.',
    ]);
});

test('logout clears session and redirects to home', function () {
    // Create and authenticate user
    $user = User::factory()->create([
        'role' => UserRole::MANAGER,
    ]);
    
    $this->actingAs($user);
    
    // Assert user is authenticated
    $this->assertAuthenticated();

    // Logout
    $response = $this->post('/logout');

    // Assert redirected to home
    $response->assertRedirect('/');
    
    // Assert user is no longer authenticated
    $this->assertGuest();
});

test('logout invalidates session', function () {
    // Create and authenticate user
    $user = User::factory()->create([
        'role' => UserRole::MANAGER,
    ]);
    
    $this->actingAs($user);
    
    // Store session ID
    $sessionId = session()->getId();
    
    // Logout
    $this->post('/logout');
    
    // Assert session ID has changed (regenerated)
    expect(session()->getId())->not->toBe($sessionId);
});

test('authenticated admin can access admin dashboard', function () {
    // Create and authenticate admin with active subscription
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);
    
    // Create active subscription for admin
    \App\Models\Subscription::factory()->create([
        'user_id' => $admin->id,
        'status' => SubscriptionStatus::ACTIVE->value,
        'starts_at' => now(),
        'expires_at' => now()->addYear(),
    ]);
    
    $this->actingAs($admin);

    // Access admin dashboard
    $response = $this->get(route('filament.admin.pages.dashboard'));

    // Assert successful access
    $response->assertOk();
});

test('authenticated manager can access manager dashboard', function () {
    // Create and authenticate manager
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
    ]);
    
    $this->actingAs($manager);

    // Access manager dashboard
    $response = $this->get('/manager/dashboard');

    // Assert successful access
    $response->assertOk();
});

test('authenticated tenant can access tenant dashboard', function () {
    // Create and authenticate tenant
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
    ]);
    
    $this->actingAs($tenant);

    // Access tenant dashboard
    $response = $this->get('/tenant/dashboard');

    // Assert successful access
    $response->assertOk();
});

test('unauthenticated user cannot access admin dashboard', function () {
    // Attempt to access admin dashboard without authentication
    $response = $this->get(route('filament.admin.pages.dashboard'));

    // Assert redirected to login
    $response->assertRedirect(route('filament.admin.auth.login'));
});

test('unauthenticated user cannot access manager dashboard', function () {
    // Attempt to access manager dashboard without authentication
    $response = $this->get('/manager/dashboard');

    // Assert redirected to login
    $response->assertRedirect('/login');
});

test('unauthenticated user cannot access tenant dashboard', function () {
    // Attempt to access tenant dashboard without authentication
    $response = $this->get('/tenant/dashboard');

    // Assert redirected to login
    $response->assertRedirect('/login');
});

test('login with remember me sets remember token', function () {
    // Create user
    $user = User::factory()->create([
        'email' => 'user@test.com',
        'password' => Hash::make('password'),
        'role' => UserRole::MANAGER,
    ]);

    // Attempt login with remember me
    $response = $this->post('/login', [
        'email' => 'user@test.com',
        'password' => 'password',
        'remember' => true,
    ]);

    // Assert redirected
    $response->assertRedirect('/manager/dashboard');
    
    // Assert user is authenticated
    $this->assertAuthenticatedAs($user);
    
    // Assert remember token is set
    expect($user->fresh()->remember_token)->not->toBeNull();
});

test('session is regenerated on successful login', function () {
    // Create user
    $user = User::factory()->create([
        'email' => 'user@test.com',
        'password' => Hash::make('password'),
        'role' => UserRole::MANAGER,
    ]);
    
    // Start a session
    $this->get('/login');
    $oldSessionId = session()->getId();

    // Attempt login
    $this->post('/login', [
        'email' => 'user@test.com',
        'password' => 'password',
    ]);

    // Assert session ID has changed
    expect(session()->getId())->not->toBe($oldSessionId);
});
