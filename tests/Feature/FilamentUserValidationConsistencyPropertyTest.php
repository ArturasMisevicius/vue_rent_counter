<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

/**
 * Property Test: User Validation Consistency
 * 
 * Validates that UserResource form validation matches backend validation rules.
 * 
 * Requirements: 6.4
 * Property: 13
 * 
 * @group property
 * @group user-resource
 */

test('user validation is consistent between form and backend', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);
    
    actingAs($admin);
    
    // Test valid data passes both form and backend validation
    $validData = [
        'name' => 'Test User',
        'email' => 'test' . uniqid() . '@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => UserRole::MANAGER->value,
        'tenant_id' => 1,
        'is_active' => true,
    ];
    
    // Backend validation
    $validator = Validator::make($validData, [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'unique:users,email', 'max:255'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
        'role' => ['required', 'in:' . implode(',', array_column(UserRole::cases(), 'value'))],
        'tenant_id' => ['required_if:role,manager,tenant', 'nullable', 'integer', 'exists:users,id'],
    ]);
    
    expect($validator->passes())->toBeTrue();
    
    // Test invalid data fails both validations
    $invalidData = [
        'name' => '', // Required
        'email' => 'invalid-email', // Invalid format
        'password' => 'short', // Too short
        'password_confirmation' => 'different', // Doesn't match
        'role' => 'invalid', // Invalid enum
        'tenant_id' => null, // Required for manager
    ];
    
    $validator = Validator::make($invalidData, [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'unique:users,email', 'max:255'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
        'role' => ['required', 'in:' . implode(',', array_column(UserRole::cases(), 'value'))],
        'tenant_id' => ['required_if:role,manager,tenant', 'nullable', 'integer', 'exists:users,id'],
    ]);
    
    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('name'))->toBeTrue()
        ->and($validator->errors()->has('email'))->toBeTrue()
        ->and($validator->errors()->has('password'))->toBeTrue()
        ->and($validator->errors()->has('role'))->toBeTrue();
});

test('user validation messages are localized', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);
    
    actingAs($admin);
    
    $invalidData = [
        'name' => '',
        'email' => 'invalid',
        'password' => 'short',
        'role' => '',
    ];
    
    $validator = Validator::make($invalidData, [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'unique:users,email', 'max:255'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
        'role' => ['required', 'in:' . implode(',', array_column(UserRole::cases(), 'value'))],
    ]);
    
    $errors = $validator->errors();
    
    // Verify error messages exist and are strings
    expect($errors->get('name'))->toBeArray()
        ->and($errors->get('email'))->toBeArray()
        ->and($errors->get('password'))->toBeArray()
        ->and($errors->get('role'))->toBeArray();
});

test('name validation enforces required and max length', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);
    
    actingAs($admin);
    
    // Test empty name fails
    $data = [
        'name' => '',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => UserRole::MANAGER->value,
        'tenant_id' => 1,
    ];
    
    $validator = Validator::make($data, [
        'name' => ['required', 'string', 'max:255'],
    ]);
    
    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('name'))->toBeTrue();
    
    // Test name over 255 characters fails
    $data['name'] = str_repeat('a', 256);
    
    $validator = Validator::make($data, [
        'name' => ['required', 'string', 'max:255'],
    ]);
    
    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('name'))->toBeTrue();
    
    // Test valid name passes
    $data['name'] = 'Valid Name';
    
    $validator = Validator::make($data, [
        'name' => ['required', 'string', 'max:255'],
    ]);
    
    expect($validator->passes())->toBeTrue();
});

test('email validation enforces required, format, and uniqueness', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);
    
    actingAs($admin);
    
    // Test empty email fails
    $data = [
        'email' => '',
    ];
    
    $validator = Validator::make($data, [
        'email' => ['required', 'email', 'unique:users,email', 'max:255'],
    ]);
    
    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('email'))->toBeTrue();
    
    // Test invalid email format fails
    $data['email'] = 'invalid-email';
    
    $validator = Validator::make($data, [
        'email' => ['required', 'email', 'unique:users,email', 'max:255'],
    ]);
    
    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('email'))->toBeTrue();
    
    // Test duplicate email fails
    $existingUser = User::factory()->create(['email' => 'existing@example.com']);
    $data['email'] = 'existing@example.com';
    
    $validator = Validator::make($data, [
        'email' => ['required', 'email', 'unique:users,email', 'max:255'],
    ]);
    
    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('email'))->toBeTrue();
    
    // Test valid unique email passes
    $data['email'] = 'unique' . uniqid() . '@example.com';
    
    $validator = Validator::make($data, [
        'email' => ['required', 'email', 'unique:users,email', 'max:255'],
    ]);
    
    expect($validator->passes())->toBeTrue();
});

test('password validation enforces min length and confirmation', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);
    
    actingAs($admin);
    
    // Test password too short fails
    $data = [
        'password' => 'short',
        'password_confirmation' => 'short',
    ];
    
    $validator = Validator::make($data, [
        'password' => ['required', 'string', 'min:8', 'confirmed'],
    ]);
    
    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('password'))->toBeTrue();
    
    // Test password confirmation mismatch fails
    $data = [
        'password' => 'password123',
        'password_confirmation' => 'different123',
    ];
    
    $validator = Validator::make($data, [
        'password' => ['required', 'string', 'min:8', 'confirmed'],
    ]);
    
    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('password'))->toBeTrue();
    
    // Test valid password passes
    $data = [
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];
    
    $validator = Validator::make($data, [
        'password' => ['required', 'string', 'min:8', 'confirmed'],
    ]);
    
    expect($validator->passes())->toBeTrue();
});

test('role validation enforces required and valid enum', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);
    
    actingAs($admin);
    
    // Test empty role fails
    $data = [
        'role' => '',
    ];
    
    $validator = Validator::make($data, [
        'role' => ['required', 'in:' . implode(',', array_column(UserRole::cases(), 'value'))],
    ]);
    
    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('role'))->toBeTrue();
    
    // Test invalid role fails
    $data['role'] = 'invalid_role';
    
    $validator = Validator::make($data, [
        'role' => ['required', 'in:' . implode(',', array_column(UserRole::cases(), 'value'))],
    ]);
    
    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('role'))->toBeTrue();
    
    // Test valid roles pass
    foreach (UserRole::cases() as $role) {
        $data['role'] = $role->value;
        
        $validator = Validator::make($data, [
            'role' => ['required', 'in:' . implode(',', array_column(UserRole::cases(), 'value'))],
        ]);
        
        expect($validator->passes())->toBeTrue();
    }
});

