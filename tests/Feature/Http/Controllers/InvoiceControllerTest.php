<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Enums\DistributionMethod;
use App\Enums\InvoiceStatus;
use App\Enums\PricingModel;
use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\ServiceConfiguration;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UtilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * InvoiceControllerTest
 *
 * Tests for invoice management controllers.
 *
 * Requirements:
 * - 5.1: Snapshot current tariff rates in invoice items
 * - 5.2: Snapshot meter readings used in calculations
 * - 5.5: Invoice finalization makes invoice immutable
 * - 6.1: Filter invoices by tenant_id (automatic via Global Scope)
 * - 6.5: Support property filtering for multi-property tenants
 */
class InvoiceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $manager;

    protected User $tenant;

    protected Property $property;

    protected Tenant $tenantRecord;

    protected function setUp(): void
    {
        parent::setUp();

        // Create manager user
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
            'email' => $this->tenantRecord->email,
        ]);
    }

    /**
     * Test manager can view invoice index.
     *
     * Requirement 6.1: Filter invoices by tenant_id (automatic via Global Scope)
     */
    public function test_manager_can_view_invoice_index(): void
    {
        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'tenant_renter_id' => $this->tenantRecord->id,
        ]);

        $response = $this->actingAs($this->manager)
            ->get(route('manager.invoices.index'));

        $response->assertOk();
        $response->assertViewIs('pages.invoices.index');
        $response->assertViewHas('invoices');
    }

    /**
     * Test manager can filter invoices by property.
     *
     * Requirement 6.5: Support property filtering for multi-property tenants
     */
    public function test_manager_can_filter_invoices_by_property(): void
    {
        // Create second property
        $property2 = Property::factory()->create(['tenant_id' => 1]);
        $tenant2 = Tenant::factory()->create([
            'tenant_id' => 1,
            'property_id' => $property2->id,
        ]);

        // Create invoices for both properties
        $invoice1 = Invoice::factory()->create([
            'tenant_id' => 1,
            'tenant_renter_id' => $this->tenantRecord->id,
        ]);

        $invoice2 = Invoice::factory()->create([
            'tenant_id' => 1,
            'tenant_renter_id' => $tenant2->id,
        ]);

        // Filter by first property
        $response = $this->actingAs($this->manager)
            ->get(route('manager.invoices.index', ['property_id' => $this->property->id]));

        $response->assertOk();
        $response->assertViewHas('invoices', function ($invoices) use ($invoice1) {
            return $invoices->contains($invoice1);
        });
    }

    /**
     * Test manager can create invoice.
     *
     * Requirement 5.1, 5.2: Snapshot tariff rates and meter readings
     */
    public function test_manager_can_create_invoice(): void
    {
        $response = $this->actingAs($this->manager)
            ->get(route('manager.invoices.create'));

        $response->assertOk();
        $response->assertViewIs('pages.invoices.create');
        $response->assertViewHas('tenants');
    }

    /**
     * Test manager can store invoice.
     *
     * Requirement 5.1, 5.2: Snapshot tariff rates and meter readings
     */
    public function test_manager_can_store_invoice(): void
    {
        $utilityService = UtilityService::factory()->create([
            'tenant_id' => 1,
            'name' => 'Electricity',
            'unit_of_measurement' => 'kWh',
            'default_pricing_model' => PricingModel::CONSUMPTION_BASED,
        ]);

        $serviceConfiguration = ServiceConfiguration::factory()->create([
            'tenant_id' => 1,
            'property_id' => $this->property->id,
            'utility_service_id' => $utilityService->id,
            'pricing_model' => PricingModel::CONSUMPTION_BASED,
            'distribution_method' => DistributionMethod::EQUAL,
            'rate_schedule' => ['rate_per_unit' => 2.0],
            'effective_from' => now()->subYears(5),
            'effective_until' => null,
            'is_active' => true,
        ]);

        // Create meter with readings
        $meter = Meter::factory()->create([
            'tenant_id' => 1,
            'property_id' => $this->property->id,
            'service_configuration_id' => $serviceConfiguration->id,
            'supports_zones' => false,
        ]);

        $periodStart = Carbon::now()->subMonth()->startOfDay();
        $periodEnd = Carbon::now()->endOfDay();

        MeterReading::factory()->create([
            'tenant_id' => 1,
            'meter_id' => $meter->id,
            'reading_date' => $periodStart->copy()->subDay(), // Before period
            'value' => 100,
        ]);

        MeterReading::factory()->create([
            'tenant_id' => 1,
            'meter_id' => $meter->id,
            'reading_date' => $periodEnd->copy()->addDay(), // After period
            'value' => 150,
        ]);

        $response = $this->actingAs($this->manager)
            ->post(route('manager.invoices.store'), [
                'tenant_renter_id' => $this->tenantRecord->id,
                'billing_period_start' => $periodStart->toDateString(),
                'billing_period_end' => $periodEnd->toDateString(),
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('invoices', [
            'tenant_renter_id' => $this->tenantRecord->id,
            'status' => InvoiceStatus::DRAFT->value,
        ]);
    }

    /**
     * Test manager can view invoice.
     */
    public function test_manager_can_view_invoice(): void
    {
        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'tenant_renter_id' => $this->tenantRecord->id,
        ]);

        $response = $this->actingAs($this->manager)
            ->get(route('manager.invoices.show', $invoice));

        $response->assertOk();
        $response->assertViewIs('pages.invoices.show');
        $response->assertViewHas('invoice');
    }

    /**
     * Test manager can finalize invoice.
     *
     * Requirement 5.5: Invoice finalization makes invoice immutable
     */
    public function test_manager_can_finalize_invoice(): void
    {
        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'tenant_renter_id' => $this->tenantRecord->id,
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => 100.00,
        ]);

        // Add invoice items
        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'total' => 100.00,
        ]);

        $response = $this->actingAs($this->manager)
            ->post(route('manager.invoices.finalize', $invoice));

        $response->assertRedirect();
        $invoice->refresh();
        $this->assertEquals(InvoiceStatus::FINALIZED, $invoice->status);
        $this->assertNotNull($invoice->finalized_at);
    }

    /**
     * Test finalized invoice cannot be modified.
     *
     * Requirement 5.5: Invoice finalization makes invoice immutable
     */
    public function test_finalized_invoice_cannot_be_modified(): void
    {
        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'tenant_renter_id' => $this->tenantRecord->id,
            'status' => InvoiceStatus::FINALIZED,
            'finalized_at' => Carbon::now(),
        ]);

        $this->expectException(\App\Exceptions\InvoiceAlreadyFinalizedException::class);

        $invoice->update(['total_amount' => 200.00]);
    }

    /**
     * Test tenant can view their invoices.
     *
     * Requirement 6.1: Filter invoices by tenant_id
     */
    public function test_tenant_can_view_their_invoices(): void
    {
        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'tenant_renter_id' => $this->tenantRecord->id,
        ]);

        $response = $this->actingAs($this->tenant)
            ->get(route('tenant.invoices.index'));

        $response->assertOk();
        $response->assertViewIs('pages.invoices.index');
    }

    /**
     * Test tenant cannot view other tenant's invoices.
     *
     * Requirement 6.1: Filter invoices by tenant_id
     */
    public function test_tenant_cannot_view_other_tenant_invoices(): void
    {
        // Create another tenant
        $otherTenant = Tenant::factory()->create([
            'tenant_id' => 2,
        ]);

        $invoice = Invoice::factory()->create([
            'tenant_id' => 2,
            'tenant_renter_id' => $otherTenant->id,
        ]);

        $response = $this->actingAs($this->tenant)
            ->get(route('tenant.invoices.show', $invoice));

        $response->assertNotFound();
    }

    /**
     * Test tenant can download invoice PDF.
     */
    public function test_tenant_can_download_invoice_pdf(): void
    {
        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'tenant_renter_id' => $this->tenantRecord->id,
        ]);

        $response = $this->actingAs($this->tenant)
            ->get(route('tenant.invoices.pdf', $invoice));

        $response->assertOk();
    }

    /**
     * Test manager can filter invoices by status.
     */
    public function test_manager_can_filter_invoices_by_status(): void
    {
        Invoice::factory()->create([
            'tenant_id' => 1,
            'tenant_renter_id' => $this->tenantRecord->id,
            'status' => InvoiceStatus::DRAFT,
        ]);

        Invoice::factory()->create([
            'tenant_id' => 1,
            'tenant_renter_id' => $this->tenantRecord->id,
            'status' => InvoiceStatus::FINALIZED,
        ]);

        $response = $this->actingAs($this->manager)
            ->get(route('manager.invoices.index', ['status' => InvoiceStatus::DRAFT->value]));

        $response->assertOk();
        $response->assertViewHas('invoices', function ($invoices) {
            return $invoices->every(fn ($invoice) => $invoice->status === InvoiceStatus::DRAFT);
        });
    }

    /**
     * Test manager can filter invoices by date range.
     */
    public function test_manager_can_filter_invoices_by_date_range(): void
    {
        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'tenant_renter_id' => $this->tenantRecord->id,
            'billing_period_start' => Carbon::now()->subMonth(),
            'billing_period_end' => Carbon::now(),
        ]);

        $response = $this->actingAs($this->manager)
            ->get(route('manager.invoices.index', [
                'from_date' => Carbon::now()->subMonth()->toDateString(),
                'to_date' => Carbon::now()->toDateString(),
            ]));

        $response->assertOk();
        $response->assertViewHas('invoices', function ($invoices) use ($invoice) {
            return $invoices->contains($invoice);
        });
    }

    /**
     * Test admin can delete draft invoice.
     */
    public function test_admin_can_delete_draft_invoice(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);

        Subscription::factory()->active()->create([
            'user_id' => $admin->id,
        ]);

        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'tenant_renter_id' => $this->tenantRecord->id,
            'status' => InvoiceStatus::DRAFT,
        ]);

        $response = $this->actingAs($admin)
            ->delete(route('manager.invoices.destroy', $invoice));

        $response->assertRedirect();
        $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);
    }

    /**
     * Test admin cannot delete finalized invoice.
     */
    public function test_admin_cannot_delete_finalized_invoice(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);

        Subscription::factory()->active()->create([
            'user_id' => $admin->id,
        ]);

        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'tenant_renter_id' => $this->tenantRecord->id,
            'status' => InvoiceStatus::FINALIZED,
        ]);

        $response = $this->actingAs($admin)
            ->delete(route('manager.invoices.destroy', $invoice));

        $response->assertRedirect();
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id]);
    }
}
