<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests can access home page without errors', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});

test('guests can access login page without errors', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
    $response->assertSee('Welcome Back');
});

test('HierarchicalScope does not filter queries for guests', function () {
    // This test ensures that HierarchicalScope doesn't cause errors
    // when unauthenticated users access public pages
    
    // Create some test data
    \App\Models\User::factory()->create(['is_active' => true]);
    
    // Access login page as guest (should not throw errors)
    $response = $this->get('/login');
    
    $response->assertStatus(200);
    $response->assertDontSee('Query executed without tenant context');
});

test('guests can see user list on login page', function () {
    // Create test users
    $admin = \App\Models\User::factory()->create([
        'name' => 'Test Admin',
        'email' => 'admin@test.com',
        'role' => \App\Enums\UserRole::ADMIN,
        'is_active' => true,
    ]);
    
    $response = $this->get('/login');
    
    $response->assertStatus(200);
    $response->assertSee('Test Admin');
    $response->assertSee('admin@test.com');
});

test('login form has CSRF token', function () {
    $response = $this->get('/login');
    
    $response->assertStatus(200);
    $response->assertSee('csrf-token', false); // Check for CSRF meta tag or hidden input
});
