<?php

declare(strict_types=1);

namespace Tests\Feature\Enhanced;

use App\Models\Invoice;
use App\Models\ServiceConfiguration;
use App\Models\Tenant;
use App\Models\UtilityService;
use App\Models\User;
use App\Enums\PricingModel;
use App\Enums\DistributionMethod;
use App\Enums\UserRole;
use App\Enums\InvoiceStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Invoice Management Feature Tests
 * 
 * Tests complete invoice management workflows with real services and database.
 * Covers end-to-end scenarios from user interaction to data persistence.
 * 
 * @package Tests\Feature\Enhanced
 */
final class InvoiceManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $tenantUser;
    private Tenant $tenant;
    private UtilityService $utilityService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        $this->adminUser = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);

        $this->tenantUser = User::factory()->create([
            'role' => UserRole::TENANT,
            'tenant_id' => 1,
        ]);

        $this->tenant = Tenant::factory()->create(['id' => 1]);

        $this->utilityService = UtilityService::factory()->create([
            'tenant_id' => 1,
            'name' => 'Fixed Service',
            'unit_of_measurement' => 'month',
            'default_pricing_model' => PricingModel::FIXED_MONTHLY,
        ]);

        ServiceConfiguration::factory()->create([
            'tenant_id' => 1,
            'property_id' => $this->tenant->property_id,
            'utility_service_id' => $this->utilityService->id,
            'pricing_model' => PricingModel::FIXED_MONTHLY,
            'rate_schedule' => ['monthly_rate' => 25.00],
            'distribution_method' => DistributionMethod::EQUAL,
            'is_shared_service' => false,
            'effective_from' => now()->subYears(5),
            'effective_until' => null,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function admin_can_create_invoice_through_web_interface(): void
    {
        // Arrange
        $this->actingAs($this->adminUser);

        // Act
        $response = $this->post(route('invoices.store'), [
            'tenant_id' => $this->tenant->id,
            'tenant_renter_id' => $this->tenant->id,
            'billing_period_start' => '2024-01-01',
            'billing_period_end' => '2024-01-31',
            'due_date' => '2024-02-14',
        ]);

        // Assert
        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('invoices', [
            'tenant_id' => $this->tenant->tenant_id,
            'tenant_renter_id' => $this->tenant->id,
            'billing_period_start' => '2024-01-01',
            'billing_period_end' => '2024-01-31',
            'status' => 'draft',
        ]);
    }

    /** @test */
    public function tenant_user_cannot_create_invoices(): void
    {
        // Arrange
        $this->actingAs($this->tenantUser);

        // Act
        $response = $this->post(route('invoices.store'), [
            'tenant_id' => $this->tenant->id,
            'tenant_renter_id' => $this->tenant->id,
            'billing_period_start' => '2024-01-01',
            'billing_period_end' => '2024-01-31',
        ]);

        // Assert
        $response->assertForbidden();
    }

    /** @test */
    public function admin_can_finalize_draft_invoice(): void
    {
        // Arrange
        $this->actingAs($this->adminUser);
        
        $invoice = Invoice::factory()->create([
            'tenant_id' => $this->tenant->tenant_id,
            'tenant_renter_id' => $this->tenant->id,
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => 100.00,
        ]);

        // Act
        $response = $this->post(route('invoices.finalize', $invoice));

        // Assert
        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'finalized',
        ]);
    }

    /** @test */
    public function bulk_invoice_generation_works_correctly(): void
    {
        // Arrange
        $this->actingAs($this->adminUser);
        
        $tenants = Tenant::factory()->count(3)->create();

        foreach ($tenants as $tenant) {
            ServiceConfiguration::factory()->create([
                'tenant_id' => $tenant->tenant_id,
                'property_id' => $tenant->property_id,
                'utility_service_id' => $this->utilityService->id,
                'pricing_model' => PricingModel::FIXED_MONTHLY,
                'rate_schedule' => ['monthly_rate' => 25.00],
                'distribution_method' => DistributionMethod::EQUAL,
                'is_shared_service' => false,
                'effective_from' => now()->subYears(5),
                'effective_until' => null,
                'is_active' => true,
            ]);
        }

        // Act
        $response = $this->post(route('invoices.generate-bulk'), [
            'tenant_ids' => $tenants->pluck('id')->toArray(),
            'billing_period_start' => '2024-01-01',
            'billing_period_end' => '2024-01-31',
        ]);

        // Assert
        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        // Check that invoices were created for all tenants
        foreach ($tenants as $tenant) {
            $this->assertDatabaseHas('invoices', [
                'tenant_id' => $tenant->tenant_id,
                'tenant_renter_id' => $tenant->id,
                'billing_period_start' => '2024-01-01',
                'billing_period_end' => '2024-01-31',
            ]);
        }
    }

    /** @test */
    public function invoice_display_includes_consumption_history(): void
    {
        // Arrange
        $this->actingAs($this->adminUser);
        
        $invoice = Invoice::factory()->create([
            'tenant_id' => $this->tenant->tenant_id,
            'tenant_renter_id' => $this->tenant->id,
        ]);

        // Act
        $response = $this->get(route('invoices.show', $invoice));

        // Assert
        $response->assertOk();
        $response->assertViewIs('invoices.show');
        $response->assertViewHas('invoice');
        $response->assertViewHas('consumptionHistory');
    }

    /** @test */
    public function api_returns_billing_history_in_json_format(): void
    {
        // Arrange
        $this->actingAs($this->adminUser);
        
        Invoice::factory()->count(3)->create([
            'tenant_id' => $this->tenant->tenant_id,
            'tenant_renter_id' => $this->tenant->id,
        ]);

        // Act
        $response = $this->getJson(route('api.invoices.billing-history', $this->tenant));

        // Assert
        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'billing_period_start',
                    'billing_period_end',
                    'total_amount',
                    'status',
                ]
            ],
            'message',
        ]);
    }

    /** @test */
    public function payment_processing_updates_invoice_status(): void
    {
        // Arrange
        $this->actingAs($this->adminUser);
        
        $invoice = Invoice::factory()->create([
            'tenant_id' => $this->tenant->tenant_id,
            'tenant_renter_id' => $this->tenant->id,
            'status' => InvoiceStatus::FINALIZED,
            'total_amount' => 100.00,
        ]);

        // Act
        $response = $this->post(route('invoices.process-payment', $invoice), [
            'invoice_id' => $invoice->id,
            'amount' => 100.00,
            'payment_method' => 'bank_transfer',
            'payment_reference' => 'REF123456',
            'payment_date' => now()->toDateString(),
        ]);

        // Assert
        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'paid',
        ]);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'payment_reference' => 'REF123456',
            'paid_amount' => 100.00,
        ]);
    }

    /** @test */
    public function partial_payment_updates_status_correctly(): void
    {
        // Arrange
        $this->actingAs($this->adminUser);
        
        $invoice = Invoice::factory()->create([
            'tenant_id' => $this->tenant->tenant_id,
            'tenant_renter_id' => $this->tenant->id,
            'status' => InvoiceStatus::FINALIZED,
            'total_amount' => 100.00,
        ]);

        // Act - Process partial payment
        $response = $this->post(route('invoices.process-payment', $invoice), [
            'invoice_id' => $invoice->id,
            'amount' => 50.00,
            'payment_method' => 'bank_transfer',
            'payment_reference' => 'REF123456',
            'payment_date' => now()->toDateString(),
        ]);

        // Assert
        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'finalized',
            'paid_amount' => 50.00,
        ]);
    }

    /** @test */
    public function service_layer_handles_errors_gracefully(): void
    {
        // Arrange
        $this->actingAs($this->adminUser);

        // Act - Try to create invoice with invalid data
        $response = $this->post(route('invoices.store'), [
            'tenant_id' => 999, // Non-existent tenant
            'billing_period_start' => '2024-01-01',
            'billing_period_end' => '2024-01-31',
        ]);

        // Assert
        $response->assertRedirect();
        $response->assertSessionHasErrors();
    }

    /** @test */
    public function consumption_calculation_service_integration(): void
    {
        // Arrange
        $this->actingAs($this->adminUser);
        
        $invoice = Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        // Act
        $response = $this->getJson(route('api.invoices.consumption-data', $invoice));

        // Assert
        $response->assertOk();
        $response->assertJsonStructure([
            'data',
            'message',
        ]);
    }
}
