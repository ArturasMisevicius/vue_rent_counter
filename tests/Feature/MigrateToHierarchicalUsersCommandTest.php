<?php

use App\Enums\UserRole;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('command migrates manager users to admin role', function () {
    // Create a manager user without subscription
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => 1,
        'is_active' => true,
    ]);

    // Run the migration command
    $this->artisan('users:migrate-hierarchical')
        ->assertSuccessful();

    // Verify the user was converted to admin
    $manager->refresh();
    expect($manager->role)->toBe(UserRole::ADMIN);
    expect($manager->is_active)->toBeTrue();
    expect($manager->organization_name)->not->toBeNull();
    
    // Verify subscription was created
    expect($manager->subscription)->not->toBeNull();
    expect($manager->subscription->status)->toBe('active');
    expect($manager->subscription->plan_type)->toBe('professional');
});

test('command assigns unique tenant_id to users without one', function () {
    // Create admin users without tenant_id
    $admin1 = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => null,
        'is_active' => true,
    ]);
    
    $admin2 = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => null,
        'is_active' => true,
    ]);

    // Run the migration command
    $this->artisan('users:migrate-hierarchical')
        ->assertSuccessful();

    // Verify unique tenant_ids were assigned
    $admin1->refresh();
    $admin2->refresh();
    
    expect($admin1->tenant_id)->not->toBeNull();
    expect($admin2->tenant_id)->not->toBeNull();
    expect($admin1->tenant_id)->not->toBe($admin2->tenant_id);
});

test('command creates subscriptions for admin users without one', function () {
    // Create an admin user without subscription
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
        'is_active' => true,
    ]);

    expect($admin->subscription)->toBeNull();

    // Run the migration command
    $this->artisan('users:migrate-hierarchical')
        ->assertSuccessful();

    // Verify subscription was created
    $admin->refresh();
    expect($admin->subscription)->not->toBeNull();
    expect($admin->subscription->status)->toBe('active');
    expect($admin->subscription->max_properties)->toBe(50);
    expect($admin->subscription->max_tenants)->toBe(200);
});

test('command sets is_active to true for all users', function () {
    // Create users with is_active = false
    $user1 = User::factory()->create([
        'role' => UserRole::TENANT,
        'is_active' => false,
    ]);
    
    $user2 = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
        'is_active' => false,
    ]);

    // Run the migration command
    $this->artisan('users:migrate-hierarchical')
        ->assertSuccessful();

    // Verify all users are active
    $user1->refresh();
    $user2->refresh();
    
    expect($user1->is_active)->toBeTrue();
    expect($user2->is_active)->toBeTrue();
});

test('command dry-run does not make changes', function () {
    // Create a manager user
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => 1,
        'is_active' => true,
    ]);

    $originalRole = $manager->role;
    $subscriptionCountBefore = Subscription::count();

    // Run the migration command with dry-run
    $this->artisan('users:migrate-hierarchical --dry-run')
        ->assertSuccessful();

    // Verify no changes were made
    $manager->refresh();
    expect($manager->role)->toBe($originalRole);
    expect(Subscription::count())->toBe($subscriptionCountBefore);
});

test('command rollback reverts admin to manager and removes subscriptions', function () {
    // Create admin users with subscriptions
    $admin1 = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
        'organization_name' => 'Test Org',
        'is_active' => true,
    ]);
    
    $subscription1 = Subscription::factory()->create([
        'user_id' => $admin1->id,
        'status' => 'active',
    ]);

    $admin2 = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 2,
        'organization_name' => 'Test Org 2',
        'is_active' => true,
    ]);
    
    $subscription2 = Subscription::factory()->create([
        'user_id' => $admin2->id,
        'status' => 'active',
    ]);

    expect(Subscription::count())->toBe(2);
    expect(User::where('role', UserRole::ADMIN)->count())->toBe(2);

    // Run the rollback command (with confirmation)
    $this->artisan('users:migrate-hierarchical --rollback')
        ->expectsConfirmation('This will revert admin roles back to manager and remove subscriptions. Continue?', 'yes')
        ->assertSuccessful();

    // Verify subscriptions were deleted
    expect(Subscription::count())->toBe(0);

    // Verify admins were reverted to manager
    $admin1->refresh();
    $admin2->refresh();
    
    expect($admin1->role)->toBe(UserRole::MANAGER);
    expect($admin2->role)->toBe(UserRole::MANAGER);
    expect($admin1->tenant_id)->toBeNull();
    expect($admin2->tenant_id)->toBeNull();
    expect($admin1->organization_name)->toBeNull();
    expect($admin2->organization_name)->toBeNull();
});

test('command rollback can be cancelled', function () {
    // Create admin user with subscription
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
        'is_active' => true,
    ]);
    
    $subscription = Subscription::factory()->create([
        'user_id' => $admin->id,
        'status' => 'active',
    ]);

    // Run the rollback command but cancel it
    $this->artisan('users:migrate-hierarchical --rollback')
        ->expectsConfirmation('This will revert admin roles back to manager and remove subscriptions. Continue?', 'no')
        ->assertSuccessful();

    // Verify nothing changed
    $admin->refresh();
    expect($admin->role)->toBe(UserRole::ADMIN);
    expect(Subscription::count())->toBe(1);
});

test('command does not create duplicate subscriptions', function () {
    // Create admin user with existing subscription
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
        'is_active' => true,
    ]);
    
    $existingSubscription = Subscription::factory()->create([
        'user_id' => $admin->id,
        'status' => 'active',
    ]);

    $subscriptionCountBefore = Subscription::count();

    // Run the migration command
    $this->artisan('users:migrate-hierarchical')
        ->assertSuccessful();

    // Verify no new subscription was created
    expect(Subscription::count())->toBe($subscriptionCountBefore);
    
    $admin->refresh();
    expect($admin->subscription->id)->toBe($existingSubscription->id);
});
