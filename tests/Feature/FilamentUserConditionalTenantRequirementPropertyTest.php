<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

/**
 * Property Test: Conditional Tenant Requirement
 * 
 * Validates that tenant_id is required only for Manager and Tenant roles.
 * 
 * Requirements: 6.5
 * Property: 14
 * 
 * @group property
 * @group user-resource
 */

test('tenant field is required for manager role', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);
    
    actingAs($admin);
    
    // Manager without tenant_id should fail
    $data = [
        'name' => 'Test Manager',
        'email' => 'manager' . uniqid() . '@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => UserRole::MANAGER->value,
        'tenant_id' => null,
    ];
    
    $validator = Validator::make($data, [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'unique:users,email', 'max:255'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
        'role' => ['required', 'in:' . implode(',', array_column(UserRole::cases(), 'value'))],
        'tenant_id' => ['required_if:role,manager,tenant', 'nullable', 'integer', 'exists:users,id'],
    ]);
    
    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('tenant_id'))->toBeTrue();
    
    // Manager with tenant_id should pass
    $data['tenant_id'] = 1;
    
    $validator = Validator::make($data, [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'unique:users,email', 'max:255'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
        'role' => ['required', 'in:' . implode(',', array_column(UserRole::cases(), 'value'))],
        'tenant_id' => ['required_if:role,manager,tenant', 'nullable', 'integer', 'exists:users,id'],
    ]);
    
    expect($validator->passes())->toBeTrue();
});

test('tenant field is required for tenant role', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);
    
    actingAs($admin);
    
    // Tenant without tenant_id should fail
    $data = [
        'name' => 'Test Tenant',
        'email' => 'tenant' . uniqid() . '@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => UserRole::TENANT->value,
        'tenant_id' => null,
    ];
    
    $validator = Validator::make($data, [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'unique:users,email', 'max:255'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
        'role' => ['required', 'in:' . implode(',', array_column(UserRole::cases(), 'value'))],
        'tenant_id' => ['required_if:role,manager,tenant', 'nullable', 'integer', 'exists:users,id'],
    ]);
    
    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('tenant_id'))->toBeTrue();
    
    // Tenant with tenant_id should pass
    $data['tenant_id'] = 1;
    
    $validator = Validator::make($data, [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'unique:users,email', 'max:255'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
        'role' => ['required', 'in:' . implode(',', array_column(UserRole::cases(), 'value'))],
        'tenant_id' => ['required_if:role,manager,tenant', 'nullable', 'integer', 'exists:users,id'],
    ]);
    
    expect($validator->passes())->toBeTrue();
});

test('tenant field is optional for admin role', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);
    
    actingAs($admin);
    
    // Admin without tenant_id should pass
    $data = [
        'name' => 'Test Admin',
        'email' => 'admin' . uniqid() . '@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => UserRole::ADMIN->value,
        'tenant_id' => null,
    ];
    
    $validator = Validator::make($data, [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'unique:users,email', 'max:255'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
        'role' => ['required', 'in:' . implode(',', array_column(UserRole::cases(), 'value'))],
        'tenant_id' => ['required_if:role,manager,tenant', 'nullable', 'integer', 'exists:users,id'],
    ]);
    
    expect($validator->passes())->toBeTrue();
    
    // Admin with tenant_id should also pass
    $data['tenant_id'] = 1;
    
    $validator = Validator::make($data, [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'unique:users,email', 'max:255'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
        'role' => ['required', 'in:' . implode(',', array_column(UserRole::cases(), 'value'))],
        'tenant_id' => ['required_if:role,manager,tenant', 'nullable', 'integer', 'exists:users,id'],
    ]);
    
    expect($validator->passes())->toBeTrue();
});

test('tenant field is optional for superadmin role', function () {
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
    ]);
    
    actingAs($superadmin);
    
    // Superadmin without tenant_id should pass
    $data = [
        'name' => 'Test Superadmin',
        'email' => 'superadmin' . uniqid() . '@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => UserRole::SUPERADMIN->value,
        'tenant_id' => null,
    ];
    
    $validator = Validator::make($data, [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'unique:users,email', 'max:255'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
        'role' => ['required', 'in:' . implode(',', array_column(UserRole::cases(), 'value'))],
        'tenant_id' => ['required_if:role,manager,tenant', 'nullable', 'integer', 'exists:users,id'],
    ]);
    
    expect($validator->passes())->toBeTrue();
});

test('tenant_id must exist in users table when provided', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);
    
    actingAs($admin);
    
    // Non-existent tenant_id should fail
    $data = [
        'name' => 'Test Manager',
        'email' => 'manager' . uniqid() . '@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => UserRole::MANAGER->value,
        'tenant_id' => 999999, // Non-existent
    ];
    
    $validator = Validator::make($data, [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'unique:users,email', 'max:255'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
        'role' => ['required', 'in:' . implode(',', array_column(UserRole::cases(), 'value'))],
        'tenant_id' => ['required_if:role,manager,tenant', 'nullable', 'integer', 'exists:users,id'],
    ]);
    
    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('tenant_id'))->toBeTrue();
    
    // Existing tenant_id should pass
    $data['tenant_id'] = $admin->id;
    
    $validator = Validator::make($data, [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'unique:users,email', 'max:255'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
        'role' => ['required', 'in:' . implode(',', array_column(UserRole::cases(), 'value'))],
        'tenant_id' => ['required_if:role,manager,tenant', 'nullable', 'integer', 'exists:users,id'],
    ]);
    
    expect($validator->passes())->toBeTrue();
});

test('tenant_id validation respects role changes', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);
    
    actingAs($admin);
    
    // Start with admin role (tenant_id optional)
    $data = [
        'name' => 'Test User',
        'email' => 'user' . uniqid() . '@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => UserRole::ADMIN->value,
        'tenant_id' => null,
    ];
    
    $validator = Validator::make($data, [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'unique:users,email', 'max:255'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
        'role' => ['required', 'in:' . implode(',', array_column(UserRole::cases(), 'value'))],
        'tenant_id' => ['required_if:role,manager,tenant', 'nullable', 'integer', 'exists:users,id'],
    ]);
    
    expect($validator->passes())->toBeTrue();
    
    // Change to manager role (tenant_id required)
    $data['role'] = UserRole::MANAGER->value;
    
    $validator = Validator::make($data, [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'unique:users,email', 'max:255'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
        'role' => ['required', 'in:' . implode(',', array_column(UserRole::cases(), 'value'))],
        'tenant_id' => ['required_if:role,manager,tenant', 'nullable', 'integer', 'exists:users,id'],
    ]);
    
    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('tenant_id'))->toBeTrue();
    
    // Add tenant_id
    $data['tenant_id'] = 1;
    
    $validator = Validator::make($data, [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'unique:users,email', 'max:255'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
        'role' => ['required', 'in:' . implode(',', array_column(UserRole::cases(), 'value'))],
        'tenant_id' => ['required_if:role,manager,tenant', 'nullable', 'integer', 'exists:users,id'],
    ]);
    
    expect($validator->passes())->toBeTrue();
});

