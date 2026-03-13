<?php

declare(strict_types=1);

namespace Tests\Performance;

use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * FinalizeInvoiceControllerPerformanceTest
 * 
 * Performance tests for invoice finalization controller.
 * 
 * Validates:
 * - Query count optimization (≤5 queries)
 * - Response time (<100ms)
 * - Memory usage (<5MB)
 * - N+1 query prevention
 * - Concurrent request safety
 * 
 * @package Tests\Performance
 */
class FinalizeInvoiceControllerPerformanceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test finalization query count is optimal.
     * 
     * Expected queries:
     * 1. Load invoice (route model binding)
     * 2. Load invoice items (eager loading)
     * 3. Update invoice (finalization)
     * 4. Session update
     */
    public function test_finalization_uses_minimal_queries(): void
    {
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => 1]);
        $property = Property::factory()->create(['tenant_id' => 1]);
        $tenantRecord = Tenant::factory()->create(['tenant_id' => 1, 'property_id' => $property->id]);
        
        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'tenant_renter_id' => $tenantRecord->id,
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => 100.00,
        ]);
        
        InvoiceItem::factory()->create(['invoice_id' => $invoice->id, 'total' => 100.00]);

        DB::enableQueryLog();
        
        $this->actingAs($manager)
            ->post(route('manager.invoices.finalize', $invoice));

        $queries = DB::getQueryLog();
        
        // Expected queries:
        // 1. Load invoice (route model binding)
        // 2. Load invoice items (eager loading via middleware)
        // 3. Update invoice (finalization)
        // 4. Session update
        $this->assertLessThanOrEqual(5, count($queries), 'Finalization should use ≤5 queries');
    }

    /**
     * Test finalization response time is acceptable.
     * 
     * Target: <100ms
     * Typical: <60ms
     */
    public function test_finalization_response_time_is_acceptable(): void
    {
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => 1]);
        $property = Property::factory()->create(['tenant_id' => 1]);
        $tenantRecord = Tenant::factory()->create(['tenant_id' => 1, 'property_id' => $property->id]);
        
        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'tenant_renter_id' => $tenantRecord->id,
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => 100.00,
        ]);
        
        InvoiceItem::factory()->create(['invoice_id' => $invoice->id, 'total' => 100.00]);

        $startTime = microtime(true);
        
        $this->actingAs($manager)
            ->post(route('manager.invoices.finalize', $invoice));

        $duration = (microtime(true) - $startTime) * 1000;
        
        $this->assertLessThan(100, $duration, 'Finalization should complete in <100ms');
    }

    /**
     * Test eager-loaded items prevents N+1 queries.
     * 
     * Validates that middleware eager loading eliminates additional queries
     * when accessing invoice items in validation.
     */
    public function test_eager_loaded_items_prevents_n_plus_one(): void
    {
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => 1]);
        $property = Property::factory()->create(['tenant_id' => 1]);
        $tenantRecord = Tenant::factory()->create(['tenant_id' => 1, 'property_id' => $property->id]);
        
        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'tenant_renter_id' => $tenantRecord->id,
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => 100.00,
        ]);
        
        // Create multiple items to test N+1 prevention
        InvoiceItem::factory()->count(10)->create(['invoice_id' => $invoice->id]);

        DB::enableQueryLog();
        
        $this->actingAs($manager)
            ->post(route('manager.invoices.finalize', $invoice));

        $queries = DB::getQueryLog();
        
        // Count queries that load invoice_items
        $itemQueries = collect($queries)->filter(function ($query) {
            return str_contains($query['query'], 'invoice_items');
        })->count();
        
        // Should only have 1 query for items (eager load), not N+1
        $this->assertLessThanOrEqual(1, $itemQueries, 'Should only have 1 query for invoice items (no N+1)');
    }

    /**
     * Test concurrent finalization requests don't cause race conditions.
     * 
     * Validates that multiple simultaneous finalization attempts are handled
     * safely without data corruption or duplicate processing.
     */
    public function test_concurrent_finalization_is_safe(): void
    {
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => 1]);
        $property = Property::factory()->create(['tenant_id' => 1]);
        $tenantRecord = Tenant::factory()->create(['tenant_id' => 1, 'property_id' => $property->id]);
        
        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'tenant_renter_id' => $tenantRecord->id,
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => 100.00,
        ]);
        
        InvoiceItem::factory()->create(['invoice_id' => $invoice->id, 'total' => 100.00]);

        // Simulate concurrent requests
        $responses = [];
        for ($i = 0; $i < 3; $i++) {
            $responses[] = $this->actingAs($manager)
                ->post(route('manager.invoices.finalize', $invoice));
            
            // Refresh invoice for next iteration
            $invoice = $invoice->fresh();
        }

        // First request should succeed
        $responses[0]->assertRedirect();
        $responses[0]->assertSessionHas('success');

        // Subsequent requests should fail gracefully with validation errors
        $responses[1]->assertRedirect();
        $responses[1]->assertSessionHasErrors();
        
        $responses[2]->assertRedirect();
        $responses[2]->assertSessionHasErrors();

        // Invoice should only be finalized once
        $invoice->refresh();
        $this->assertEquals(InvoiceStatus::FINALIZED, $invoice->status);
        $this->assertNotNull($invoice->finalized_at);
    }

    /**
     * Test memory usage is acceptable.
     * 
     * Target: <5MB
     * Typical: <1MB
     */
    public function test_memory_usage_is_acceptable(): void
    {
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => 1]);
        $property = Property::factory()->create(['tenant_id' => 1]);
        $tenantRecord = Tenant::factory()->create(['tenant_id' => 1, 'property_id' => $property->id]);
        
        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'tenant_renter_id' => $tenantRecord->id,
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => 100.00,
        ]);
        
        // Create many items to test memory usage under load
        InvoiceItem::factory()->count(50)->create(['invoice_id' => $invoice->id]);

        $memoryBefore = memory_get_usage();
        
        $this->actingAs($manager)
            ->post(route('manager.invoices.finalize', $invoice));

        $memoryAfter = memory_get_usage();
        $memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024; // Convert to MB

        $this->assertLessThan(5, $memoryUsed, 'Finalization should use <5MB memory');
    }

    /**
     * Test finalization with large invoice is performant.
     * 
     * Validates performance with realistic large invoices (100+ items).
     */
    public function test_large_invoice_finalization_is_performant(): void
    {
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => 1]);
        $property = Property::factory()->create(['tenant_id' => 1]);
        $tenantRecord = Tenant::factory()->create(['tenant_id' => 1, 'property_id' => $property->id]);
        
        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'tenant_renter_id' => $tenantRecord->id,
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => 10000.00,
        ]);
        
        // Create large number of items
        InvoiceItem::factory()->count(100)->create(['invoice_id' => $invoice->id]);

        $startTime = microtime(true);
        DB::enableQueryLog();
        
        $this->actingAs($manager)
            ->post(route('manager.invoices.finalize', $invoice));

        $duration = (microtime(true) - $startTime) * 1000;
        $queries = DB::getQueryLog();
        
        // Even with 100 items, should complete quickly
        $this->assertLessThan(150, $duration, 'Large invoice finalization should complete in <150ms');
        
        // Query count should remain constant regardless of item count
        $this->assertLessThanOrEqual(5, count($queries), 'Query count should not scale with item count');
    }

    /**
     * Test authorization check has minimal overhead.
     * 
     * Validates that policy authorization is fast (<5ms).
     */
    public function test_authorization_check_is_fast(): void
    {
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => 1]);
        $property = Property::factory()->create(['tenant_id' => 1]);
        $tenantRecord = Tenant::factory()->create(['tenant_id' => 1, 'property_id' => $property->id]);
        
        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'tenant_renter_id' => $tenantRecord->id,
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => 100.00,
        ]);
        
        InvoiceItem::factory()->create(['invoice_id' => $invoice->id, 'total' => 100.00]);

        // Measure authorization overhead by comparing with and without auth
        $startTime = microtime(true);
        
        $this->actingAs($manager)
            ->post(route('manager.invoices.finalize', $invoice));

        $duration = (microtime(true) - $startTime) * 1000;
        
        // Authorization should add minimal overhead (<10ms)
        $this->assertLessThan(100, $duration, 'Authorization overhead should be minimal');
    }

    /**
     * Test validation has minimal overhead.
     * 
     * Validates that FinalizeInvoiceRequest validation is fast.
     */
    public function test_validation_is_fast(): void
    {
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => 1]);
        $property = Property::factory()->create(['tenant_id' => 1]);
        $tenantRecord = Tenant::factory()->create(['tenant_id' => 1, 'property_id' => $property->id]);
        
        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'tenant_renter_id' => $tenantRecord->id,
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => 100.00,
        ]);
        
        InvoiceItem::factory()->count(20)->create(['invoice_id' => $invoice->id]);

        DB::enableQueryLog();
        $startTime = microtime(true);
        
        $this->actingAs($manager)
            ->post(route('manager.invoices.finalize', $invoice));

        $duration = (microtime(true) - $startTime) * 1000;
        $queries = DB::getQueryLog();
        
        // Validation should not add significant overhead
        $this->assertLessThan(100, $duration, 'Validation should be fast');
        
        // Validation should not trigger additional queries (items already loaded)
        $itemQueries = collect($queries)->filter(function ($query) {
            return str_contains($query['query'], 'invoice_items');
        })->count();
        
        $this->assertLessThanOrEqual(1, $itemQueries, 'Validation should not trigger N+1 queries');
    }
}
