<?php

use App\Enums\UserRole;
use App\Filament\Resources\UserResource;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// Feature: filament-admin-panel, Property 15: Null tenant allowance for admin users
// Validates: Requirements 6.6
test('Filament UserResource allows null tenant_id for admin users on create', function () {
    // Create an admin user to perform the operation
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => null,
    ]);
    
    // Act as the admin
    $this->actingAs($admin);
    
    // Generate random test data for an admin user WITHOUT tenant_id
    $testData = [
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => UserRole::ADMIN->value,
        // tenant_id is intentionally null/missing
    ];
    
    // Property: Admin users can have null tenant_id
    
    // Test with Filament form
    $component = Livewire::test(UserResource\Pages\CreateUser::class);
    
    $component->fillForm([
        'name' => $testData['name'],
        'email' => $testData['email'],
        'password' => $testData['password'],
        'password_confirmation' => $testData['password_confirmation'],
        'role' => $testData['role'],
        // tenant_id is not set (null)
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
            "Admin user creation without tenant_id should succeed, but got errors: " . json_encode($errors)
        );
    }
    
    // Property: Validation should pass when tenant_id is null for admin
    expect($passed)->toBeTrue(
        "Admin user creation should succeed without tenant_id"
    );
    
    // Verify the user was actually created with null tenant_id
    $createdUser = User::where('email', $testData['email'])->first();
    expect($createdUser)->not->toBeNull("User should be created in database");
    expect($createdUser->tenant_id)->toBeNull("Admin user should have null tenant_id");
    expect($createdUser->role)->toBe(UserRole::ADMIN, "User should have admin role");
})->repeat(100);

// Feature: filament-admin-panel, Property 15: Null tenant allowance for admin users
// Validates: Requirements 6.6
test('Filament UserResource allows changing tenant_id to null when updating to admin role', function () {
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
    
    // Property: When changing role to admin, tenant_id can be set to null
    
    // Test with Filament form - change to admin and set tenant_id to null
    $component = Livewire::test(UserResource\Pages\EditUser::class, [
        'record' => $existingUser->id,
    ]);
    
    $component->fillForm([
        'name' => $existingUser->name,
        'email' => $existingUser->email,
        'role' => UserRole::ADMIN->value, // Change to admin
        'tenant_id' => null, // Set tenant_id to null
    ]);
    
    // Try to save - this should succeed
    try {
        $component->call('save');
        $passed = true;
    } catch (\Illuminate\Validation\ValidationException $e) {
        $passed = false;
        $errors = $e->errors();
        
        // If it failed, show the errors
        expect($passed)->toBeTrue(
            "User update to admin with null tenant_id should succeed, but got errors: " . json_encode($errors)
        );
    }
    
    // Property: Validation should pass when changing to admin with null tenant_id
    expect($passed)->toBeTrue(
        "User update should succeed when changing to admin with null tenant_id"
    );
    
    // Verify the user was actually updated with null tenant_id
    $updatedUser = User::find($existingUser->id);
    expect($updatedUser->tenant_id)->toBeNull("Admin user should have null tenant_id after update");
    expect($updatedUser->role)->toBe(UserRole::ADMIN, "User should have admin role after update");
})->repeat(100);

// Feature: filament-admin-panel, Property 15: Null tenant allowance for admin users
// Validates: Requirements 6.6
test('Filament UserResource allows admin users to remain with null tenant_id on update', function () {
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
    
    // Property: Admin users can remain with null tenant_id when updated
    
    // Test with Filament form - update admin user without changing tenant_id
    $component = Livewire::test(UserResource\Pages\EditUser::class, [
        'record' => $existingAdmin->id,
    ]);
    
    $newName = fake()->name();
    
    $component->fillForm([
        'name' => $newName,
        'email' => $existingAdmin->email,
        'role' => UserRole::ADMIN->value, // Keep admin role
        'tenant_id' => null, // Keep tenant_id as null
    ]);
    
    // Try to save - this should succeed
    try {
        $component->call('save');
        $passed = true;
    } catch (\Illuminate\Validation\ValidationException $e) {
        $passed = false;
        $errors = $e->errors();
        
        // If it failed, show the errors
        expect($passed)->toBeTrue(
            "Admin user update with null tenant_id should succeed, but got errors: " . json_encode($errors)
        );
    }
    
    // Property: Validation should pass when admin keeps null tenant_id
    expect($passed)->toBeTrue(
        "Admin user update should succeed with null tenant_id"
    );
    
    // Verify the user was actually updated and tenant_id remains null
    $updatedUser = User::find($existingAdmin->id);
    expect($updatedUser->name)->toBe($newName, "User name should be updated");
    expect($updatedUser->tenant_id)->toBeNull("Admin user should still have null tenant_id");
    expect($updatedUser->role)->toBe(UserRole::ADMIN, "User should still have admin role");
})->repeat(100);

// Feature: filament-admin-panel, Property 15: Null tenant allowance for admin users
// Validates: Requirements 6.6
test('Filament UserResource does not require tenant_id field for admin role', function () {
    // Create an admin user to perform the operation
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => null,
    ]);
    
    // Act as the admin
    $this->actingAs($admin);
    
    // Generate random test data for an admin user
    $testData = [
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => UserRole::ADMIN->value,
    ];
    
    // Property: Admin users should not be required to have tenant_id
    
    // Test with Filament form - explicitly omit tenant_id
    $component = Livewire::test(UserResource\Pages\CreateUser::class);
    
    $component->fillForm([
        'name' => $testData['name'],
        'email' => $testData['email'],
        'password' => $testData['password'],
        'password_confirmation' => $testData['password_confirmation'],
        'role' => $testData['role'],
        // tenant_id is explicitly not provided
    ]);
    
    // Try to create - this should succeed without tenant_id validation error
    try {
        $component->call('create');
        $passed = true;
        $errors = [];
    } catch (\Illuminate\Validation\ValidationException $e) {
        $passed = false;
        $errors = $e->errors();
        
        // If it failed, tenant_id should NOT be in the errors
        expect($errors)->not->toHaveKey('tenant_id',
            "Admin user should not require tenant_id, but got tenant_id error: " . json_encode($errors)
        );
    }
    
    // Property: Creation should succeed without tenant_id for admin
    expect($passed)->toBeTrue(
        "Admin user creation should succeed without tenant_id requirement"
    );
    
    // Verify the user was created correctly
    $createdUser = User::where('email', $testData['email'])->first();
    expect($createdUser)->not->toBeNull("User should be created in database");
    expect($createdUser->tenant_id)->toBeNull("Admin user should have null tenant_id");
    expect($createdUser->role)->toBe(UserRole::ADMIN, "User should have admin role");
})->repeat(100);

// Feature: filament-admin-panel, Property 15: Null tenant allowance for admin users
// Validates: Requirements 6.6
test('Filament UserResource allows admin users to be created with explicit null tenant_id', function () {
    // Create an admin user to perform the operation
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => null,
    ]);
    
    // Act as the admin
    $this->actingAs($admin);
    
    // Generate random test data for an admin user with explicit null tenant_id
    $testData = [
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => UserRole::ADMIN->value,
        'tenant_id' => null, // Explicitly set to null
    ];
    
    // Property: Admin users can be created with explicit null tenant_id
    
    // Test with Filament form
    $component = Livewire::test(UserResource\Pages\CreateUser::class);
    
    $component->fillForm([
        'name' => $testData['name'],
        'email' => $testData['email'],
        'password' => $testData['password'],
        'password_confirmation' => $testData['password_confirmation'],
        'role' => $testData['role'],
        'tenant_id' => $testData['tenant_id'], // Explicitly null
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
            "Admin user creation with explicit null tenant_id should succeed, but got errors: " . json_encode($errors)
        );
    }
    
    // Property: Validation should pass with explicit null tenant_id for admin
    expect($passed)->toBeTrue(
        "Admin user creation should succeed with explicit null tenant_id"
    );
    
    // Verify the user was actually created with null tenant_id
    $createdUser = User::where('email', $testData['email'])->first();
    expect($createdUser)->not->toBeNull("User should be created in database");
    expect($createdUser->tenant_id)->toBeNull("Admin user should have null tenant_id");
    expect($createdUser->role)->toBe(UserRole::ADMIN, "User should have admin role");
})->repeat(100);
