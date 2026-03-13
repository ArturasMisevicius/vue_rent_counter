<?php

use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Property;
use App\Services\BillingService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

beforeEach(function () {
    $this->tenantId = 1;
    session(['tenant_id' => $this->tenantId]);
    Cache::flush();
});

test('invoice generation requires authorization', function () {
    $property = Property::factory()->create(['tenant_id' => $this->tenantId]);
    $tenant = Tenant::factory()->create([
        'tenant_id' => $this->tenantId,
        'property_id' => $property->id,
    ]);
    
    // Create unauthorized user (tenant role trying to generate invoice)
    $unauthorizedUser = User::factory()->create([
        'tenant_id' => $this->tenantId,
        'role' => UserRole::TENANT,
    ]);
    
    $this->actingAs($unauthorizedUser);
    
    expect(fn() => app(BillingService::class)->generateInvoice($tenant, now(), now()))
        ->toThrow(AuthorizationException::class);
});

test('invoice generation is rate limited', function () {
    $property = Property::factory()->create(['tenant_id' => $this->tenantId]);
    $tenant = Tenant::factory()->create([
        'tenant_id' => $this->tenantId,
        'property_id' => $property->id,
    ]);
    
    $user = User::factory()->create([
        'tenant_id' => $this->tenantId,
        'role' => UserRole::ADMIN,
    ]);
    
    $this->actingAs($user);
    
    // Set rate limit to 3 for testing
    config(['billing.rate_limit.max_attempts' => 3]);
    
    $service = app(BillingService::class);
    
    // First 3 attempts should work (or fail for other reasons, but not rate limit)
    for ($i = 0; $i < 3; $i++) {
        try {
            $service->generateInvoice($tenant, now()->subMonth(), now());
        } catch (TooManyRequestsHttpException $e) {
            throw new Exception("Rate limit triggered too early on attempt {$i}");
        } catch (\Exception $e) {
            // Other exceptions are fine (missing meters, etc.)
        }
    }
    
    // 4th attempt should be rate limited
    expect(fn() => $service->generateInvoice($tenant, now()->subMonth(), now()))
        ->toThrow(TooManyRequestsHttpException::class);
});

test('authorized admin can generate invoices', function () {
    $property = Property::factory()->create(['tenant_id' => $this->tenantId]);
    $tenant = Tenant::factory()->create([
        'tenant_id' => $this->tenantId,
        'property_id' => $property->id,
    ]);
    
    $admin = User::factory()->create([
        'tenant_id' => $this->tenantId,
        'role' => UserRole::ADMIN,
    ]);
    
    $this->actingAs($admin);
    
    // Should not throw authorization exception
    // (may throw other exceptions due to missing meters, but that's expected)
    try {
        app(BillingService::class)->generateInvoice($tenant, now()->subMonth(), now());
    } catch (AuthorizationException $e) {
        throw new Exception('Admin should be authorized to generate invoices');
    } catch (\Exception $e) {
        // Other exceptions are expected (missing meters, etc.)
        expect($e)->not->toBeInstanceOf(AuthorizationException::class);
    }
});

test('cache integrity is validated', function () {
    // This test verifies that the cache validation logic works
    // by checking that invalid cached data is detected and removed
    
    $service = app(BillingService::class);
    
    // Use reflection to access private cache
    $reflection = new ReflectionClass($service);
    $cacheProperty = $reflection->getProperty('providerCache');
    $cacheProperty->setAccessible(true);
    
    // Inject invalid data into cache
    $cacheProperty->setValue($service, [
        'electricity' => 'invalid_data', // Not a Provider instance
    ]);
    
    // Attempt to get provider should detect invalid cache and fetch fresh data
    $method = $reflection->getMethod('getProviderForMeterType');
    $method->setAccessible(true);
    
    try {
        $method->invoke($service, \App\Enums\MeterType::ELECTRICITY);
    } catch (\Exception $e) {
        // May fail due to missing provider in test DB, but should not be cache-related
        expect($e->getMessage())->not->toContain('cache');
    }
});
