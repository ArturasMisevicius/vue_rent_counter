<?php

declare(strict_types=1);

use App\Models\Property;
use App\Models\User;
use App\Services\TenantBoundaryService;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

beforeEach(function () {
    $this->service = app(TenantBoundaryService::class);
});

describe('TenantBoundaryService', function () {
    describe('canAccessTenant', function () {
        it('allows superadmin to access any tenant', function () {
            $user = User::factory()->create();
            $user->assignRole('superadmin');
            
            expect($this->service->canAccessTenant($user, 123))->toBeTrue();
        });

        it('allows user to access their own tenant', function () {
            $user = User::factory()->create(['tenant_id' => 100]);
            
            expect($this->service->canAccessTenant($user, 100))->toBeTrue();
        });

        it('denies user access to different tenant', function () {
            $user = User::factory()->create(['tenant_id' => 100]);
            
            expect($this->service->canAccessTenant($user, 200))->toBeFalse();
        });
    });

    describe('canAccessModel', function () {
        it('allows access to model that belongs to user tenant', function () {
            $user = User::factory()->create(['tenant_id' => 100]);
            $property = Property::factory()->create(['tenant_id' => 100]);
            
            expect($this->service->canAccessModel($user, $property))->toBeTrue();
        });

        it('denies access to model from different tenant', function () {
            $user = User::factory()->create(['tenant_id' => 100]);
            $property = Property::factory()->create(['tenant_id' => 200]);
            
            expect($this->service->canAccessModel($user, $property))->toBeFalse();
        });

        it('allows superadmin to access any model', function () {
            $user = User::factory()->create(['tenant_id' => 100]);
            $user->assignRole('superadmin');
            $property = Property::factory()->create(['tenant_id' => 200]);
            
            expect($this->service->canAccessModel($user, $property))->toBeTrue();
        });

        it('allows access to models without tenant scoping', function () {
            $user = User::factory()->create(['tenant_id' => 100]);
            
            // Create a mock model that doesn't use BelongsToTenant trait
            $model = new class extends Model {
                protected $table = 'test_models';
            };
            
            expect($this->service->canAccessModel($user, $model))->toBeTrue();
        });
    });

    describe('canCreateForCurrentTenant', function () {
        it('allows creation when user has access to current tenant', function () {
            $user = User::factory()->create(['tenant_id' => 100]);
            $this->actingAs($user);
            
            expect($this->service->canCreateForCurrentTenant($user))->toBeTrue();
        });

        it('denies creation when no current tenant', function () {
            $user = User::factory()->create(['tenant_id' => null]);
            
            expect($this->service->canCreateForCurrentTenant($user))->toBeFalse();
        });
    });

    describe('hasRequiredRole', function () {
        it('returns true when user has one of the allowed roles', function () {
            $user = User::factory()->create();
            $user->assignRole('admin');
            
            expect($this->service->hasRequiredRole($user, ['admin', 'manager']))->toBeTrue();
        });

        it('returns false when user does not have allowed roles', function () {
            $user = User::factory()->create();
            $user->assignRole('tenant');
            
            expect($this->service->hasRequiredRole($user, ['admin', 'manager']))->toBeFalse();
        });
    });

    describe('canPerformAdminOperations', function () {
        it('allows admin operations for admin user', function () {
            $user = User::factory()->create();
            $user->assignRole('admin');
            
            expect($this->service->canPerformAdminOperations($user))->toBeTrue();
        });

        it('allows admin operations for superadmin user', function () {
            $user = User::factory()->create();
            $user->assignRole('superadmin');
            
            expect($this->service->canPerformAdminOperations($user))->toBeTrue();
        });

        it('denies admin operations for manager user', function () {
            $user = User::factory()->create();
            $user->assignRole('manager');
            
            expect($this->service->canPerformAdminOperations($user))->toBeFalse();
        });
    });

    describe('canPerformManagerOperations', function () {
        it('allows manager operations for manager user', function () {
            $user = User::factory()->create();
            $user->assignRole('manager');
            
            expect($this->service->canPerformManagerOperations($user))->toBeTrue();
        });

        it('allows manager operations for admin user', function () {
            $user = User::factory()->create();
            $user->assignRole('admin');
            
            expect($this->service->canPerformManagerOperations($user))->toBeTrue();
        });

        it('denies manager operations for tenant user', function () {
            $user = User::factory()->create();
            $user->assignRole('tenant');
            
            expect($this->service->canPerformManagerOperations($user))->toBeFalse();
        });
    });

    describe('getAccessibleTenantIds', function () {
        it('returns all tenant IDs for superadmin', function () {
            $user = User::factory()->create();
            $user->assignRole('superadmin');
            
            // Create some users with different tenant IDs
            User::factory()->create(['tenant_id' => 100]);
            User::factory()->create(['tenant_id' => 200]);
            User::factory()->create(['tenant_id' => 300]);
            
            $accessibleIds = $this->service->getAccessibleTenantIds($user);
            
            expect($accessibleIds)->toContain(100, 200, 300);
        });

        it('returns only user tenant ID for regular user', function () {
            $user = User::factory()->create(['tenant_id' => 100]);
            $user->assignRole('admin');
            
            $accessibleIds = $this->service->getAccessibleTenantIds($user);
            
            expect($accessibleIds)->toBe([100]);
        });

        it('returns empty array for user without tenant', function () {
            $user = User::factory()->create(['tenant_id' => null]);
            $user->assignRole('admin');
            
            $accessibleIds = $this->service->getAccessibleTenantIds($user);
            
            expect($accessibleIds)->toBe([]);
        });
    });
});