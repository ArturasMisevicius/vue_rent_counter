# TariffPolicy Test Recommendations

## Overview

This document provides comprehensive test recommendations for the TariffPolicy following the SUPERADMIN support enhancement. It covers unit tests, integration tests, feature tests, security tests, and property-based tests.

**Date**: November 26, 2025  
**Status**: Implementation Guide

---

## 1. Unit Tests (COMPLETE ✅)

### Current Coverage

**File**: `tests/Unit/Policies/TariffPolicyTest.php`

**Tests**: 6 tests, 28 assertions, 100% coverage

1. ✅ `test_all_roles_can_view_tariffs()` - 8 assertions
2. ✅ `test_only_admins_can_create_tariffs()` - 4 assertions
3. ✅ `test_only_admins_can_update_tariffs()` - 4 assertions
4. ✅ `test_only_admins_can_delete_tariffs()` - 4 assertions
5. ✅ `test_only_admins_can_restore_tariffs()` - 4 assertions (NEW)
6. ✅ `test_only_superadmins_can_force_delete_tariffs()` - 4 assertions

### Recommendations

**Status**: No additional unit tests needed. Current coverage is comprehensive.

---

## 2. Integration Tests (RECOMMENDED)

### Filament Resource Integration

**File**: `tests/Feature/Filament/TariffResourceTest.php` (TO CREATE)

**Purpose**: Verify TariffPolicy integration with Filament resources

#### Test Cases

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Filament\Resources\TariffResource;
use App\Models\Tariff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TariffResourceTest
 * 
 * Integration tests for TariffResource with TariffPolicy.
 * 
 * @group filament
 * @group integration
 * @group tariffs
 */
class TariffResourceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that superadmin can access tariff create page.
     */
    public function test_superadmin_can_access_create_page(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        
        $this->actingAs($superadmin);
        
        $response = $this->get(TariffResource::getUrl('create'));
        
        $response->assertOk();
    }

    /**
     * Test that admin can access tariff create page.
     */
    public function test_admin_can_access_create_page(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        
        $this->actingAs($admin);
        
        $response = $this->get(TariffResource::getUrl('create'));
        
        $response->assertOk();
    }

    /**
     * Test that manager cannot access tariff create page.
     */
    public function test_manager_cannot_access_create_page(): void
    {
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        
        $this->actingAs($manager);
        
        $response = $this->get(TariffResource::getUrl('create'));
        
        $response->assertForbidden();
    }

    /**
     * Test that tenant cannot access tariff create page.
     */
    public function test_tenant_cannot_access_create_page(): void
    {
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);
        
        $this->actingAs($tenant);
        
        $response = $this->get(TariffResource::getUrl('create'));
        
        $response->assertForbidden();
    }

    /**
     * Test that superadmin can edit tariff.
     */
    public function test_superadmin_can_edit_tariff(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $tariff = Tariff::factory()->create();
        
        $this->actingAs($superadmin);
        
        $response = $this->get(TariffResource::getUrl('edit', ['record' => $tariff]));
        
        $response->assertOk();
    }

    /**
     * Test that admin can edit tariff.
     */
    public function test_admin_can_edit_tariff(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $tariff = Tariff::factory()->create();
        
        $this->actingAs($admin);
        
        $response = $this->get(TariffResource::getUrl('edit', ['record' => $tariff]));
        
        $response->assertOk();
    }

    /**
     * Test that manager cannot edit tariff.
     */
    public function test_manager_cannot_edit_tariff(): void
    {
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tariff = Tariff::factory()->create();
        
        $this->actingAs($manager);
        
        $response = $this->get(TariffResource::getUrl('edit', ['record' => $tariff]));
        
        $response->assertForbidden();
    }

    /**
     * Test that superadmin can delete tariff.
     */
    public function test_superadmin_can_delete_tariff(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $tariff = Tariff::factory()->create();
        
        $this->actingAs($superadmin);
        
        $response = $this->delete(TariffResource::getUrl('delete', ['record' => $tariff]));
        
        $response->assertRedirect();
        $this->assertSoftDeleted('tariffs', ['id' => $tariff->id]);
    }

    /**
     * Test that admin can delete tariff.
     */
    public function test_admin_can_delete_tariff(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $tariff = Tariff::factory()->create();
        
        $this->actingAs($admin);
        
        $response = $this->delete(TariffResource::getUrl('delete', ['record' => $tariff]));
        
        $response->assertRedirect();
        $this->assertSoftDeleted('tariffs', ['id' => $tariff->id]);
    }

    /**
     * Test that manager cannot delete tariff.
     */
    public function test_manager_cannot_delete_tariff(): void
    {
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tariff = Tariff::factory()->create();
        
        $this->actingAs($manager);
        
        $response = $this->delete(TariffResource::getUrl('delete', ['record' => $tariff]));
        
        $response->assertForbidden();
        $this->assertDatabaseHas('tariffs', ['id' => $tariff->id, 'deleted_at' => null]);
    }

    /**
     * Test that only superadmin can force delete tariff.
     */
    public function test_only_superadmin_can_force_delete_tariff(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $tariff = Tariff::factory()->create();
        
        // Soft delete first
        $tariff->delete();
        
        // Admin cannot force delete
        $this->actingAs($admin);
        $response = $this->delete(TariffResource::getUrl('force-delete', ['record' => $tariff]));
        $response->assertForbidden();
        
        // Superadmin can force delete
        $this->actingAs($superadmin);
        $response = $this->delete(TariffResource::getUrl('force-delete', ['record' => $tariff]));
        $response->assertRedirect();
        $this->assertDatabaseMissing('tariffs', ['id' => $tariff->id]);
    }

    /**
     * Test that all roles can view tariff list.
     */
    public function test_all_roles_can_view_tariff_list(): void
    {
        $roles = [
            UserRole::SUPERADMIN,
            UserRole::ADMIN,
            UserRole::MANAGER,
            UserRole::TENANT,
        ];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);
            
            $this->actingAs($user);
            
            $response = $this->get(TariffResource::getUrl('index'));
            
            $response->assertOk();
        }
    }

    /**
     * Test that all roles can view individual tariff.
     */
    public function test_all_roles_can_view_individual_tariff(): void
    {
        $tariff = Tariff::factory()->create();
        
        $roles = [
            UserRole::SUPERADMIN,
            UserRole::ADMIN,
            UserRole::MANAGER,
            UserRole::TENANT,
        ];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);
            
            $this->actingAs($user);
            
            $response = $this->get(TariffResource::getUrl('view', ['record' => $tariff]));
            
            $response->assertOk();
        }
    }
}
```

**Estimated Tests**: 13 tests, ~40 assertions

---

## 3. Feature Tests (RECOMMENDED)

### Controller Integration

**File**: `tests/Feature/TariffControllerTest.php` (TO CREATE)

**Purpose**: Verify TariffPolicy integration with controllers

#### Test Cases

```php
<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Provider;
use App\Models\Tariff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TariffControllerTest
 * 
 * Feature tests for tariff controller endpoints.
 * 
 * @group feature
 * @group controllers
 * @group tariffs
 */
class TariffControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that superadmin can create tariff via POST.
     */
    public function test_superadmin_can_create_tariff_via_post(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $provider = Provider::factory()->create();
        
        $this->actingAs($superadmin);
        
        $response = $this->post(route('tariffs.store'), [
            'name' => 'Test Tariff',
            'provider_id' => $provider->id,
            'type' => 'flat',
            'rate' => 0.20,
        ]);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('tariffs', ['name' => 'Test Tariff']);
    }

    /**
     * Test that admin can create tariff via POST.
     */
    public function test_admin_can_create_tariff_via_post(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $provider = Provider::factory()->create();
        
        $this->actingAs($admin);
        
        $response = $this->post(route('tariffs.store'), [
            'name' => 'Test Tariff',
            'provider_id' => $provider->id,
            'type' => 'flat',
            'rate' => 0.20,
        ]);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('tariffs', ['name' => 'Test Tariff']);
    }

    /**
     * Test that manager cannot create tariff via POST.
     */
    public function test_manager_cannot_create_tariff_via_post(): void
    {
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $provider = Provider::factory()->create();
        
        $this->actingAs($manager);
        
        $response = $this->post(route('tariffs.store'), [
            'name' => 'Test Tariff',
            'provider_id' => $provider->id,
            'type' => 'flat',
            'rate' => 0.20,
        ]);
        
        $response->assertForbidden();
        $this->assertDatabaseMissing('tariffs', ['name' => 'Test Tariff']);
    }

    /**
     * Test that superadmin can update tariff via PUT.
     */
    public function test_superadmin_can_update_tariff_via_put(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $tariff = Tariff::factory()->create(['name' => 'Original Name']);
        
        $this->actingAs($superadmin);
        
        $response = $this->put(route('tariffs.update', $tariff), [
            'name' => 'Updated Name',
        ]);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('tariffs', ['id' => $tariff->id, 'name' => 'Updated Name']);
    }

    /**
     * Test that admin can update tariff via PUT.
     */
    public function test_admin_can_update_tariff_via_put(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $tariff = Tariff::factory()->create(['name' => 'Original Name']);
        
        $this->actingAs($admin);
        
        $response = $this->put(route('tariffs.update', $tariff), [
            'name' => 'Updated Name',
        ]);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('tariffs', ['id' => $tariff->id, 'name' => 'Updated Name']);
    }

    /**
     * Test that manager cannot update tariff via PUT.
     */
    public function test_manager_cannot_update_tariff_via_put(): void
    {
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tariff = Tariff::factory()->create(['name' => 'Original Name']);
        
        $this->actingAs($manager);
        
        $response = $this->put(route('tariffs.update', $tariff), [
            'name' => 'Updated Name',
        ]);
        
        $response->assertForbidden();
        $this->assertDatabaseHas('tariffs', ['id' => $tariff->id, 'name' => 'Original Name']);
    }

    /**
     * Test that superadmin can delete tariff via DELETE.
     */
    public function test_superadmin_can_delete_tariff_via_delete(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $tariff = Tariff::factory()->create();
        
        $this->actingAs($superadmin);
        
        $response = $this->delete(route('tariffs.destroy', $tariff));
        
        $response->assertRedirect();
        $this->assertSoftDeleted('tariffs', ['id' => $tariff->id]);
    }

    /**
     * Test that admin can delete tariff via DELETE.
     */
    public function test_admin_can_delete_tariff_via_delete(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $tariff = Tariff::factory()->create();
        
        $this->actingAs($admin);
        
        $response = $this->delete(route('tariffs.destroy', $tariff));
        
        $response->assertRedirect();
        $this->assertSoftDeleted('tariffs', ['id' => $tariff->id]);
    }

    /**
     * Test that manager cannot delete tariff via DELETE.
     */
    public function test_manager_cannot_delete_tariff_via_delete(): void
    {
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tariff = Tariff::factory()->create();
        
        $this->actingAs($manager);
        
        $response = $this->delete(route('tariffs.destroy', $tariff));
        
        $response->assertForbidden();
        $this->assertDatabaseHas('tariffs', ['id' => $tariff->id, 'deleted_at' => null]);
    }
}
```

**Estimated Tests**: 9 tests, ~27 assertions

---

## 4. Security Tests (COMPLETE ✅)

### Current Coverage

**File**: `tests/Security/TariffPolicySecurityTest.php`

**Tests**: 17 tests covering:
- Unauthenticated access prevention
- Role-based authorization enforcement
- Audit logging verification
- Force delete restrictions
- Authorization matrix validation

**Status**: Comprehensive security coverage already in place.

---

## 5. Property-Based Tests (RECOMMENDED)

### Authorization Invariants

**File**: `tests/Feature/PropertyTests/TariffAuthorizationPropertyTest.php` (TO CREATE)

**Purpose**: Verify authorization invariants hold across random role/action combinations

#### Test Cases

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\PropertyTests;

use App\Enums\UserRole;
use App\Models\Tariff;
use App\Models\User;
use App\Policies\TariffPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TariffAuthorizationPropertyTest
 * 
 * Property-based tests for tariff authorization invariants.
 * 
 * @group property
 * @group authorization
 * @group tariffs
 */
class TariffAuthorizationPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property: Role hierarchy is consistent.
     * 
     * SUPERADMIN ⊇ ADMIN ⊇ MANAGER ⊇ TENANT
     */
    public function test_role_hierarchy_is_consistent(): void
    {
        $policy = new TariffPolicy();
        $tariff = Tariff::factory()->create();

        $actions = ['viewAny', 'view', 'create', 'update', 'delete', 'restore', 'forceDelete'];
        
        $superadminPermissions = [];
        $adminPermissions = [];
        $managerPermissions = [];
        $tenantPermissions = [];

        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        foreach ($actions as $action) {
            $superadminPermissions[$action] = $this->checkPermission($policy, $superadmin, $tariff, $action);
            $adminPermissions[$action] = $this->checkPermission($policy, $admin, $tariff, $action);
            $managerPermissions[$action] = $this->checkPermission($policy, $manager, $tariff, $action);
            $tenantPermissions[$action] = $this->checkPermission($policy, $tenant, $tariff, $action);
        }

        // Property: SUPERADMIN has all permissions ADMIN has
        foreach ($actions as $action) {
            if ($adminPermissions[$action]) {
                $this->assertTrue(
                    $superadminPermissions[$action],
                    "SUPERADMIN should have {$action} if ADMIN has it"
                );
            }
        }

        // Property: ADMIN has all permissions MANAGER has
        foreach ($actions as $action) {
            if ($managerPermissions[$action]) {
                $this->assertTrue(
                    $adminPermissions[$action],
                    "ADMIN should have {$action} if MANAGER has it"
                );
            }
        }

        // Property: MANAGER has all permissions TENANT has
        foreach ($actions as $action) {
            if ($tenantPermissions[$action]) {
                $this->assertTrue(
                    $managerPermissions[$action],
                    "MANAGER should have {$action} if TENANT has it"
                );
            }
        }
    }

    /**
     * Property: View permissions are universal.
     * 
     * All roles can view tariffs.
     */
    public function test_view_permissions_are_universal(): void
    {
        $policy = new TariffPolicy();
        $tariff = Tariff::factory()->create();

        $roles = [
            UserRole::SUPERADMIN,
            UserRole::ADMIN,
            UserRole::MANAGER,
            UserRole::TENANT,
        ];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);
            
            $this->assertTrue(
                $policy->viewAny($user),
                "Role {$role->value} should be able to viewAny"
            );
            
            $this->assertTrue(
                $policy->view($user, $tariff),
                "Role {$role->value} should be able to view"
            );
        }
    }

    /**
     * Property: Mutation permissions are restricted.
     * 
     * Only ADMIN and SUPERADMIN can mutate tariffs.
     */
    public function test_mutation_permissions_are_restricted(): void
    {
        $policy = new TariffPolicy();
        $tariff = Tariff::factory()->create();

        $mutationActions = ['create', 'update', 'delete', 'restore'];

        // SUPERADMIN and ADMIN can mutate
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        foreach ($mutationActions as $action) {
            $this->assertTrue(
                $this->checkPermission($policy, $superadmin, $tariff, $action),
                "SUPERADMIN should be able to {$action}"
            );
            
            $this->assertTrue(
                $this->checkPermission($policy, $admin, $tariff, $action),
                "ADMIN should be able to {$action}"
            );
        }

        // MANAGER and TENANT cannot mutate
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        foreach ($mutationActions as $action) {
            $this->assertFalse(
                $this->checkPermission($policy, $manager, $tariff, $action),
                "MANAGER should not be able to {$action}"
            );
            
            $this->assertFalse(
                $this->checkPermission($policy, $tenant, $tariff, $action),
                "TENANT should not be able to {$action}"
            );
        }
    }

    /**
     * Property: Force delete is exclusive to SUPERADMIN.
     */
    public function test_force_delete_is_exclusive_to_superadmin(): void
    {
        $policy = new TariffPolicy();
        $tariff = Tariff::factory()->create();

        $roles = [
            UserRole::SUPERADMIN => true,
            UserRole::ADMIN => false,
            UserRole::MANAGER => false,
            UserRole::TENANT => false,
        ];

        foreach ($roles as $role => $expected) {
            $user = User::factory()->create(['role' => $role]);
            
            $actual = $policy->forceDelete($user, $tariff);
            
            $this->assertEquals(
                $expected,
                $actual,
                "Role {$role->value} forceDelete permission should be " . ($expected ? 'true' : 'false')
            );
        }
    }

    /**
     * Helper method to check permission.
     */
    private function checkPermission(TariffPolicy $policy, User $user, Tariff $tariff, string $action): bool
    {
        return match ($action) {
            'viewAny' => $policy->viewAny($user),
            'view' => $policy->view($user, $tariff),
            'create' => $policy->create($user),
            'update' => $policy->update($user, $tariff),
            'delete' => $policy->delete($user, $tariff),
            'restore' => $policy->restore($user, $tariff),
            'forceDelete' => $policy->forceDelete($user, $tariff),
            default => false,
        };
    }
}
```

**Estimated Tests**: 4 property tests, ~50 assertions

---

## 6. Performance Tests (OPTIONAL)

### Policy Check Performance

**File**: `tests/Performance/TariffPolicyPerformanceTest.php` (OPTIONAL)

**Purpose**: Ensure policy checks remain fast

#### Test Case

```php
<?php

declare(strict_types=1);

namespace Tests\Performance;

use App\Enums\UserRole;
use App\Models\Tariff;
use App\Models\User;
use App\Policies\TariffPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TariffPolicyPerformanceTest
 * 
 * Performance tests for tariff policy checks.
 * 
 * @group performance
 * @group policies
 */
class TariffPolicyPerformanceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that policy checks complete within acceptable time.
     */
    public function test_policy_checks_complete_within_acceptable_time(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        $tariff = Tariff::factory()->create();
        $policy = new TariffPolicy();
        
        $iterations = 10000;
        $start = microtime(true);
        
        for ($i = 0; $i < $iterations; $i++) {
            $policy->create($user);
            $policy->update($user, $tariff);
            $policy->delete($user, $tariff);
        }
        
        $duration = microtime(true) - $start;
        $avgTime = ($duration / $iterations) * 1000; // ms
        
        // Should complete 10,000 checks in < 100ms (< 0.01ms per check)
        $this->assertLessThan(0.01, $avgTime, 'Policy checks should be < 0.01ms each');
    }
}
```

**Estimated Tests**: 1 performance test

---

## Summary

### Test Coverage Overview

| Test Type | File | Tests | Assertions | Status |
|-----------|------|-------|------------|--------|
| Unit | TariffPolicyTest.php | 6 | 28 | ✅ COMPLETE |
| Security | TariffPolicySecurityTest.php | 17 | ~50 | ✅ COMPLETE |
| Integration | TariffResourceTest.php | 13 | ~40 | ⚠️ RECOMMENDED |
| Feature | TariffControllerTest.php | 9 | ~27 | ⚠️ RECOMMENDED |
| Property | TariffAuthorizationPropertyTest.php | 4 | ~50 | ⚠️ RECOMMENDED |
| Performance | TariffPolicyPerformanceTest.php | 1 | ~1 | ℹ️ OPTIONAL |

### Total Estimated Coverage

- **Current**: 23 tests, ~78 assertions
- **With Recommendations**: 50 tests, ~196 assertions

### Priority

1. **HIGH**: Unit tests (COMPLETE ✅)
2. **HIGH**: Security tests (COMPLETE ✅)
3. **MEDIUM**: Integration tests (Filament resources)
4. **MEDIUM**: Feature tests (Controllers)
5. **LOW**: Property-based tests (Invariants)
6. **LOW**: Performance tests (Benchmarks)

### Implementation Order

1. ✅ Unit tests - COMPLETE
2. ✅ Security tests - COMPLETE
3. ⚠️ Integration tests - Create `TariffResourceTest.php`
4. ⚠️ Feature tests - Create `TariffControllerTest.php`
5. ⚠️ Property tests - Create `TariffAuthorizationPropertyTest.php`
6. ℹ️ Performance tests - Optional

---

## Running Tests

### All TariffPolicy Tests
```bash
php artisan test --filter=Tariff
```

### Unit Tests Only
```bash
php artisan test --filter=TariffPolicyTest
```

### Security Tests Only
```bash
php artisan test --filter=TariffPolicySecurityTest
```

### Integration Tests
```bash
php artisan test --filter=TariffResourceTest
```

### Feature Tests
```bash
php artisan test --filter=TariffControllerTest
```

### Property Tests
```bash
php artisan test --filter=TariffAuthorizationPropertyTest
```

### With Coverage
```bash
XDEBUG_MODE=coverage php artisan test --filter=Tariff --coverage
```

---

## Conclusion

The TariffPolicy has comprehensive unit and security test coverage. Integration and feature tests are recommended to ensure proper integration with Filament resources and controllers. Property-based tests would provide additional confidence in authorization invariants.

**Current Status**: ✅ Production Ready (Unit + Security tests complete)  
**Recommended Status**: ⭐ Excellent (All recommended tests implemented)

---

**Last Updated**: November 26, 2025  
**Maintained By**: Development Team  
**Version**: 1.0.0
