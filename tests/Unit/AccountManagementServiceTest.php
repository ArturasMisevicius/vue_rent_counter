<?php

use App\Enums\SubscriptionPlanType;
use App\Enums\SubscriptionStatus;
use App\Exceptions\CannotDeleteWithDependenciesException;
use App\Exceptions\InvalidPropertyAssignmentException;
use App\Models\Property;
use App\Models\User;
use App\Notifications\TenantReassignedEmail;
use App\Notifications\WelcomeEmail;
use App\Services\AccountManagementService;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->subscriptionService = app(SubscriptionService::class);
    $this->accountService = new AccountManagementService($this->subscriptionService);
});

test('createAdminAccount creates admin with unique tenant_id and subscription', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);

    $data = [
        'email' => 'admin@example.com',
        'password' => 'password123',
        'name' => 'Test Admin',
        'organization_name' => 'Test Organization',
        'plan_type' => SubscriptionPlanType::BASIC->value,
        'expires_at' => now()->addYear()->toDateString(),
    ];

    $admin = $this->accountService->createAdminAccount($data, $superadmin);

    expect($admin)->toBeInstanceOf(User::class)
        ->and($admin->role->value)->toBe('admin')
        ->and($admin->email)->toBe('admin@example.com')
        ->and($admin->organization_name)->toBe('Test Organization')
        ->and($admin->tenant_id)->not->toBeNull()
        ->and($admin->is_active)->toBeTrue()
        ->and($admin->subscription)->not->toBeNull()
        ->and($admin->subscription->plan_type)->toBe(SubscriptionPlanType::BASIC->value);
});

test('createTenantAccount creates tenant inheriting admin tenant_id', function () {
    Notification::fake();

    $admin = User::factory()->create(['role' => 'admin', 'tenant_id' => 1000]);
    $subscription = $admin->subscription()->create([
        'plan_type' => SubscriptionPlanType::BASIC->value,
        'status' => SubscriptionStatus::ACTIVE->value,
        'starts_at' => now(),
        'expires_at' => now()->addYear(),
        'max_properties' => 10,
        'max_tenants' => 50,
    ]);

    $property = Property::factory()->create(['tenant_id' => 1000]);

    $data = [
        'email' => 'tenant@example.com',
        'password' => 'password123',
        'name' => 'Test Tenant',
        'property_id' => $property->id,
    ];

    $tenant = $this->accountService->createTenantAccount($data, $admin);

    expect($tenant)->toBeInstanceOf(User::class)
        ->and($tenant->role->value)->toBe('tenant')
        ->and($tenant->tenant_id)->toBe($admin->tenant_id)
        ->and($tenant->property_id)->toBe($property->id)
        ->and($tenant->parent_user_id)->toBe($admin->id)
        ->and($tenant->is_active)->toBeTrue();

    Notification::assertSentTo($tenant, WelcomeEmail::class);
});

test('createTenantAccount throws exception for property from different tenant', function () {
    $admin = User::factory()->create(['role' => 'admin', 'tenant_id' => 1000]);
    $admin->subscription()->create([
        'plan_type' => SubscriptionPlanType::BASIC->value,
        'status' => SubscriptionStatus::ACTIVE->value,
        'starts_at' => now(),
        'expires_at' => now()->addYear(),
        'max_properties' => 10,
        'max_tenants' => 50,
    ]);

    $property = Property::factory()->create(['tenant_id' => 2000]); // Different tenant

    $data = [
        'email' => 'tenant@example.com',
        'password' => 'password123',
        'name' => 'Test Tenant',
        'property_id' => $property->id,
    ];

    $this->accountService->createTenantAccount($data, $admin);
})->throws(InvalidPropertyAssignmentException::class);

test('reassignTenant updates property and creates audit log', function () {
    Notification::fake();

    $admin = User::factory()->create(['role' => 'admin', 'tenant_id' => 1000]);
    $oldProperty = Property::factory()->create(['tenant_id' => 1000]);
    $newProperty = Property::factory()->create(['tenant_id' => 1000]);
    
    $tenant = User::factory()->create([
        'role' => 'tenant',
        'tenant_id' => 1000,
        'property_id' => $oldProperty->id,
        'parent_user_id' => $admin->id,
    ]);

    $this->accountService->reassignTenant($tenant, $newProperty, $admin);

    $tenant->refresh();

    expect($tenant->property_id)->toBe($newProperty->id);

    // Check audit log
    $auditLog = DB::table('user_assignments_audit')
        ->where('user_id', $tenant->id)
        ->where('action', 'reassigned')
        ->first();

    expect($auditLog)->not->toBeNull()
        ->and($auditLog->property_id)->toBe($newProperty->id)
        ->and($auditLog->previous_property_id)->toBe($oldProperty->id)
        ->and($auditLog->performed_by)->toBe($admin->id);

    Notification::assertSentTo($tenant, TenantReassignedEmail::class);
});

test('deactivateAccount sets is_active to false and creates audit log', function () {
    $user = User::factory()->create(['is_active' => true]);

    $this->actingAs($user);
    $this->accountService->deactivateAccount($user, 'Test reason');

    $user->refresh();

    expect($user->is_active)->toBeFalse();

    // Check audit log
    $auditLog = DB::table('user_assignments_audit')
        ->where('user_id', $user->id)
        ->where('action', 'deactivated')
        ->first();

    expect($auditLog)->not->toBeNull()
        ->and($auditLog->reason)->toBe('Test reason');
});

test('reactivateAccount sets is_active to true', function () {
    $user = User::factory()->create(['is_active' => false]);

    $this->actingAs($user);
    $this->accountService->reactivateAccount($user);

    $user->refresh();

    expect($user->is_active)->toBeTrue();
});

test('deleteAccount throws exception when user has dependencies', function () {
    $user = User::factory()->create();
    
    // Create a child user (dependency)
    User::factory()->create(['parent_user_id' => $user->id]);

    $this->accountService->deleteAccount($user);
})->throws(CannotDeleteWithDependenciesException::class);

test('deleteAccount succeeds when user has no dependencies', function () {
    $user = User::factory()->create();

    $this->accountService->deleteAccount($user);

    expect(User::find($user->id))->toBeNull();
});
