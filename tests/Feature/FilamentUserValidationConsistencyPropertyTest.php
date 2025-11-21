<?php

use App\Enums\UserRole;
use App\Filament\Resources\UserResource;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// Feature: filament-admin-panel, Property 13: User validation consistency
// Validates: Requirements 6.4
test('Filament UserResource applies same validation rules as StoreUserRequest for create operations', function () {
    // Create an admin user to perform the operation
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => null,
    ]);
    
    // Create a tenant for assignment
    $tenant = Tenant::factory()->create();
    
    // Act as the admin
    $this->actingAs($admin);
    
    // Generate random test data for a manager or tenant user
    $role = fake()->randomElement([UserRole::MANAGER->value, UserRole::TENANT->value]);
    
    $testData = [
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => $role,
        'tenant_id' => $tenant->id,
    ];
    
    // Property: Validation rules from StoreUserRequest should match Filament validation
    
    // Test with StoreUserRequest
    $request = new StoreUserRequest();
    $request->setContainer(app());
    $request->setRedirector(app('redirect'));
    $request->setUserResolver(fn() => $admin);
    $request->replace($testData);
    
    $validator = Validator::make($testData, $request->rules(), $request->messages());
    
    $formRequestPasses = !$validator->fails();
    $formRequestErrors = $validator->errors()->toArray();
    
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
    
    // Try to create - this will trigger validation
    try {
        $component->call('create');
        $filamentPasses = true;
        $filamentErrors = [];
    } catch (\Illuminate\Validation\ValidationException $e) {
        $filamentPasses = false;
        $filamentErrors = $e->errors();
    }
    
    // Property: Both should have the same validation outcome
    expect($filamentPasses)->toBe($formRequestPasses, 
        "Validation outcome mismatch. FormRequest: " . ($formRequestPasses ? 'pass' : 'fail') . 
        ", Filament: " . ($filamentPasses ? 'pass' : 'fail') .
        ". FormRequest errors: " . json_encode($formRequestErrors) .
        ". Filament errors: " . json_encode($filamentErrors)
    );
    
    // If both failed, verify they failed for similar reasons
    if (!$formRequestPasses && !$filamentPasses) {
        $formRequestErrorFields = array_keys($formRequestErrors);
        $filamentErrorFields = array_keys($filamentErrors);
        
        // Both should have errors on the same fields (allowing for password_confirmation)
        expect($filamentErrorFields)->toEqualCanonicalizing($formRequestErrorFields,
            "Error fields mismatch. FormRequest: " . json_encode($formRequestErrorFields) .
            ", Filament: " . json_encode($filamentErrorFields)
        );
    }
})->repeat(100);

// Feature: filament-admin-panel, Property 13: User validation consistency
// Validates: Requirements 6.4
test('Filament UserResource rejects invalid data consistently with StoreUserRequest', function () {
    // Create an admin user to perform the operation
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => null,
    ]);
    
    // Create a tenant for assignment
    $tenant = Tenant::factory()->create();
    
    // Act as the admin
    $this->actingAs($admin);
    
    // Generate INVALID test data (randomly choose one type of invalid data)
    $invalidationType = fake()->randomElement([
        'missing_name',
        'empty_name',
        'name_too_long',
        'missing_email',
        'invalid_email',
        'email_too_long',
        'duplicate_email',
        'missing_password',
        'password_too_short',
        'password_mismatch',
        'missing_role',
        'invalid_role',
        'missing_tenant_for_manager',
        'missing_tenant_for_tenant',
        'invalid_tenant_id',
    ]);
    
    $role = fake()->randomElement([UserRole::MANAGER->value, UserRole::TENANT->value]);
    
    $testData = [
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => $role,
        'tenant_id' => $tenant->id,
    ];
    
    // Apply the invalidation
    switch ($invalidationType) {
        case 'missing_name':
            unset($testData['name']);
            break;
        case 'empty_name':
            $testData['name'] = '';
            break;
        case 'name_too_long':
            $testData['name'] = str_repeat('a', 256); // Max is 255
            break;
        case 'missing_email':
            unset($testData['email']);
            break;
        case 'invalid_email':
            $testData['email'] = 'not-an-email';
            break;
        case 'email_too_long':
            $testData['email'] = str_repeat('a', 250) . '@test.com'; // Max is 255
            break;
        case 'duplicate_email':
            $existingUser = User::factory()->create();
            $testData['email'] = $existingUser->email;
            break;
        case 'missing_password':
            unset($testData['password']);
            unset($testData['password_confirmation']);
            break;
        case 'password_too_short':
            $testData['password'] = 'short';
            $testData['password_confirmation'] = 'short';
            break;
        case 'password_mismatch':
            $testData['password'] = 'password123';
            $testData['password_confirmation'] = 'different456';
            break;
        case 'missing_role':
            unset($testData['role']);
            break;
        case 'invalid_role':
            $testData['role'] = 'invalid_role';
            break;
        case 'missing_tenant_for_manager':
            $testData['role'] = UserRole::MANAGER->value;
            unset($testData['tenant_id']);
            break;
        case 'missing_tenant_for_tenant':
            $testData['role'] = UserRole::TENANT->value;
            unset($testData['tenant_id']);
            break;
        case 'invalid_tenant_id':
            $testData['tenant_id'] = 999999;
            break;
    }
    
    // Property: Both StoreUserRequest and Filament should reject invalid data
    
    // Test with StoreUserRequest
    $request = new StoreUserRequest();
    $request->setContainer(app());
    $request->setRedirector(app('redirect'));
    $request->setUserResolver(fn() => $admin);
    $request->replace($testData);
    
    $validator = Validator::make($testData, $request->rules(), $request->messages());
    
    $formRequestPasses = !$validator->fails();
    
    // Test with Filament form
    $component = Livewire::test(UserResource\Pages\CreateUser::class);
    
    $formData = [];
    
    if (isset($testData['name'])) {
        $formData['name'] = $testData['name'];
    }
    if (isset($testData['email'])) {
        $formData['email'] = $testData['email'];
    }
    if (isset($testData['password'])) {
        $formData['password'] = $testData['password'];
    }
    if (isset($testData['password_confirmation'])) {
        $formData['password_confirmation'] = $testData['password_confirmation'];
    }
    if (isset($testData['role'])) {
        $formData['role'] = $testData['role'];
    }
    if (isset($testData['tenant_id'])) {
        $formData['tenant_id'] = $testData['tenant_id'];
    }
    
    $component->fillForm($formData);
    
    try {
        $component->call('create');
        $filamentPasses = true;
    } catch (\Illuminate\Validation\ValidationException $e) {
        $filamentPasses = false;
    }
    
    // Property: Both should reject the invalid data
    expect($formRequestPasses)->toBeFalse("StoreUserRequest should reject invalid data (type: {$invalidationType})");
    expect($filamentPasses)->toBeFalse("Filament should reject invalid data (type: {$invalidationType})");
    
    // Property: Both should have the same validation outcome
    expect($filamentPasses)->toBe($formRequestPasses,
        "Validation outcome mismatch for {$invalidationType}. FormRequest: " . ($formRequestPasses ? 'pass' : 'fail') . 
        ", Filament: " . ($filamentPasses ? 'pass' : 'fail')
    );
})->repeat(100);

// Feature: filament-admin-panel, Property 13: User validation consistency
// Validates: Requirements 6.4
test('Filament UserResource applies same validation rules as UpdateUserRequest for edit operations', function () {
    // Create an admin user to perform the operation
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => null,
    ]);
    
    // Create a tenant for assignment
    $tenant = Tenant::factory()->create();
    
    // Create an existing user
    $existingUser = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenant->id,
    ]);
    
    // Act as the admin
    $this->actingAs($admin);
    
    // Generate random updated data (no password change)
    $testData = [
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'role' => fake()->randomElement([UserRole::MANAGER->value, UserRole::TENANT->value]),
        'tenant_id' => $tenant->id,
    ];
    
    // Property: Validation rules from UpdateUserRequest should match Filament validation
    
    // Test with UpdateUserRequest
    $request = new UpdateUserRequest();
    $request->setContainer(app());
    $request->setRedirector(app('redirect'));
    $request->setUserResolver(fn() => $admin);
    $request->setRouteResolver(function () use ($existingUser) {
        return (object) ['parameters' => ['user' => $existingUser]];
    });
    $request->replace($testData);
    
    $validator = Validator::make($testData, $request->rules(), $request->messages());
    
    $formRequestPasses = !$validator->fails();
    $formRequestErrors = $validator->errors()->toArray();
    
    // Test with Filament form
    $component = Livewire::test(UserResource\Pages\EditUser::class, [
        'record' => $existingUser->id,
    ]);
    
    $component->fillForm([
        'name' => $testData['name'],
        'email' => $testData['email'],
        'role' => $testData['role'],
        'tenant_id' => $testData['tenant_id'],
    ]);
    
    // Try to save - this will trigger validation
    try {
        $component->call('save');
        $filamentPasses = true;
        $filamentErrors = [];
    } catch (\Illuminate\Validation\ValidationException $e) {
        $filamentPasses = false;
        $filamentErrors = $e->errors();
    }
    
    // Property: Both should have the same validation outcome
    expect($filamentPasses)->toBe($formRequestPasses,
        "Validation outcome mismatch. FormRequest: " . ($formRequestPasses ? 'pass' : 'fail') . 
        ", Filament: " . ($filamentPasses ? 'pass' : 'fail') .
        ". FormRequest errors: " . json_encode($formRequestErrors) .
        ". Filament errors: " . json_encode($filamentErrors)
    );
    
    // If both failed, verify they failed for similar reasons
    if (!$formRequestPasses && !$filamentPasses) {
        $formRequestErrorFields = array_keys($formRequestErrors);
        $filamentErrorFields = array_keys($filamentErrors);
        
        // Both should have errors on the same fields
        expect($filamentErrorFields)->toEqualCanonicalizing($formRequestErrorFields,
            "Error fields mismatch. FormRequest: " . json_encode($formRequestErrorFields) .
            ", Filament: " . json_encode($filamentErrorFields)
        );
    }
})->repeat(100);

// Feature: filament-admin-panel, Property 13: User validation consistency
// Validates: Requirements 6.4
test('Filament UserResource rejects invalid updates consistently with UpdateUserRequest', function () {
    // Create an admin user to perform the operation
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => null,
    ]);
    
    // Create a tenant for assignment
    $tenant = Tenant::factory()->create();
    
    // Create an existing user
    $existingUser = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenant->id,
    ]);
    
    // Act as the admin
    $this->actingAs($admin);
    
    // Generate INVALID test data (randomly choose one type of invalid data)
    $invalidationType = fake()->randomElement([
        'missing_name',
        'empty_name',
        'name_too_long',
        'missing_email',
        'invalid_email',
        'email_too_long',
        'duplicate_email',
        'missing_role',
        'invalid_role',
        'missing_tenant_for_manager',
        'missing_tenant_for_tenant',
        'invalid_tenant_id',
    ]);
    
    $testData = [
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'role' => UserRole::MANAGER->value,
        'tenant_id' => $tenant->id,
    ];
    
    // Apply the invalidation
    switch ($invalidationType) {
        case 'missing_name':
            unset($testData['name']);
            break;
        case 'empty_name':
            $testData['name'] = '';
            break;
        case 'name_too_long':
            $testData['name'] = str_repeat('a', 256); // Max is 255
            break;
        case 'missing_email':
            unset($testData['email']);
            break;
        case 'invalid_email':
            $testData['email'] = 'not-an-email';
            break;
        case 'email_too_long':
            $testData['email'] = str_repeat('a', 250) . '@test.com'; // Max is 255
            break;
        case 'duplicate_email':
            $anotherUser = User::factory()->create();
            $testData['email'] = $anotherUser->email;
            break;
        case 'missing_role':
            unset($testData['role']);
            break;
        case 'invalid_role':
            $testData['role'] = 'invalid_role';
            break;
        case 'missing_tenant_for_manager':
            $testData['role'] = UserRole::MANAGER->value;
            unset($testData['tenant_id']);
            break;
        case 'missing_tenant_for_tenant':
            $testData['role'] = UserRole::TENANT->value;
            unset($testData['tenant_id']);
            break;
        case 'invalid_tenant_id':
            $testData['tenant_id'] = 999999;
            break;
    }
    
    // Property: Both UpdateUserRequest and Filament should reject invalid data
    
    // Test with UpdateUserRequest
    $request = new UpdateUserRequest();
    $request->setContainer(app());
    $request->setRedirector(app('redirect'));
    $request->setUserResolver(fn() => $admin);
    $request->setRouteResolver(function () use ($existingUser) {
        return (object) ['parameters' => ['user' => $existingUser]];
    });
    $request->replace($testData);
    
    $validator = Validator::make($testData, $request->rules(), $request->messages());
    
    $formRequestPasses = !$validator->fails();
    
    // Test with Filament form
    $component = Livewire::test(UserResource\Pages\EditUser::class, [
        'record' => $existingUser->id,
    ]);
    
    $formData = [];
    
    if (isset($testData['name'])) {
        $formData['name'] = $testData['name'];
    }
    if (isset($testData['email'])) {
        $formData['email'] = $testData['email'];
    }
    if (isset($testData['role'])) {
        $formData['role'] = $testData['role'];
    }
    if (isset($testData['tenant_id'])) {
        $formData['tenant_id'] = $testData['tenant_id'];
    }
    
    $component->fillForm($formData);
    
    try {
        $component->call('save');
        $filamentPasses = true;
    } catch (\Illuminate\Validation\ValidationException $e) {
        $filamentPasses = false;
    }
    
    // Property: Both should reject the invalid data
    expect($formRequestPasses)->toBeFalse("UpdateUserRequest should reject invalid data (type: {$invalidationType})");
    expect($filamentPasses)->toBeFalse("Filament should reject invalid data (type: {$invalidationType})");
    
    // Property: Both should have the same validation outcome
    expect($filamentPasses)->toBe($formRequestPasses,
        "Validation outcome mismatch for {$invalidationType}. FormRequest: " . ($formRequestPasses ? 'pass' : 'fail') . 
        ", Filament: " . ($filamentPasses ? 'pass' : 'fail')
    );
})->repeat(100);
