<?php

use App\Enums\UserRole;
use App\Filament\Resources\UserResource;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// Feature: filament-admin-panel, Property 14: Conditional tenant requirement for non-admin users
// Validates: Requirements 6.5
test('Filament UserResource requires tenant_id for manager users', function () {
    // Create an admin user to perform the operation
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => null,
    ]);
    
    // Act as the admin
    $this->actingAs($admin);
    
    // Generate random test data for a manager user WITHOUT tenant_id
    $testData = [
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => UserRole::MANAGER->value,
        // tenant_id is intentionally missing
    ];
    
    // Property: Manager users MUST have tenant_id
    
    // Test with Filament form
    $component = Livewire::test(UserResource\Pages\CreateUser::class);
    
    $component->fillForm([
        'name' => $testData['name'],
        'email' => $testData['email'],
        'password' => $testData['password'],
        'password_confirmation' => $testData['password_confirmation'],
        'role' => $testData['role'],
        // tenant_id is not set
    ]);
    
    // Try to create - this should fail validation
    try {
        $component->call('create');
        $passed = true;
    } catch (\Illuminate\Validation\ValidationException $e) {
        $passed = false;
        $errors = $e->errors();
        
        // Verify that tenant_id is in the error fields
        expect($errors)->toHaveKey('tenant_id', 
            "Expected tenant_id validation error for manager role, but got errors: " . json_encode($errors)
        );
    }
    
    // Property: Validation should fail when tenant_id is missing for manager
    expect($passed)->toBeFalse(
        "Manager user creation should fail without tenant_id"
    );
})->repeat(100);

// Feature: filament-admin-panel, Property 14: Conditional tenant requirement for non-admin users
// Validates: Requirements 6.5
test('Filament UserResource requires tenant_id for tenant users', function () {
    // Create an admin user to perform the operation
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => null,
    ]);
    
    // Act as the admin
    $this->actingAs($admin);
    
    // Generate random test data for a tenant user WITHOUT tenant_id
    $testData = [
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => UserRole::TENANT->value,
        // tenant_id is intentionally missing
    ];
    
    // Property: Tenant users MUST have tenant_id
    
    // Test with Filament form
    $component = Livewire::test(UserResource\Pages\CreateUser::class);
    
    $component->fillForm([
        'name' => $testData['name'],
        'email' => $testData['email'],
        'password' => $testData['password'],
        'password_confirmation' => $testData['password_confirmation'],
        'role' => $testData['role'],
        // tenant_id is not set
    ]);
    
    // Try to create - this should fail validation
    try {
        $component->call('create');
        $passed = true;
    } catch (\Illuminate\Validation\ValidationException $e) {
        $passed = false;
        $errors = $e->errors();
        
        // Verify that tenant_id is in the error fields
        expect($errors)->toHaveKey('tenant_id',
            "Expected tenant_id validation error for tenant role, but got errors: " . json_encode($errors)
        );
    }
    
    // Property: Validation should fail when tenant_id is missing for tenant
    expect($passed)->toBeFalse(
        "Tenant user creation should fail without tenant_id"
    );
})->repeat(100);

// Feature: filament-admin-panel, Property 14: Conditional tenant requirement for non-admin users
// Validates: Requirements 6.5
test('Filament UserResource accepts manager users with valid tenant_id', function () {
    // Create an admin user to perform the operation
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => null,
    ]);
    
    // Create a tenant for assignment
    $tenant = Tenant::factory()->create();
    
    // Act as the admin
    $this->actingAs($admin);
    
    // Generate random test data for a manager user WITH tenant_id
    $testData = [
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => UserRole::MANAGER->value,
        'tenant_id' => $tenant->id,
    ];
    
    // Property: Manager users with tenant_id should be accepted
    
    // Test with Filament form
    $component = Livewire::test(UserResource\Pages\CreateUser::class);
    
    $component->fillForm([
        'name' => $testData['name'],
        'email' => $testData['email'],
        'password' => $testData['password'],
        'password_confirmation' => $testData['password_confirmation'],
        'role' => $testData['role'],
        'tenant_id' => $testData['tenant_id'],
    ]);
    
    // Try to create - this should succeed
    try {
        $component->call('create');
        $passed = true;
    } catch (\Illuminate\Validation\ValidationException $e) {
        $passed = false;
        $errors = $e->errors();
        
        // If it failed, show the errors
        expect($passed)->toBeTrue(
            "Manager user creation with tenant_id should succeed, but got errors: " . json_encode($errors)
        );
    }
    
    // Property: Validation should pass when tenant_id is provided for manager
    expect($passed)->toBeTrue(
        "Manager user creation should succeed with tenant_id"
    );
    
    // Verify the user was actually created with the correct tenant_id
    $createdUser = User::where('email', $testData['email'])->first();
    expect($createdUser)->not->toBeNull("User should be created in database");
    expect($createdUser->tenant_id)->toBe($tenant->id, "User should have the correct tenant_id");
    expect($createdUser->role)->toBe(UserRole::MANAGER, "User should have manager role");
})->repeat(100);

// Feature: filament-admin-panel, Property 14: Conditional tenant requirement for non-admin users
// Validates: Requirements 6.5
test('Filament UserResource accepts tenant users with valid tenant_id', function () {
    // Create an admin user to perform the operation
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => null,
    ]);
    
    // Create a tenant for assignment
    $tenant = Tenant::factory()->create();
    
    // Act as the admin
    $this->actingAs($admin);
    
    // Generate random test data for a tenant user WITH tenant_id
    $testData = [
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => UserRole::TENANT->value,
        'tenant_id' => $tenant->id,
    ];
    
    // Property: Tenant users with tenant_id should be accepted
    
    // Test with Filament form
    $component = Livewire::test(UserResource\Pages\CreateUser::class);
    
    $component->fillForm([
        'name' => $testData['name'],
        'email' => $testData['email'],
        'password' => $testData['password'],
        'password_confirmation' => $testData['password_confirmation'],
        'role' => $testData['role'],
        'tenant_id' => $testData['tenant_id'],
    ]);
    
    // Try to create - this should succeed
    try {
        $component->call('create');
        $passed = true;
    } catch (\Illuminate\Validation\ValidationException $e) {
        $passed = false;
        $errors = $e->errors();
        
        // If it failed, show the errors
        expect($passed)->toBeTrue(
            "Tenant user creation with tenant_id should succeed, but got errors: " . json_encode($errors)
        );
    }
    
    // Property: Validation should pass when tenant_id is provided for tenant
    expect($passed)->toBeTrue(
        "Tenant user creation should succeed with tenant_id"
    );
    
    // Verify the user was actually created with the correct tenant_id
    $createdUser = User::where('email', $testData['email'])->first();
    expect($createdUser)->not->toBeNull("User should be created in database");
    expect($createdUser->tenant_id)->toBe($tenant->id, "User should have the correct tenant_id");
    expect($createdUser->role)->toBe(UserRole::TENANT, "User should have tenant role");
})->repeat(100);

// Feature: filament-admin-panel, Property 14: Conditional tenant requirement for non-admin users
// Validates: Requirements 6.5
test('Filament UserResource requires tenant_id when updating manager to non-admin role', function () {
    // Create an admin user to perform the operation
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => null,
    ]);
    
    // Create a tenant
    $tenant = Tenant::factory()->create();
    
    // Create an existing manager user with tenant
    $existingUser = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenant->id,
    ]);
    
    // Act as the admin
    $this->actingAs($admin);
    
    // Property: When updating a user, if role is manager or tenant, tenant_id must be present
    
    // Test with Filament form - try to remove tenant_id
    $component = Livewire::test(UserResource\Pages\EditUser::class, [
        'record' => $existingUser->id,
    ]);
    
    $component->fillForm([
        'name' => $existingUser->name,
        'email' => $existingUser->email,
        'role' => UserRole::MANAGER->value,
        'tenant_id' => null, // Try to remove tenant_id
    ]);
    
    // Try to save - this should fail validation
    try {
        $component->call('save');
        $passed = true;
    } catch (\Illuminate\Validation\ValidationException $e) {
        $passed = false;
        $errors = $e->errors();
        
        // Verify that tenant_id is in the error fields
        expect($errors)->toHaveKey('tenant_id',
            "Expected tenant_id validation error when removing tenant from manager, but got errors: " . json_encode($errors)
        );
    }
    
    // Property: Validation should fail when tenant_id is removed from manager
    expect($passed)->toBeFalse(
        "Manager user update should fail when tenant_id is removed"
    );
})->repeat(100);

// Feature: filament-admin-panel, Property 14: Conditional tenant requirement for non-admin users
// Validates: Requirements 6.5
test('Filament UserResource requires tenant_id when changing role from admin to manager', function () {
    // Create an admin user to perform the operation
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => null,
    ]);
    
    // Create another admin user (the one we'll update)
    $existingAdmin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => null,
    ]);
    
    // Act as the admin
    $this->actingAs($admin);
    
    // Property: When changing role from admin to manager, tenant_id must be provided
    
    // Test with Filament form - try to change to manager without tenant_id
    $component = Livewire::test(UserResource\Pages\EditUser::class, [
        'record' => $existingAdmin->id,
    ]);
    
    $component->fillForm([
        'name' => $existingAdmin->name,
        'email' => $existingAdmin->email,
        'role' => UserRole::MANAGER->value, // Change to manager
        'tenant_id' => null, // No tenant_id provided
    ]);
    
    // Try to save - this should fail validation
    try {
        $component->call('save');
        $passed = true;
    } catch (\Illuminate\Validation\ValidationException $e) {
        $passed = false;
        $errors = $e->errors();
        
        // Verify that tenant_id is in the error fields
        expect($errors)->toHaveKey('tenant_id',
            "Expected tenant_id validation error when changing admin to manager without tenant, but got errors: " . json_encode($errors)
        );
    }
    
    // Property: Validation should fail when changing to manager without tenant_id
    expect($passed)->toBeFalse(
        "User update should fail when changing from admin to manager without tenant_id"
    );
})->repeat(100);
