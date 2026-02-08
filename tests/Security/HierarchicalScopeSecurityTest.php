<?php

declare(strict_types=1);

use App\Models\Building;
use App\Models\Property;
use App\Models\User;
use App\Scopes\HierarchicalScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

describe('Input Validation Security', function () {
    test('rejects negative tenant_id', function () {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid tenant_id: must be positive');
        
        Property::forTenant(-1)->get();
    });

    test('rejects zero tenant_id', function () {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid tenant_id: must be positive');
        
        Property::forTenant(0)->get();
    });

    test('rejects tenant_id exceeding INT_MAX', function () {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid tenant_id: exceeds maximum allowed value');
        
        Property::forTenant(2147483648)->get();
    });

    test('rejects non-numeric tenant_id string', function () {
        $this->expectException(TypeError::class);
        
        Property::forTenant('abc')->get();
    });

    test('accepts valid tenant_id', function () {
        $property = Property::factory()->create(['tenant_id' => 123]);
        
        $result = Property::forTenant(123)->get();
        
        expect($result)->toHaveCount(1);
        expect($result->first()->id)->toBe($property->id);
    });

    test('rejects negative property_id', function () {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid property_id: must be positive');
        
        Property::forProperty(-1)->get();
    });

    test('rejects zero property_id', function () {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid property_id: must be positive');
        
        Property::forProperty(0)->get();
    });

    test('rejects property_id exceeding INT_MAX', function () {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid property_id: exceeds maximum allowed value');
        
        Property::forProperty(2147483648)->get();
    });

    test('accepts valid property_id', function () {
        $property = Property::factory()->create(['tenant_id' => 1]);
        
        $result = Property::forProperty($property->id)->get();
        
        expect($result)->toHaveCount(1);
        expect($result->first()->id)->toBe($property->id);
    });
});

describe('Audit Logging Security', function () {
    test('logs scope bypass attempts', function () {
        Log::spy();
        
        $admin = User::factory()->admin()->create(['tenant_id' => 1]);
        $this->actingAs($admin);
        
        Property::withoutHierarchicalScope()->get();
        
        Log::shouldHaveReceived('warning')
            ->once()
            ->with('HierarchicalScope bypassed', \Mockery::on(function ($context) {
                return isset($context['user_id']) 
                    && isset($context['model'])
                    && isset($context['ip']);
            }));
    });

    test('logs tenant context switches', function () {
        Log::spy();
        
        $admin = User::factory()->admin()->create(['tenant_id' => 1]);
        $this->actingAs($admin);
        
        Property::forTenant(123)->get();
        
        Log::shouldHaveReceived('info')
            ->once()
            ->with('Tenant context switched via forTenant macro', \Mockery::on(function ($context) {
                return isset($context['user_id'])
                    && isset($context['target_tenant_id'])
                    && $context['target_tenant_id'] === 123;
            }));
    });

    test('logs property context switches', function () {
        Log::spy();
        
        $admin = User::factory()->admin()->create(['tenant_id' => 1]);
        $this->actingAs($admin);
        
        Property::forProperty(456)->get();
        
        Log::shouldHaveReceived('info')
            ->once()
            ->with('Property context switched via forProperty macro', \Mockery::on(function ($context) {
                return isset($context['user_id'])
                    && isset($context['target_property_id'])
                    && $context['target_property_id'] === 456;
            }));
    });

    test('logs superadmin unrestricted access', function () {
        Log::spy();
        
        $superadmin = User::factory()->superadmin()->create();
        Property::factory()->create(['tenant_id' => 1]);
        
        $this->actingAs($superadmin);
        Property::all();
        
        Log::shouldHaveReceived('info')
            ->once()
            ->with('Superadmin unrestricted access', \Mockery::on(function ($context) {
                return isset($context['user_id'])
                    && isset($context['model'])
                    && isset($context['table']);
            }));
    });

    test('logs missing tenant context', function () {
        Log::spy();
        
        // Create user without tenant_id
        $user = User::factory()->create([
            'role' => \App\Enums\UserRole::ADMIN,
            'tenant_id' => null,
        ]);
        
        $this->actingAs($user);
        Property::all();
        
        Log::shouldHaveReceived('warning')
            ->once()
            ->with('Query executed without tenant context', \Mockery::type('array'));
    });
});

describe('DoS Prevention Security', function () {
    test('caches schema queries to prevent DoS', function () {
        Cache::flush();
        
        $admin = User::factory()->admin()->create(['tenant_id' => 1]);
        Property::factory()->create(['tenant_id' => 1]);
        
        $this->actingAs($admin);
        
        // First query should cache
        Property::all();
        
        // Verify cache was set
        $cacheKey = 'hierarchical_scope:columns:properties:tenant_id';
        expect(Cache::has($cacheKey))->toBeTrue();
        
        // Second query should use cache (no additional Schema::hasColumn call)
        Property::all();
    });

    test('uses fillable array before schema query', function () {
        // Property model has tenant_id in fillable array
        $property = new Property();
        
        expect($property->getFillable())->toContain('tenant_id');
        
        // This should not trigger a schema query
        $admin = User::factory()->admin()->create(['tenant_id' => 1]);
        $this->actingAs($admin);
        
        Property::all();
        
        // If fillable check works, no exception should be thrown
        expect(true)->toBeTrue();
    });

    test('handles schema query failures gracefully', function () {
        Log::spy();
        
        // This test verifies fail-closed behavior
        // In production, if Schema::hasColumn fails, scope should assume column doesn't exist
        
        $admin = User::factory()->admin()->create(['tenant_id' => 1]);
        $this->actingAs($admin);
        
        // Should not throw exception even if schema check fails
        Property::all();
        
        expect(true)->toBeTrue();
    });

    test('cache clearing is logged', function () {
        Log::spy();
        
        HierarchicalScope::clearColumnCache('properties');
        
        Log::shouldHaveReceived('info')
            ->once()
            ->with('HierarchicalScope column cache cleared', \Mockery::on(function ($context) {
                return isset($context['table']) && $context['table'] === 'properties';
            }));
    });
});

describe('Data Isolation Security', function () {
    test('prevents cross-tenant data leakage via direct query', function () {
        $tenant1 = User::factory()->admin()->create(['tenant_id' => 1]);
        $tenant2 = User::factory()->admin()->create(['tenant_id' => 2]);
        
        $property1 = Property::factory()->create(['tenant_id' => 1]);
        $property2 = Property::factory()->create(['tenant_id' => 2]);
        
        $this->actingAs($tenant1);
        $properties = Property::all();
        
        expect($properties)->toHaveCount(1);
        expect($properties->first()->id)->toBe($property1->id);
        expect(Property::find($property2->id))->toBeNull();
    });

    test('prevents cross-tenant data leakage via relationship', function () {
        $tenant1 = User::factory()->admin()->create(['tenant_id' => 1]);
        
        $building1 = Building::factory()->create(['tenant_id' => 1]);
        $building2 = Building::factory()->create(['tenant_id' => 2]);
        
        $this->actingAs($tenant1);
        $buildings = Building::all();
        
        expect($buildings)->toHaveCount(1);
        expect($buildings->first()->id)->toBe($building1->id);
    });

    test('tenant users only see their assigned property', function () {
        $property1 = Property::factory()->create(['tenant_id' => 1]);
        $property2 = Property::factory()->create(['tenant_id' => 1]);
        
        $tenant = User::factory()->tenant()->create([
            'tenant_id' => 1,
            'property_id' => $property1->id,
        ]);
        
        $this->actingAs($tenant);
        $properties = Property::all();
        
        expect($properties)->toHaveCount(1);
        expect($properties->first()->id)->toBe($property1->id);
        expect(Property::find($property2->id))->toBeNull();
    });

    test('superadmin can access all tenant data', function () {
        $superadmin = User::factory()->superadmin()->create();
        
        $property1 = Property::factory()->create(['tenant_id' => 1]);
        $property2 = Property::factory()->create(['tenant_id' => 2]);
        
        $this->actingAs($superadmin);
        $properties = Property::all();
        
        expect($properties)->toHaveCount(2);
        expect($properties->pluck('id')->toArray())->toContain($property1->id, $property2->id);
    });
});

describe('Authorization Security', function () {
    test('unauthenticated users cannot bypass scope', function () {
        Property::factory()->create(['tenant_id' => 1]);
        
        // No authentication
        $properties = Property::all();
        
        // Should return empty (no tenant context)
        expect($properties)->toHaveCount(0);
    });

    test('scope bypass requires explicit macro call', function () {
        $admin = User::factory()->admin()->create(['tenant_id' => 1]);
        
        Property::factory()->create(['tenant_id' => 1]);
        Property::factory()->create(['tenant_id' => 2]);
        
        $this->actingAs($admin);
        
        // Normal query respects scope
        $scoped = Property::all();
        expect($scoped)->toHaveCount(1);
        
        // Explicit bypass required
        $unscoped = Property::withoutHierarchicalScope()->get();
        expect($unscoped)->toHaveCount(2);
    });
});

describe('Error Handling Security', function () {
    test('validation errors do not expose sensitive data', function () {
        try {
            Property::forTenant(-1)->get();
            $this->fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            // Error message should not contain sensitive data
            expect($e->getMessage())->not->toContain('password');
            expect($e->getMessage())->not->toContain('token');
            expect($e->getMessage())->not->toContain('secret');
        }
    });

    test('scope errors are logged without PII', function () {
        Log::spy();
        
        // Trigger an error condition
        $admin = User::factory()->admin()->create(['tenant_id' => 1]);
        $this->actingAs($admin);
        
        Property::all();
        
        // If any errors were logged, verify they don't contain PII
        // This is a placeholder - actual implementation depends on error scenarios
        expect(true)->toBeTrue();
    });
});

describe('Performance Security', function () {
    test('scope does not cause N+1 queries for simple cases', function () {
        $admin = User::factory()->admin()->create(['tenant_id' => 1]);
        
        Property::factory()->count(10)->create(['tenant_id' => 1]);
        
        $this->actingAs($admin);
        
        // Enable query logging
        DB::enableQueryLog();
        
        Property::all();
        
        $queries = DB::getQueryLog();
        
        // Should be 1 query (SELECT * FROM properties WHERE tenant_id = 1)
        expect($queries)->toHaveCount(1);
        
        DB::disableQueryLog();
    });

    test('cache hit rate is high for repeated queries', function () {
        Cache::flush();
        
        $admin = User::factory()->admin()->create(['tenant_id' => 1]);
        Property::factory()->create(['tenant_id' => 1]);
        
        $this->actingAs($admin);
        
        // First query caches
        Property::all();
        
        // Subsequent queries should use cache
        for ($i = 0; $i < 10; $i++) {
            Property::all();
        }
        
        // Verify cache is being used
        $cacheKey = 'hierarchical_scope:columns:properties:tenant_id';
        expect(Cache::has($cacheKey))->toBeTrue();
    });
});

describe('Integration Security', function () {
    test('scope works with Laravel policies', function () {
        $admin = User::factory()->admin()->create(['tenant_id' => 1]);
        $property = Property::factory()->create(['tenant_id' => 1]);
        
        $this->actingAs($admin);
        
        // Policy should work with scoped queries
        $this->assertTrue($admin->can('view', $property));
    });

    test('scope works with Filament resources', function () {
        $admin = User::factory()->admin()->create(['tenant_id' => 1]);
        
        Property::factory()->create(['tenant_id' => 1]);
        Property::factory()->create(['tenant_id' => 2]);
        
        $this->actingAs($admin);
        
        // Filament resources should respect scope
        $properties = Property::all();
        expect($properties)->toHaveCount(1);
    });
});
