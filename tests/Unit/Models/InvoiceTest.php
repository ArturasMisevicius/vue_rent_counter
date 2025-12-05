<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\InvoiceStatus;
use App\Exceptions\InvoiceAlreadyFinalizedException;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Comprehensive Unit Tests for Invoice Model
 *
 * Tests:
 * - Mass assignment and fillable attributes
 * - Attribute casting (InvoiceStatus enum, dates, decimals, datetimes)
 * - Relationships (tenant, property, items)
 * - Tenant isolation via BelongsToTenant trait
 * - Query scopes (draft, finalized, paid, forPeriod, forTenant)
 * - Business logic (finalize, status checks, immutability)
 * - Immutability protection for finalized/paid invoices
 */
final class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    private User $tenantUser;
    private User $otherTenantUser;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        // Create two separate tenant organizations
        $this->tenantUser = User::factory()->create(['tenant_id' => 1]);
        $this->otherTenantUser = User::factory()->create(['tenant_id' => 2]);

        // Create a tenant (renter) for testing
        $this->tenant = Tenant::factory()->create();
    }

    /** @test */
    public function it_has_correct_fillable_attributes(): void
    {
        $invoice = new Invoice();

        $expectedFillable = [
            'tenant_id',
            'tenant_renter_id',
            'invoice_number',
            'billing_period_start',
            'billing_period_end',
            'due_date',
            'total_amount',
            'status',
            'finalized_at',
            'paid_at',
            'payment_reference',
            'paid_amount',
            'overdue_notified_at',
        ];

        $this->assertEquals($expectedFillable, $invoice->getFillable());
    }

    /** @test */
    public function it_can_be_created_with_mass_assignment(): void
    {
        $data = [
            'tenant_id' => 1,
            'tenant_renter_id' => $this->tenant->id,
            'billing_period_start' => '2024-01-01',
            'billing_period_end' => '2024-01-31',
            'due_date' => '2024-02-15',
            'total_amount' => 1500.00,
            'status' => InvoiceStatus::DRAFT,
        ];

        $invoice = Invoice::create($data);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'tenant_id' => 1,
            'total_amount' => 1500.00,
        ]);
    }

    /** @test */
    public function it_casts_status_to_invoice_status_enum(): void
    {
        $invoice = Invoice::factory()->create([
            'status' => InvoiceStatus::DRAFT,
        ]);

        $this->assertInstanceOf(InvoiceStatus::class, $invoice->status);
        $this->assertEquals(InvoiceStatus::DRAFT, $invoice->status);
    }

    /** @test */
    public function it_casts_dates_correctly(): void
    {
        $invoice = Invoice::factory()->create([
            'billing_period_start' => '2024-01-01',
            'billing_period_end' => '2024-01-31',
            'due_date' => '2024-02-15',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $invoice->billing_period_start);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $invoice->billing_period_end);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $invoice->due_date);

        $this->assertEquals('2024-01-01', $invoice->billing_period_start->format('Y-m-d'));
        $this->assertEquals('2024-01-31', $invoice->billing_period_end->format('Y-m-d'));
        $this->assertEquals('2024-02-15', $invoice->due_date->format('Y-m-d'));
    }

    /** @test */
    public function it_casts_decimal_amounts_with_two_places(): void
    {
        $invoice = Invoice::factory()->create([
            'total_amount' => 1234.567,
            'paid_amount' => 1234.567,
        ]);

        $invoice->refresh();

        $this->assertIsString($invoice->total_amount);
        $this->assertIsString($invoice->paid_amount);
        $this->assertEquals('1234.57', $invoice->total_amount);
        $this->assertEquals('1234.57', $invoice->paid_amount);
    }

    /** @test */
    public function it_belongs_to_a_tenant_renter(): void
    {
        $invoice = Invoice::factory()->create([
            'tenant_renter_id' => $this->tenant->id,
        ]);

        $this->assertInstanceOf(Tenant::class, $invoice->tenant);
        $this->assertEquals($this->tenant->id, $invoice->tenant->id);
    }

    /** @test */
    public function it_has_many_invoice_items(): void
    {
        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
        ]);

        InvoiceItem::factory()->count(3)->create([
            'invoice_id' => $invoice->id,
        ]);

        $invoice->load('items');

        $this->assertCount(3, $invoice->items);
        $this->assertInstanceOf(InvoiceItem::class, $invoice->items->first());
    }

    /** @test */
    public function it_has_timestamps(): void
    {
        $invoice = Invoice::factory()->create();

        $this->assertNotNull($invoice->created_at);
        $this->assertNotNull($invoice->updated_at);
    }

    /** @test */
    public function scope_draft_returns_only_draft_invoices(): void
    {
        Invoice::factory()->count(2)->create([
            'status' => InvoiceStatus::DRAFT,
            'tenant_id' => 1,
        ]);

        Invoice::factory()->count(1)->create([
            'status' => InvoiceStatus::FINALIZED,
            'tenant_id' => 1,
        ]);

        $this->actingAs($this->tenantUser);

        $drafts = Invoice::draft()->get();

        $this->assertCount(2, $drafts);
        $drafts->each(fn($invoice) => $this->assertEquals(InvoiceStatus::DRAFT, $invoice->status));
    }

    /** @test */
    public function scope_finalized_returns_only_finalized_invoices(): void
    {
        Invoice::factory()->count(1)->create([
            'status' => InvoiceStatus::DRAFT,
            'tenant_id' => 1,
        ]);

        Invoice::factory()->count(3)->create([
            'status' => InvoiceStatus::FINALIZED,
            'tenant_id' => 1,
        ]);

        $this->actingAs($this->tenantUser);

        $finalized = Invoice::finalized()->get();

        $this->assertCount(3, $finalized);
        $finalized->each(fn($invoice) => $this->assertEquals(InvoiceStatus::FINALIZED, $invoice->status));
    }

    /** @test */
    public function scope_paid_returns_only_paid_invoices(): void
    {
        Invoice::factory()->count(2)->create([
            'status' => InvoiceStatus::DRAFT,
            'tenant_id' => 1,
        ]);

        Invoice::factory()->count(2)->create([
            'status' => InvoiceStatus::PAID,
            'tenant_id' => 1,
        ]);

        $this->actingAs($this->tenantUser);

        $paid = Invoice::paid()->get();

        $this->assertCount(2, $paid);
        $paid->each(fn($invoice) => $this->assertEquals(InvoiceStatus::PAID, $invoice->status));
    }

    /** @test */
    public function scope_for_period_filters_by_billing_period(): void
    {
        Invoice::factory()->create([
            'billing_period_start' => '2024-01-01',
            'billing_period_end' => '2024-01-31',
            'tenant_id' => 1,
        ]);

        Invoice::factory()->create([
            'billing_period_start' => '2024-02-01',
            'billing_period_end' => '2024-02-29',
            'tenant_id' => 1,
        ]);

        Invoice::factory()->create([
            'billing_period_start' => '2024-03-01',
            'billing_period_end' => '2024-03-31',
            'tenant_id' => 1,
        ]);

        $this->actingAs($this->tenantUser);

        $januaryInvoices = Invoice::forPeriod('2024-01-01', '2024-01-31')->get();
        $februaryInvoices = Invoice::forPeriod('2024-02-01', '2024-02-29')->get();

        $this->assertCount(1, $januaryInvoices);
        $this->assertCount(1, $februaryInvoices);
    }

    /** @test */
    public function finalize_method_sets_status_and_timestamp(): void
    {
        $invoice = Invoice::factory()->create([
            'status' => InvoiceStatus::DRAFT,
            'tenant_id' => 1,
        ]);

        $this->assertNull($invoice->finalized_at);
        $this->assertTrue($invoice->isDraft());

        $invoice->finalize();

        $this->assertEquals(InvoiceStatus::FINALIZED, $invoice->status);
        $this->assertNotNull($invoice->finalized_at);
        $this->assertTrue($invoice->isFinalized());
    }

    /** @test */
    public function is_draft_method_returns_correct_boolean(): void
    {
        $draft = Invoice::factory()->create(['status' => InvoiceStatus::DRAFT]);
        $finalized = Invoice::factory()->create(['status' => InvoiceStatus::FINALIZED]);

        $this->assertTrue($draft->isDraft());
        $this->assertFalse($finalized->isDraft());
    }

    /** @test */
    public function is_finalized_method_returns_correct_boolean(): void
    {
        $draft = Invoice::factory()->create(['status' => InvoiceStatus::DRAFT]);
        $finalized = Invoice::factory()->create(['status' => InvoiceStatus::FINALIZED]);

        $this->assertFalse($draft->isFinalized());
        $this->assertTrue($finalized->isFinalized());
    }

    /** @test */
    public function is_paid_method_returns_correct_boolean(): void
    {
        $draft = Invoice::factory()->create(['status' => InvoiceStatus::DRAFT]);
        $paid = Invoice::factory()->create(['status' => InvoiceStatus::PAID]);

        $this->assertFalse($draft->isPaid());
        $this->assertTrue($paid->isPaid());
    }

    /** @test */
    public function is_overdue_returns_true_for_past_due_unpaid_invoices(): void
    {
        $overdue = Invoice::factory()->create([
            'status' => InvoiceStatus::FINALIZED,
            'due_date' => now()->subDays(10),
        ]);

        $this->assertTrue($overdue->isOverdue());
    }

    /** @test */
    public function is_overdue_returns_false_for_paid_invoices(): void
    {
        $paid = Invoice::factory()->create([
            'status' => InvoiceStatus::PAID,
            'due_date' => now()->subDays(10),
        ]);

        $this->assertFalse($paid->isOverdue());
    }

    /** @test */
    public function is_overdue_returns_false_for_future_due_date(): void
    {
        $notOverdue = Invoice::factory()->create([
            'status' => InvoiceStatus::FINALIZED,
            'due_date' => now()->addDays(10),
        ]);

        $this->assertFalse($notOverdue->isOverdue());
    }

    /** @test */
    public function finalized_invoice_cannot_be_modified_except_status(): void
    {
        $invoice = Invoice::factory()->create([
            'status' => InvoiceStatus::FINALIZED,
            'total_amount' => 1000.00,
            'tenant_id' => 1,
        ]);

        $this->expectException(InvoiceAlreadyFinalizedException::class);

        $invoice->total_amount = 2000.00;
        $invoice->save();
    }

    /** @test */
    public function finalized_invoice_status_can_be_changed_to_paid(): void
    {
        $invoice = Invoice::factory()->create([
            'status' => InvoiceStatus::FINALIZED,
            'total_amount' => 1000.00,
            'tenant_id' => 1,
        ]);

        // Changing only status should work
        $invoice->status = InvoiceStatus::PAID;
        $invoice->save();

        $invoice->refresh();
        $this->assertEquals(InvoiceStatus::PAID, $invoice->status);
    }

    /** @test */
    public function tenant_renter_id_is_auto_set_from_tenant_id_on_creation(): void
    {
        $invoice = Invoice::create([
            'tenant_id' => 1,
            'billing_period_start' => '2024-01-01',
            'billing_period_end' => '2024-01-31',
            'due_date' => '2024-02-15',
            'total_amount' => 1000.00,
            'status' => InvoiceStatus::DRAFT,
        ]);

        // tenant_renter_id should be auto-set to tenant_id
        $this->assertEquals(1, $invoice->tenant_renter_id);
    }
}
