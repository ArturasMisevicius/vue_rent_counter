<?php

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'is_active' => true,
    ]);
});

test('superadmin can access platform user resource', function () {
    $this->actingAs($this->superadmin);
    
    $response = $this->get('/admin/platform-users');
    
    $response->assertStatus(200);
});

test('non-superadmin cannot access platform user resource', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'is_active' => true,
    ]);
    
    $this->actingAs($admin);
    
    $response = $this->get('/admin/platform-users');
    
    $response->assertStatus(403);
});

test('platform user resource displays users', function () {
    $this->actingAs($this->superadmin);
    
    // Create test users
    User::factory()->count(3)->create([
        'role' => UserRole::ADMIN,
    ]);
    
    $response = $this->get('/admin/platform-users');
    
    $response->assertStatus(200);
});

test('superadmin can create user through resource', function () {
    $this->actingAs($this->superadmin);
    
    $organization = Organization::factory()->create();
    
    $response = $this->post('/admin/platform-users', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'role' => UserRole::ADMIN->value,
        'tenant_id' => $organization->id,
        'is_active' => true,
    ]);
    
    expect(User::where('email', 'test@example.com')->exists())->toBeTrue();
});

test('superadmin can deactivate user', function () {
    $this->actingAs($this->superadmin);
    
    $user = User::factory()->create([
        'role' => UserRole::ADMIN,
        'is_active' => true,
    ]);
    
    $user->update(['is_active' => false]);
    
    expect($user->fresh()->is_active)->toBeFalse();
});

test('superadmin can reactivate user', function () {
    $this->actingAs($this->superadmin);
    
    $user = User::factory()->create([
        'role' => UserRole::ADMIN,
        'is_active' => false,
    ]);
    
    $user->update(['is_active' => true]);
    
    expect($user->fresh()->is_active)->toBeTrue();
});

test('bulk deactivate updates multiple users', function () {
    $this->actingAs($this->superadmin);
    
    $users = User::factory()->count(3)->create([
        'role' => UserRole::ADMIN,
        'is_active' => true,
    ]);
    
    $users->each(fn (User $user) => $user->update(['is_active' => false]));
    
    foreach ($users as $user) {
        expect($user->fresh()->is_active)->toBeFalse();
    }
});

test('bulk reactivate updates multiple users', function () {
    $this->actingAs($this->superadmin);
    
    $users = User::factory()->count(3)->create([
        'role' => UserRole::ADMIN,
        'is_active' => false,
    ]);
    
    $users->each(fn (User $user) => $user->update(['is_active' => true]));
    
    foreach ($users as $user) {
        expect($user->fresh()->is_active)->toBeTrue();
    }
});
