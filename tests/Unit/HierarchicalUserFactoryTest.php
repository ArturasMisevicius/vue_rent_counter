<?php

use App\Enums\UserRole;
use App\Models\User;

test('UserFactory can create superadmin users', function () {
    $superadmin = User::factory()->superadmin()->make();
    
    expect($superadmin->role)->toBe(UserRole::SUPERADMIN)
        ->and($superadmin->tenant_id)->toBeNull()
        ->and($superadmin->property_id)->toBeNull()
        ->and($superadmin->parent_user_id)->toBeNull()
        ->and($superadmin->organization_name)->toBeNull();
});

test('UserFactory can create admin users with tenant_id', function () {
    $admin = User::factory()->admin(123)->make();
    
    expect($admin->role)->toBe(UserRole::ADMIN)
        ->and($admin->tenant_id)->toBe(123)
        ->and($admin->property_id)->toBeNull()
        ->and($admin->parent_user_id)->toBeNull()
        ->and($admin->organization_name)->not->toBeNull();
});

test('UserFactory can create admin users with auto-generated tenant_id', function () {
    $admin1 = User::factory()->admin()->make();
    $admin2 = User::factory()->admin()->make();
    
    expect($admin1->role)->toBe(UserRole::ADMIN)
        ->and($admin1->tenant_id)->not->toBeNull()
        ->and($admin2->tenant_id)->not->toBeNull()
        ->and($admin1->tenant_id)->not->toBe($admin2->tenant_id);
});

test('UserFactory can create tenant users with property assignment', function () {
    $tenant = User::factory()->tenant(456, 789, 111)->make();
    
    expect($tenant->role)->toBe(UserRole::TENANT)
        ->and($tenant->tenant_id)->toBe(456)
        ->and($tenant->property_id)->toBe(789)
        ->and($tenant->parent_user_id)->toBe(111)
        ->and($tenant->organization_name)->toBeNull();
});

test('UserFactory can create inactive users', function () {
    $user = User::factory()->inactive()->make();
    
    expect($user->is_active)->toBeFalse();
});

test('UserFactory can create manager users (legacy)', function () {
    $manager = User::factory()->manager(999)->make();
    
    expect($manager->role)->toBe(UserRole::MANAGER)
        ->and($manager->tenant_id)->toBe(999)
        ->and($manager->property_id)->toBeNull()
        ->and($manager->parent_user_id)->toBeNull();
});
