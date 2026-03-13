<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FinalizeInvoiceControllerTest
 * 
 * Tests for the dedicated invoice finalization controller.
 * 
 * Requirements:
 * - 5.5: Invoice finalization makes invoice immutable
 * - 11.1: Verify user's role using Laravel Policies
 * - 11.3: Manager can finalize invoices
 * 
 * @package Tests\Feature\Http\Controllers
 */
class FinalizeInvoiceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $manager;
    protected User $admin;
    protected User $tenant;
    protected Property $property;
    protected Tenant $tenantRecord;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users
        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);

        $this->manager = User::factory()->create([
            'role' => UserRole::MANAGER,
            'tenant_id' => 1,
        ]);

        // Create property
        $this->property = Property::factory()->create([
            'tenant_id' => 1,
        ]);

        // Create tenant record
        $this->tenantRecord = Tenant::factory()->create([
            'tenant_id' => 1,
            'property_id' => $this->property->id,
        ]);

        // Create tenant user
        $this->tenant = User::factory()->create([
            'role' => UserRole::TENANT,
            'tenant_id' => 1,
            'property_id' => $this->property->id,
        ]);
    }

    /**
     * Test manager can finalize draft invoice.
     * 
     * Requirement 5.5, 11.3: Manager can finalize invoices
     */
    public function test_manager_can_finalize_draft_invoice(): void
    {
        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'tenant_renter_id' => $this->tenantRecord->id,
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => 100.00,
        ]);

        // Add invoice items (required for finalization)
        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'total' => 100.00,
        ]);

        $response = $this->actingAs($this->manager)
            ->post(route('manager.invoices.finalize', $invoice));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $invoice->refresh();
        $this->assertEquals(InvoiceStatus::FINALIZED, $invoice->status);
        $this->assertNotNull($invoice->finalized_at);
    }

    /**
     * Test admin can finalize draft invoice via direct controller invocation.
     * 
     * Requirement 11.1: Verify user's role using Laravel Policies
     */
    public function test_admin_can_finalize_draft_invoice(): void
    {
        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'tenant_renter_id' => $this->tenantRecord->id,
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => 100.00,
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'total' => 100.00,
        ]);

        // Admin can finalize via policy check
        $this->assertTrue($this->admin->can('finalize', $invoice));
        
        // Directly test the service
        $billingService = app(\App\Services\BillingService::class);
        $billingService->finalizeInvoice($invoice);

        $invoice->refresh();
        $this->assertEquals(InvoiceStatus::FINALIZED, $invoice->status);
    }

    /**
     * Test tenant cannot finalize invoice.
     * 
     * Requirement 11.1: Verify user's role using Laravel Policies
     */
    public function test_tenant_cannot_finalize_invoice(): void
    {
        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'tenant_renter_id' => $this->tenantRecord->id,
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => 100.00,
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'total' => 100.00,
        ]);

        $response = $this->actingAs($this->tenant)
            ->post(route('manager.invoices.finalize', $invoice));

        $response->assertForbidden();
    }

    /**
     * Test cannot finalize already finalized invoice.
     * 
     * Requirement 5.5: Invoice finalization makes invoice immutable
     */
    public function test_cannot_finalize_already_finalized_invoice(): void
    {
        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'tenant_renter_id' => $this->tenantRecord->id,
            'status' => InvoiceStatus::FINALIZED,
            'finalized_at' => Carbon::now(),
            'total_amount' => 100.00,
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'total' => 100.00,
        ]);

        $response = $this->actingAs($this->manager)
            ->post(route('manager.invoices.finalize', $invoice));

        $response->assertRedirect();
        // The validation error will be in the session errors, not as a flash message
        $response->assertSessionHasErrors();
    }

    /**
     * Test cannot finalize invoice without items.
     * 
     * Requirement 5.5: Invoice must have valid data before finalization
     */
    public function test_cannot_finalize_invoice_without_items(): void
    {
        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'tenant_renter_id' => $this->tenantRecord->id,
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => 0.00,
        ]);

        $response = $this->actingAs($this->manager)
            ->post(route('manager.invoices.finalize', $invoice));

        $response->assertRedirect();
        $response->assertSessionHasErrors();
    }

    /**
     * Test finalized invoice has immutable timestamp.
     * 
     * Requirement 5.5: Invoice finalization makes invoice immutable
     */
    public function test_finalized_invoice_has_timestamp(): void
    {
        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'tenant_renter_id' => $this->tenantRecord->id,
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => 100.00,
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'total' => 100.00,
        ]);

        $beforeFinalization = Carbon::now();

        $this->actingAs($this->manager)
            ->post(route('manager.invoices.finalize', $invoice));

        $invoice->refresh();
        $this->assertNotNull($invoice->finalized_at);
        $this->assertTrue($invoice->finalized_at->greaterThanOrEqualTo($beforeFinalization));
    }

    /**
     * Test finalization validates billing period.
     * 
     * Requirement 5.5: Invoice must have valid data before finalization
     */
    public function test_finalization_validates_billing_period(): void
    {
        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'tenant_renter_id' => $this->tenantRecord->id,
            'status' => InvoiceStatus::DRAFT,
            'billing_period_start' => Carbon::now(),
            'billing_period_end' => Carbon::now()->subDay(), // Invalid: end before start
            'total_amount' => 100.00,
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'total' => 100.00,
        ]);

        $response = $this->actingAs($this->manager)
            ->post(route('manager.invoices.finalize', $invoice));

        $response->assertRedirect();
        $response->assertSessionHasErrors();
    }
}
