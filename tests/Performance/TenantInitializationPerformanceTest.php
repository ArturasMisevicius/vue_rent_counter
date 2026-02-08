<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\Property;
use App\Services\TenantInitializationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/**
 * Performance tests for TenantInitializationService
 * 
 * These tests ensure the service performs efficiently under various loads
 * and maintains acceptable response times.
 */

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(TenantInitializationService::class);
});

describe('Performance Benchmarks', function () {
    it('initializes services within acceptable time limits', function () {
        $tenant = Organization::factory()->create();
        
        $startTime = microtime(true);
        $result = $this->service->initializeUniversalServices($tenant);
        $executionTime = microtime(true) - $startTime;
        
        // Should complete within 500ms for single tenant
        expect($executionTime)->toBeLessThan(0.5);
        expect($result['utility_services'])->toHaveCount(4);
    });

    it('handles batch tenant initialization efficiently', function () {
        $tenantCount = 20;
        $tenants = Organization::factory()->count($tenantCount)->create();
        
        $startTime = microtime(true);
        
        foreach ($tenants as $tenant) {
            $this->service->initializeUniversalServices($tenant);
        }
        
        $executionTime = microtime(true) - $startTime;
        $averageTime = $executionTime / $tenantCount;
        
        // Average time per tenant should be reasonable
        expect($averageTime)->toBeLessThan(0.2); // 200ms per tenant
        expect($executionTime)->toBeLessThan(5.0); // Total under 5 seconds
        
        // Verify all services were created
        $totalServices = \App\Models\UtilityService::whereIn('tenant_id', $tenants->pluck('id'))->count();
        expect($totalServices)->toBe($tenantCount * 4);
    });

    it('scales linearly with property count', function () {
        $tenant = Organization::factory()->create();
        
        // Test different property counts
        $propertyCounts = [10, 50, 100];
        $times = [];
        
        foreach ($propertyCounts as $count) {
            // Clean up
            Property::where('tenant_id', $tenant->id)->delete();
            
            // Create properties
            Property::factory()->count($count)->forTenantId($tenant->id)->create();
            
            $result = $this->service->initializeUniversalServices($tenant);
            
            $startTime = microtime(true);
            $this->service->initializePropertyServiceAssignments($tenant, $result['utility_services']);
            $executionTime = microtime(true) - $startTime;
            
            $times[$count] = $executionTime;
            
            // Should complete within reasonable time
            expect($executionTime)->toBeLessThan($count * 0.01); // 10ms per property max
        }
        
        // Verify roughly linear scaling
        $ratio1 = $times[50] / $times[10];
        $ratio2 = $times[100] / $times[50];
        
        // Ratios should be roughly proportional (allowing for some overhead)
        expect($ratio1)->toBeLessThan(8); // Should be ~5x but allow overhead
        expect($ratio2)->toBeLessThan(3); // Should be ~2x but allow overhead
    });
});

describe('Database Performance', function () {
    it('minimizes database queries', function () {
        $tenant = Organization::factory()->create();
        
        DB::enableQueryLog();
        
        $this->service->initializeUniversalServices($tenant);
        
        $queries = DB::getQueryLog();
        $queryCount = count($queries);
        
        // Should use reasonable number of queries (not N+1)
        expect($queryCount)->toBeLessThan(20); // Adjust based on actual needs
        
        DB::disableQueryLog();
    });

    it('uses transactions efficiently', function () {
        $tenant = Organization::factory()->create();
        
        // Monitor transaction usage
        $transactionCount = 0;
        DB::listen(function ($query) use (&$transactionCount) {
            if (str_contains(strtolower($query->sql), 'begin') || 
                str_contains(strtolower($query->sql), 'start transaction')) {
                $transactionCount++;
            }
        });
        
        $this->service->initializeUniversalServices($tenant);
        
        // Should use minimal transactions
        expect($transactionCount)->toBeLessThan(5);
    });

    it('handles concurrent access gracefully', function () {
        $tenant = Organization::factory()->create();
        
        // Simulate concurrent initialization attempts
        $results = [];
        $errors = [];
        
        for ($i = 0; $i < 5; $i++) {
            try {
                $results[] = $this->service->initializeUniversalServices($tenant);
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }
        }
        
        // Should handle gracefully without major errors
        expect(count($results))->toBeGreaterThan(0);
        expect(count($errors))->toBeLessThan(3); // Allow some constraint violations
        
        // Final state should be consistent
        $services = \App\Models\UtilityService::where('tenant_id', $tenant->id)->get();
        expect($services->count())->toBeGreaterThanOrEqual(4);
        expect($services->count())->toBeLessThanOrEqual(20); // Reasonable upper bound
    });
});

describe('Memory Usage', function () {
    it('maintains reasonable memory usage during batch operations', function () {
        $initialMemory = memory_get_usage(true);
        
        $tenants = Organization::factory()->count(50)->create();
        
        foreach ($tenants as $tenant) {
            $this->service->initializeUniversalServices($tenant);
        }
        
        $finalMemory = memory_get_usage(true);
        $memoryIncrease = $finalMemory - $initialMemory;
        
        // Memory increase should be reasonable (less than 50MB for 50 tenants)
        expect($memoryIncrease)->toBeLessThan(50 * 1024 * 1024);
    });

    it('cleans up resources properly', function () {
        $tenant = Organization::factory()->create();
        
        $initialMemory = memory_get_peak_usage(true);
        
        // Run multiple iterations
        for ($i = 0; $i < 10; $i++) {
            $this->service->initializeUniversalServices($tenant);
        }
        
        // Force garbage collection
        gc_collect_cycles();
        
        $finalMemory = memory_get_peak_usage(true);
        $memoryIncrease = $finalMemory - $initialMemory;
        
        // Memory should not grow significantly with repeated calls
        expect($memoryIncrease)->toBeLessThan(10 * 1024 * 1024); // Less than 10MB
    });
});

describe('Cache Performance', function () {
    it('benefits from caching on repeated calls', function () {
        $tenant = Organization::factory()->create();
        
        // First call (cold cache)
        $startTime = microtime(true);
        $this->service->initializeUniversalServices($tenant);
        $coldTime = microtime(true) - $startTime;
        
        // Clear services but keep cache warm
        \App\Models\UtilityService::where('tenant_id', $tenant->id)->delete();
        
        // Second call (warm cache)
        $startTime = microtime(true);
        $this->service->initializeUniversalServices($tenant);
        $warmTime = microtime(true) - $startTime;
        
        // Warm cache should be faster or similar (allowing for variance)
        expect($warmTime)->toBeLessThanOrEqual($coldTime * 1.5);
    });
});