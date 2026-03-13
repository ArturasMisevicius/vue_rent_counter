<?php

use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin can access user management index', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenant->id,
    ]);

    $response = $this->actingAs($admin)->get(route('admin.users.index'));

    $response->assertOk();
    $response->assertViewIs('pages.users.index');
    $response->assertSee('Users Management');
});

test('admin can view user details', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenant->id,
    ]);
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    $response = $this->actingAs($admin)->get(route('admin.users.show', $user));

    $response->assertOk();
    $response->assertViewIs('pages.users.show');
    $response->assertSee($user->name);
    $response->assertSee($user->email);
});

test('admin can access user creation form', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenant->id,
    ]);

    $response = $this->actingAs($admin)->get(route('admin.users.create'));

    $response->assertOk();
    $response->assertViewIs('pages.users.create');
    $response->assertSee('Create User');
});

test('admin can create a new user', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenant->id,
    ]);

    $userData = [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'tenant_id' => $tenant->id,
        'role' => 'manager',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    $response = $this->actingAs($admin)->post(route('admin.users.store'), $userData);

    $response->assertRedirect(route('admin.users.index'));
    $response->assertSessionHas('success', 'User created successfully.');

    $this->assertDatabaseHas('users', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'tenant_id' => $tenant->id,
        'role' => 'manager',
    ]);
});

test('admin can access user edit form', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenant->id,
    ]);
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    $response = $this->actingAs($admin)->get(route('admin.users.edit', $user));

    $response->assertOk();
    $response->assertViewIs('pages.users.edit');
    $response->assertSee('Edit User');
    $response->assertSee($user->name);
});

test('admin can update a user', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenant->id,
    ]);
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Old Name',
    ]);

    $updateData = [
        'name' => 'Updated Name',
        'email' => $user->email,
        'tenant_id' => $tenant->id,
        'role' => $user->role->value,
    ];

    $response = $this->actingAs($admin)->put(route('admin.users.update', $user), $updateData);

    $response->assertRedirect(route('admin.users.index'));
    $response->assertSessionHas('success', 'User updated successfully.');

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'Updated Name',
    ]);
});

test('admin can delete a user without associated data', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenant->id,
    ]);
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
    ]);

    $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $user));

    $response->assertRedirect(route('admin.users.index'));
    $response->assertSessionHas('success', 'User deleted successfully.');

    $this->assertDatabaseMissing('users', [
        'id' => $user->id,
    ]);
});

test('manager cannot access user management', function () {
    $tenant = Tenant::factory()->create();
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenant->id,
    ]);

    $response = $this->actingAs($manager)->get(route('admin.users.index'));

    $response->assertForbidden();
});

test('tenant cannot access user management', function () {
    $tenant = Tenant::factory()->create();
    $tenantUser = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $tenant->id,
    ]);

    $response = $this->actingAs($tenantUser)->get(route('admin.users.index'));

    $response->assertForbidden();
});

test('user creation validates email uniqueness', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenant->id,
    ]);
    $existingUser = User::factory()->create([
        'email' => 'existing@example.com',
        'tenant_id' => $tenant->id,
    ]);

    $userData = [
        'name' => 'Test User',
        'email' => 'existing@example.com', // Duplicate email
        'tenant_id' => $tenant->id,
        'role' => 'manager',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    $response = $this->actingAs($admin)->post(route('admin.users.store'), $userData);

    $response->assertSessionHasErrors('email');
});

test('user update validates email uniqueness except for current user', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenant->id,
    ]);
    $user1 = User::factory()->create([
        'email' => 'user1@example.com',
        'tenant_id' => $tenant->id,
    ]);
    $user2 = User::factory()->create([
        'email' => 'user2@example.com',
        'tenant_id' => $tenant->id,
    ]);

    // Try to update user1 with user2's email
    $updateData = [
        'name' => $user1->name,
        'email' => 'user2@example.com',
        'tenant_id' => $tenant->id,
        'role' => $user1->role->value,
    ];

    $response = $this->actingAs($admin)->put(route('admin.users.update', $user1), $updateData);

    $response->assertSessionHasErrors('email');
});

test('user can update their own email', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenant->id,
    ]);
    $user = User::factory()->create([
        'email' => 'original@example.com',
        'tenant_id' => $tenant->id,
    ]);

    // Update with same email should work
    $updateData = [
        'name' => $user->name,
        'email' => 'original@example.com',
        'tenant_id' => $tenant->id,
        'role' => $user->role->value,
    ];

    $response = $this->actingAs($admin)->put(route('admin.users.update', $user), $updateData);

    $response->assertRedirect(route('admin.users.index'));
    $response->assertSessionHasNoErrors();
});
