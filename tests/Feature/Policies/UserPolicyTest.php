<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

describe('UserPolicy - viewAny', function () {
    test('superadmin can view any users', function () {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        
        expect($superadmin->can('viewAny', User::class))->toBeTrue();
    });

    test('admin can view users list', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
        
        expect($admin->can('viewAny', User::class))->toBeTrue();
    });

    test('manager cannot view users list', function () {
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => 1]);
        
        expect($manager->can('viewAny', User::class))->toBeFalse();
    });

    test('tenant cannot view users list', function () {
        $tenant = User::factory()->create(['role' => UserRole::TENANT, 'tenant_id' => 1]);
        
        expect($tenant->can('viewAny', User::class))->toBeFalse();
    });
});

describe('UserPolicy - view', function () {
    test('superadmin can view any user', function () {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $otherUser = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
        
        expect($superadmin->can('view', $otherUser))->toBeTrue();
    });

    test('admin can view users within their tenant', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
        $sameTenanUser = User::factory()->create(['role' => UserRole::TENANT, 'tenant_id' => 1]);
        
        expect($admin->can('view', $sameTenanUser))->toBeTrue();
    });

    test('admin cannot view users from other tenants', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
        $otherTenantUser = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 2]);
        
        expect($admin->can('view', $otherTenantUser))->toBeFalse();
    });

    test('users can view themselves', function () {
        $user = User::factory()->create(['role' => UserRole::TENANT, 'tenant_id' => 1]);
        
        expect($user->can('view', $user))->toBeTrue();
    });

    test('tenant cannot view other users', function () {
        $tenant = User::factory()->create(['role' => UserRole::TENANT, 'tenant_id' => 1]);
        $otherUser = User::factory()->create(['role' => UserRole::TENANT, 'tenant_id' => 1]);
        
        expect($tenant->can('view', $otherUser))->toBeFalse();
    });
});

describe('UserPolicy - create', function () {
    test('superadmin can create users', function () {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        
        expect($superadmin->can('create', User::class))->toBeTrue();
    });

    test('admin can create users', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
        
        expect($admin->can('create', User::class))->toBeTrue();
    });

    test('manager cannot create users', function () {
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => 1]);
        
        expect($manager->can('create', User::class))->toBeFalse();
    });

    test('tenant cannot create users', function () {
        $tenant = User::factory()->create(['role' => UserRole::TENANT, 'tenant_id' => 1]);
        
        expect($tenant->can('create', User::class))->toBeFalse();
    });
});

describe('UserPolicy - update', function () {
    test('superadmin can update any user and operation is logged', function () {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $otherUser = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);

        Log::shouldReceive('channel')
            ->with('audit')
            ->once()
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->once()
            ->with('User update operation', \Mockery::type('array'));
        
        expect($superadmin->can('update', $otherUser))->toBeTrue();
    });

    test('admin can update users within their tenant', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
        $sameTenanUser = User::factory()->create(['role' => UserRole::TENANT, 'tenant_id' => 1]);
        
        expect($admin->can('update', $sameTenanUser))->toBeTrue();
    });

    test('admin cannot update users from other tenants', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
        $otherTenantUser = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 2]);
        
        expect($admin->can('update', $otherTenantUser))->toBeFalse();
    });

    test('users can update themselves', function () {
        $user = User::factory()->create(['role' => UserRole::TENANT, 'tenant_id' => 1]);
        
        expect($user->can('update', $user))->toBeTrue();
    });

    test('tenant cannot update other users', function () {
        $tenant = User::factory()->create(['role' => UserRole::TENANT, 'tenant_id' => 1]);
        $otherUser = User::factory()->create(['role' => UserRole::TENANT, 'tenant_id' => 1]);
        
        expect($tenant->can('update', $otherUser))->toBeFalse();
    });
});

describe('UserPolicy - delete', function () {
    test('superadmin can delete any user except themselves', function () {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $otherUser = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
        
        expect($superadmin->can('delete', $otherUser))->toBeTrue();
        expect($superadmin->can('delete', $superadmin))->toBeFalse();
    });

    test('admin can delete users within their tenant', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
        $sameTenanUser = User::factory()->create(['role' => UserRole::TENANT, 'tenant_id' => 1]);
        
        expect($admin->can('delete', $sameTenanUser))->toBeTrue();
    });

    test('admin cannot delete users from other tenants', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
        $otherTenantUser = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 2]);
        
        expect($admin->can('delete', $otherTenantUser))->toBeFalse();
    });

    test('users cannot delete themselves', function () {
        $user = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
        
        expect($user->can('delete', $user))->toBeFalse();
    });

    test('tenant cannot delete users', function () {
        $tenant = User::factory()->create(['role' => UserRole::TENANT, 'tenant_id' => 1]);
        $otherUser = User::factory()->create(['role' => UserRole::TENANT, 'tenant_id' => 1]);
        
        expect($tenant->can('delete', $otherUser))->toBeFalse();
    });

    test('delete operation is logged for audit', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
        $targetUser = User::factory()->create(['role' => UserRole::TENANT, 'tenant_id' => 1]);

        Log::shouldReceive('channel')
            ->with('audit')
            ->once()
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->once()
            ->with('User delete operation', \Mockery::type('array'));
        
        $admin->can('delete', $targetUser);
    });
});

describe('UserPolicy - restore', function () {
    test('superadmin can restore any user', function () {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $deletedUser = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
        
        expect($superadmin->can('restore', $deletedUser))->toBeTrue();
    });

    test('admin can restore users within their tenant', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
        $deletedUser = User::factory()->create(['role' => UserRole::TENANT, 'tenant_id' => 1]);
        
        expect($admin->can('restore', $deletedUser))->toBeTrue();
    });

    test('admin cannot restore users from other tenants', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
        $deletedUser = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 2]);
        
        expect($admin->can('restore', $deletedUser))->toBeFalse();
    });
});

describe('UserPolicy - forceDelete', function () {
    test('only superadmin can force delete users', function () {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $otherUser = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
        
        expect($superadmin->can('forceDelete', $otherUser))->toBeTrue();
    });

    test('superadmin cannot force delete themselves', function () {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        
        expect($superadmin->can('forceDelete', $superadmin))->toBeFalse();
    });

    test('admin cannot force delete users', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
        $otherUser = User::factory()->create(['role' => UserRole::TENANT, 'tenant_id' => 1]);
        
        expect($admin->can('forceDelete', $otherUser))->toBeFalse();
    });
});

describe('UserPolicy - impersonate', function () {
    test('superadmin can impersonate other users', function () {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $otherUser = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
        
        expect($superadmin->can('impersonate', $otherUser))->toBeTrue();
    });

    test('superadmin cannot impersonate themselves', function () {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        
        expect($superadmin->can('impersonate', $superadmin))->toBeFalse();
    });

    test('admin cannot impersonate users', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
        $otherUser = User::factory()->create(['role' => UserRole::TENANT, 'tenant_id' => 1]);
        
        expect($admin->can('impersonate', $otherUser))->toBeFalse();
    });

    test('impersonate operation is logged', function () {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $targetUser = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);

        Log::shouldReceive('channel')
            ->with('audit')
            ->once()
            ->andReturnSelf();
        
        Log::shouldReceive('info')
            ->once()
            ->with('User impersonate operation', \Mockery::type('array'));
        
        $superadmin->can('impersonate', $targetUser);
    });
});

describe('UserPolicy - replicate', function () {
    test('only superadmin can replicate users', function () {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $otherUser = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
        
        expect($superadmin->can('replicate', $otherUser))->toBeTrue();
    });

    test('admin cannot replicate users', function () {
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
        $otherUser = User::factory()->create(['role' => UserRole::TENANT, 'tenant_id' => 1]);
        
        expect($admin->can('replicate', $otherUser))->toBeFalse();
    });
});
