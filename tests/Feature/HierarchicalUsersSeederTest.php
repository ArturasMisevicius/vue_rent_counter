<?php

use App\Enums\UserRole;
use App\Models\Building;
use App\Models\Property;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('UsersSeeder creates superadmin account', function () {
    // Seed buildings and properties first
    $this->seed(\Database\Seeders\TestBuildingsSeeder::class);
    $this->seed(\Database\Seeders\TestPropertiesSeeder::class);
    
    // Seed users
    $this->seed(\Database\Seeders\UsersSeeder::class);
    
    $superadmin = User::where('email', 'superadmin@example.com')->first();
    
    expect($superadmin)->not->toBeNull()
        ->and($superadmin->role)->toBe(UserRole::SUPERADMIN)
        ->and($superadmin->tenant_id)->toBeNull()
        ->and($superadmin->is_active)->toBeTrue();
});

test('UsersSeeder creates admin accounts with subscriptions', function () {
    $this->seed(\Database\Seeders\TestBuildingsSeeder::class);
    $this->seed(\Database\Seeders\TestPropertiesSeeder::class);
    $this->seed(\Database\Seeders\UsersSeeder::class);
    
    $admin1 = User::where('email', 'admin1@example.com')->first();
    
    expect($admin1)->not->toBeNull()
        ->and($admin1->role)->toBe(UserRole::ADMIN)
        ->and($admin1->tenant_id)->toBe(1)
        ->and($admin1->organization_name)->toBe('Vilnius Properties Ltd')
        ->and($admin1->is_active)->toBeTrue()
        ->and($admin1->subscription)->not->toBeNull()
        ->and($admin1->subscription->plan_type)->toBe('professional')
        ->and($admin1->subscription->status)->toBe('active');
});

test('UsersSeeder creates tenant accounts with property assignments', function () {
    $this->seed(\Database\Seeders\TestBuildingsSeeder::class);
    $this->seed(\Database\Seeders\TestPropertiesSeeder::class);
    $this->seed(\Database\Seeders\UsersSeeder::class);
    
    $tenant = User::where('email', 'jonas.petraitis@example.com')->first();
    
    expect($tenant)->not->toBeNull()
        ->and($tenant->role)->toBe(UserRole::TENANT)
        ->and($tenant->tenant_id)->toBe(1)
        ->and($tenant->property_id)->not->toBeNull()
        ->and($tenant->parent_user_id)->not->toBeNull()
        ->and($tenant->is_active)->toBeTrue();
    
    // Verify parent is admin
    expect($tenant->parentUser)->not->toBeNull()
        ->and($tenant->parentUser->role)->toBe(UserRole::ADMIN)
        ->and($tenant->parentUser->email)->toBe('admin1@example.com');
});

test('UsersSeeder creates inactive tenant', function () {
    $this->seed(\Database\Seeders\TestBuildingsSeeder::class);
    $this->seed(\Database\Seeders\TestPropertiesSeeder::class);
    $this->seed(\Database\Seeders\UsersSeeder::class);
    
    $inactiveTenant = User::where('email', 'deactivated@example.com')->first();
    
    expect($inactiveTenant)->not->toBeNull()
        ->and($inactiveTenant->is_active)->toBeFalse();
});

test('UsersSeeder creates admin with expired subscription', function () {
    $this->seed(\Database\Seeders\TestBuildingsSeeder::class);
    $this->seed(\Database\Seeders\TestPropertiesSeeder::class);
    $this->seed(\Database\Seeders\UsersSeeder::class);
    
    $admin3 = User::where('email', 'admin3@example.com')->first();
    
    expect($admin3)->not->toBeNull()
        ->and($admin3->subscription)->not->toBeNull()
        ->and($admin3->subscription->status)->toBe('expired')
        ->and($admin3->subscription->expires_at)->toBeLessThan(now());
});

test('UsersSeeder creates hierarchical test users', function () {
    $this->seed(\Database\Seeders\TestBuildingsSeeder::class);
    $this->seed(\Database\Seeders\TestPropertiesSeeder::class);
    $this->seed(\Database\Seeders\UsersSeeder::class);
    
    $admin = User::where('email', 'admin@test.com')->first();
    
    expect($admin)->not->toBeNull()
        ->and($admin->role)->toBe(UserRole::ADMIN)
        ->and($admin->tenant_id)->toBe(1)
        ->and($admin->organization_name)->toBe('Test Organization 1')
        ->and($admin->subscription)->not->toBeNull();
    
    $tenant = User::where('email', 'tenant@test.com')->first();
    
    expect($tenant)->not->toBeNull()
        ->and($tenant->role)->toBe(UserRole::TENANT)
        ->and($tenant->tenant_id)->toBe(1)
        ->and($tenant->property_id)->not->toBeNull()
        ->and($tenant->parent_user_id)->toBe($admin->id);
});

test('UsersSeeder respects tenant_id isolation', function () {
    $this->seed(\Database\Seeders\TestBuildingsSeeder::class);
    $this->seed(\Database\Seeders\TestPropertiesSeeder::class);
    $this->seed(\Database\Seeders\UsersSeeder::class);
    
    $admin1 = User::where('email', 'admin@test.com')->first();
    $admin2 = User::where('email', 'manager2@test.com')->first();
    
    expect($admin1->tenant_id)->toBe(1)
        ->and($admin2->tenant_id)->toBe(2)
        ->and($admin1->tenant_id)->not->toBe($admin2->tenant_id);
    
    // Verify tenants inherit correct tenant_id
    $tenant1 = User::where('email', 'tenant@test.com')->first();
    $tenant3 = User::where('email', 'tenant3@test.com')->first();
    
    expect($tenant1->tenant_id)->toBe(1)
        ->and($tenant3->tenant_id)->toBe(2);
});
