<?php

declare(strict_types=1);

namespace Tests\Unit\Scopes;

use App\Enums\UserRole;
use App\Models\Building;
use App\Models\Property;
use App\Models\User;
use App\Scopes\HierarchicalScope;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Clear any cached column checks
    Cache::flush();
});

describe('Guest User Handling', function () {
    test('allows unauthenticated users to query models', function () {
        // Ensure no user is authenticated
        Auth::logout();
        
        // This should not throw an exception
        $properties = Property::all();
        
        expect(Auth::check())->toBeFalse();
    });
    
    test('does not apply filtering for guest users', function () {
        Auth::logout();
        
        // Create properties with different tenant_ids
        Property::factory()->create(['tenant_id' => 1]);
        Property::factory()->create(['tenant_id' => 2]);
        
        // Guest should see all properties (no filtering applied)
        $count = Property::count();
        
        expect($count)->toBe(2);
    });
});

describe('Superadmin Access', function () {
    test('superadmin sees all data without filtering', function () {
        $superadmin = User::factory()->create([
            'role' => UserRole::SUPERADMIN,
            'tenant_id' => 1,
        ]);
        
        // Create properties for different tenants
        Property::factory()->create(['tenant_id' => 1]);
        Property::factory()->create(['tenant_id' => 2]);
        Property::factory()->create(['tenant_id' => 3]);
        
        $this->actingAs($superadmin);
        
        $properties = Property::all();
        
        expect($properties)->toHaveCount(3);
    });
    
    test('logs superadmin access for audit trail', function () {
        Log::shouldReceive('info')
            ->once()
            ->with('Superadmin unrestricted access', \Mockery::type('array'));
        
        $superadmin = User::factory()->create([
            'role' => UserRole::SUPERADMIN,
            'tenant_id' => 1,
        ]);
        
        $this->actingAs($superadmin);
        
        Property::all();
    });
});

describe('Admin/Manager Filtering', function () {
    test('admin sees only their tenant data', function () {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);
        
        Property::factory()->create(['tenant_id' => 1]);
        Property::factory()->create(['tenant_id' => 1]);
        Property::factory()->create(['tenant_id' => 2]);
        
        $this->actingAs($admin);
        
        $properties = Property::all();
        
        expect($properties)->toHaveCount(2)
            ->and($properties->every(fn($p) => $p->tenant_id === 1))->toBeTrue();
    });
    
    test('manager sees only their tenant data', function () {
        $manager = User::factory()->create([
            'role' => UserRole::MANAGER,
            'tenant_id' => 2,
        ]);
        
        Property::factory()->create(['tenant_id' => 1]);
        Property::factory()->create(['tenant_id' => 2]);
        Property::factory()->create(['tenant_id' => 2]);
        
        $this->actingAs($manager);
        
        $properties = Property::all();
        
        expect($properties)->toHaveCount(2)
            ->and($properties->every(fn($p) => $p->tenant_id === 2))->toBeTrue();
    });
});

describe('Tenant User Filtering', function () {
    test('tenant user sees only their property data', function () {
        $property1 = Property::factory()->create(['tenant_id' => 1]);
        $property2 = Property::factory()->create(['tenant_id' => 1]);
        
        $tenant = User::factory()->create([
            'role' => UserRole::TENANT,
            'tenant_id' => 1,
            'property_id' => $property1->id,
        ]);
        
        $this->actingAs($tenant);
        
        $properties = Property::all();
        
        expect($properties)->toHaveCount(1)
            ->and($properties->first()->id)->toBe($property1->id);
    });
    
    test('tenant user without property_id sees tenant data', function () {
        Property::factory()->create(['tenant_id' => 1]);
        Property::factory()->create(['tenant_id' => 1]);
        Property::factory()->create(['tenant_id' => 2]);
        
        $tenant = User::factory()->create([
            'role' => UserRole::TENANT,
            'tenant_id' => 1,
            'property_id' => null,
        ]);
        
        $this->actingAs($tenant);
        
        $properties = Property::all();
        
        expect($properties)->toHaveCount(2);
    });
});

describe('TenantContext Integration', function () {
    test('uses TenantContext when available', function () {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);
        
        Property::factory()->create(['tenant_id' => 1]);
        Property::factory()->create(['tenant_id' => 2]);
        
        $this->actingAs($admin);
        
        // Switch context to tenant 2
        TenantContext::set(2);
        
        $properties = Property::all();
        
        // Should see tenant 2 data due to TenantContext override
        expect($properties)->toHaveCount(1)
            ->and($properties->first()->tenant_id)->toBe(2);
        
        TenantContext::clear();
    });
});

describe('Input Validation', function () {
    test('validates tenant_id is positive integer', function () {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => -1, // Invalid
        ]);
        
        $this->actingAs($admin);
        
        expect(fn() => Property::all())
            ->toThrow(InvalidArgumentException::class, 'Invalid tenant_id: must be positive');
    });
    
    test('validates tenant_id does not exceed maximum', function () {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 2147483648, // Exceeds INT max
        ]);
        
        $this->actingAs($admin);
        
        expect(fn() => Property::all())
            ->toThrow(InvalidArgumentException::class, 'Invalid tenant_id: exceeds maximum allowed value');
    });
    
    test('validates property_id is positive integer', function () {
        $tenant = User::factory()->create([
            'role' => UserRole::TENANT,
            'tenant_id' => 1,
            'property_id' => -1, // Invalid
        ]);
        
        $this->actingAs($tenant);
        
        expect(fn() => Property::all())
            ->toThrow(InvalidArgumentException::class, 'Invalid property_id: must be positive');
    });
});

describe('Query Macros', function () {
    test('withoutHierarchicalScope bypasses filtering', function () {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);
        
        Property::factory()->create(['tenant_id' => 1]);
        Property::factory()->create(['tenant_id' => 2]);
        
        $this->actingAs($admin);
        
        $properties = Property::withoutHierarchicalScope()->get();
        
        expect($properties)->toHaveCount(2);
    });
    
    test('withoutHierarchicalScope logs bypass attempt', function () {
        Log::shouldReceive('warning')
            ->once()
            ->with('HierarchicalScope bypassed', \Mockery::type('array'));
        
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);
        
        $this->actingAs($admin);
        
        Property::withoutHierarchicalScope()->get();
    });
    
    test('forTenant queries specific tenant data', function () {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);
        
        Property::factory()->create(['tenant_id' => 1]);
        Property::factory()->create(['tenant_id' => 2]);
        
        $this->actingAs($admin);
        
        $properties = Property::forTenant(2)->get();
        
        expect($properties)->toHaveCount(1)
            ->and($properties->first()->tenant_id)->toBe(2);
    });
    
    test('forTenant validates tenant_id', function () {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);
        
        $this->actingAs($admin);
        
        expect(fn() => Property::forTenant(-1)->get())
            ->toThrow(InvalidArgumentException::class, 'Invalid tenant_id');
    });
    
    test('forProperty queries specific property data', function () {
        $property1 = Property::factory()->create(['tenant_id' => 1]);
        $property2 = Property::factory()->create(['tenant_id' => 1]);
        
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);
        
        $this->actingAs($admin);
        
        $properties = Property::forProperty($property1->id)->get();
        
        expect($properties)->toHaveCount(1)
            ->and($properties->first()->id)->toBe($property1->id);
    });
});

describe('Column Caching', function () {
    test('caches column existence checks', function () {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);
        
        $this->actingAs($admin);
        
        // First query should cache the column check
        Property::all();
        
        // Verify cache was set
        $cacheKey = 'hierarchical_scope:columns:properties:tenant_id';
        expect(Cache::has($cacheKey))->toBeTrue();
    });
    
    test('clearColumnCache removes specific table cache', function () {
        $cacheKey = 'hierarchical_scope:columns:properties:tenant_id';
        Cache::put($cacheKey, true, 3600);
        
        HierarchicalScope::clearColumnCache('properties');
        
        expect(Cache::has($cacheKey))->toBeFalse();
    });
});

describe('Error Handling', function () {
    test('logs errors without exposing sensitive details', function () {
        Log::shouldReceive('error')
            ->once()
            ->with('HierarchicalScope error', \Mockery::type('array'));
        
        // Create a scenario that triggers an error
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 'invalid', // This will cause validation error
        ]);
        
        $this->actingAs($admin);
        
        try {
            Property::all();
        } catch (\Exception $e) {
            // Expected to throw
        }
    });
    
    test('logs missing tenant context', function () {
        Log::shouldReceive('warning')
            ->once()
            ->with('Query executed without tenant context', \Mockery::type('array'));
        
        $user = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => null, // No tenant context
        ]);
        
        $this->actingAs($user);
        
        Property::all();
    });
});

describe('Special Table Handling', function () {
    test('handles buildings table with properties relationship', function () {
        $property = Property::factory()->create(['tenant_id' => 1]);
        $building = Building::factory()->create(['tenant_id' => 1]);
        
        // Attach building to property
        $building->properties()->attach($property->id);
        
        $tenant = User::factory()->create([
            'role' => UserRole::TENANT,
            'tenant_id' => 1,
            'property_id' => $property->id,
        ]);
        
        $this->actingAs($tenant);
        
        $buildings = Building::all();
        
        expect($buildings)->toHaveCount(1)
            ->and($buildings->first()->id)->toBe($building->id);
    });
});
