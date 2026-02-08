<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;

/**
 * Property Test: Null Tenant Allowance for Admin/Superadmin
 * 
 * Validates that Admin and Superadmin users can have null tenant_id.
 * 
 * Requirements: 6.6
 * Property: 15
 * 
 * @group property
 * @group user-resource
 */

test('admin users can have null tenant_id', function () {
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
    ]);
    
    actingAs($superadmin);
    
    // Create admin with null tenant_id
    $admin = User::create([
        'name' => 'Test Admin',
        'email' => 'admin' . uniqid() . '@example.com',
        'password' => bcrypt('password123'),
        'role' => UserRole::ADMIN,
        'tenant_id' => null,
        'is_active' => true,
    ]);
    
    expect($admin)->toBeInstanceOf(User::class)
        ->and($admin->role)->toBe(UserRole::ADMIN)
        ->and($admin->tenant_id)->toBeNull();
    
    // Verify admin can be retrieved
    $retrieved = User::find($admin->id);
    expect($retrieved)->not->toBeNull()
        ->and($retrieved->tenant_id)->toBeNull();
});

test('superadmin users can have null tenant_id', function () {
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
    ]);
    
    actingAs($superadmin);
    
    // Create another superadmin with null tenant_id
    $newSuperadmin = User::create([
        'name' => 'Test Superadmin',
        'email' => 'superadmin' . uniqid() . '@example.com',
        'password' => bcrypt('password123'),
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
        'is_active' => true,
    ]);
    
    expect($newSuperadmin)->toBeInstanceOf(User::class)
        ->and($newSuperadmin->role)->toBe(UserRole::SUPERADMIN)
        ->and($newSuperadmin->tenant_id)->toBeNull();
});

test('admin with null tenant_id can be persisted and retrieved', function () {
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
    ]);
    
    actingAs($superadmin);
    
    // Create admin with null tenant_id
    $admin = User::create([
        'name' => 'Persistent Admin',
        'email' => 'persistent' . uniqid() . '@example.com',
        'password' => bcrypt('password123'),
        'role' => UserRole::ADMIN,
        'tenant_id' => null,
        'is_active' => true,
    ]);
    
    // Refresh from database
    $admin->refresh();
    
    expect($admin->tenant_id)->toBeNull()
        ->and($admin->role)->toBe(UserRole::ADMIN);
    
    // Query from database
    $found = User::where('email', $admin->email)->first();
    
    expect($found)->not->toBeNull()
        ->and($found->tenant_id)->toBeNull()
        ->and($found->role)->toBe(UserRole::ADMIN);
});

test('null tenant_id allows superadmin to access all tenants', function () {
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
    ]);
    
    // Create users in different tenants
    User::factory()->count(5)->create(['tenant_id' => 1]);
    User::factory()->count(3)->create(['tenant_id' => 2]);
    
    actingAs($superadmin);
    
    // Superadmin should see all users (no tenant scope applied)
    $allUsers = User::all();
    expect($allUsers->count())->toBeGreaterThanOrEqual(9); // 5 + 3 + superadmin
    
    // Verify users from both tenants are included
    $tenant1Users = $allUsers->where('tenant_id', 1);
    $tenant2Users = $allUsers->where('tenant_id', 2);
    
    expect($tenant1Users->count())->toBeGreaterThanOrEqual(5)
        ->and($tenant2Users->count())->toBeGreaterThanOrEqual(3);
});

test('admin with null tenant_id creates isolated organization', function () {
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
    ]);
    
    actingAs($superadmin);
    
    // Create admin with null tenant_id (new organization)
    $admin = User::create([
        'name' => 'New Org Admin',
        'email' => 'neworg' . uniqid() . '@example.com',
        'password' => bcrypt('password123'),
        'role' => UserRole::ADMIN,
        'tenant_id' => null,
        'is_active' => true,
    ]);
    
    expect($admin->tenant_id)->toBeNull();
    
    // This admin can later be assigned as tenant for other users
    $manager = User::create([
        'name' => 'Manager under Admin',
        'email' => 'manager' . uniqid() . '@example.com',
        'password' => bcrypt('password123'),
        'role' => UserRole::MANAGER,
        'tenant_id' => $admin->id, // Admin becomes the tenant
        'is_active' => true,
    ]);
    
    expect($manager->tenant_id)->toBe($admin->id)
        ->and($manager->parentUser->id)->toBe($admin->id);
});

test('database schema allows null tenant_id', function () {
    // Verify database schema allows null tenant_id
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
    ]);
    
    actingAs($superadmin);
    
    // Create multiple users with null tenant_id
    $users = collect();
    
    for ($i = 0; $i < 5; $i++) {
        $user = User::create([
            'name' => "Admin $i",
            'email' => "admin$i" . uniqid() . '@example.com',
            'password' => bcrypt('password123'),
            'role' => UserRole::ADMIN,
            'tenant_id' => null,
            'is_active' => true,
        ]);
        
        $users->push($user);
    }
    
    // Verify all users have null tenant_id
    $users->each(function ($user) {
        expect($user->tenant_id)->toBeNull();
    });
    
    // Verify they can all be retrieved
    $retrieved = User::whereIn('id', $users->pluck('id'))->get();
    
    expect($retrieved->count())->toBe(5);
    
    $retrieved->each(function ($user) {
        expect($user->tenant_id)->toBeNull();
    });
});

test('null tenant_id does not bypass authorization', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => null,
    ]);
    
    actingAs($admin);
    
    // Create another admin in a different tenant
    $otherAdmin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);
    
    // Admin with null tenant_id should still respect authorization
    // (This is enforced by policies, not tenant scope)
    expect($admin->can('view', $otherAdmin))->toBeFalse();
});

test('queries handle null tenant_id correctly', function () {
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
    ]);
    
    actingAs($superadmin);
    
    // Create users with various tenant_id values
    $nullTenantAdmin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => null,
    ]);
    
    $tenant1Admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);
    
    $tenant2Admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 2,
    ]);
    
    // Query for null tenant_id
    $nullTenantUsers = User::whereNull('tenant_id')->get();
    expect($nullTenantUsers->count())->toBeGreaterThanOrEqual(2); // superadmin + nullTenantAdmin
    
    // Query for specific tenant_id
    $tenant1Users = User::where('tenant_id', 1)->get();
    expect($tenant1Users->count())->toBeGreaterThanOrEqual(1);
    
    // Query for any tenant_id (including null)
    $allUsers = User::all();
    expect($allUsers->count())->toBeGreaterThanOrEqual(4);
});

test('admin can transition from null to assigned tenant_id', function () {
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
    ]);
    
    actingAs($superadmin);
    
    // Create admin with null tenant_id
    $admin = User::create([
        'name' => 'Transitioning Admin',
        'email' => 'transition' . uniqid() . '@example.com',
        'password' => bcrypt('password123'),
        'role' => UserRole::ADMIN,
        'tenant_id' => null,
        'is_active' => true,
    ]);
    
    expect($admin->tenant_id)->toBeNull();
    
    // Assign tenant_id
    $admin->tenant_id = 1;
    $admin->save();
    
    expect($admin->tenant_id)->toBe(1);
    
    // Verify persistence
    $admin->refresh();
    expect($admin->tenant_id)->toBe(1);
});

test('superadmin with null tenant_id bypasses tenant scope', function () {
    // Create superadmin with null tenant_id
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
    ]);
    
    // Create users in different tenants
    $tenant1Users = User::factory()->count(3)->create(['tenant_id' => 1]);
    $tenant2Users = User::factory()->count(2)->create(['tenant_id' => 2]);
    
    actingAs($superadmin);
    
    // Superadmin should see all users regardless of tenant
    $allUsers = User::all();
    
    expect($allUsers->count())->toBeGreaterThanOrEqual(6); // 3 + 2 + superadmin
    
    // Verify superadmin can access users from all tenants
    $tenant1Count = $allUsers->where('tenant_id', 1)->count();
    $tenant2Count = $allUsers->where('tenant_id', 2)->count();
    
    expect($tenant1Count)->toBeGreaterThanOrEqual(3)
        ->and($tenant2Count)->toBeGreaterThanOrEqual(2);
});

