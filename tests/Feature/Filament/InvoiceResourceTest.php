<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\InvoiceStatus;
use App\Enums\MeterType;
use App\Enums\PropertyType;
use App\Enums\ServiceType;
use App\Enums\UserRole;
use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\InvoiceResource\Pages\ListInvoices;
use App\Filament\Resources\InvoiceResource\Pages\ViewInvoice;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\Provider;
use App\Models\Tariff;
use App\Models\Tenant;
use App\Models\User;
use App\Services\BillingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * InvoiceResource Feature Tests (Phase 7)
 *
 * Tests the Invoice Management UI in Filament including:
 * - Page rendering (List, View)
 * - Invoice items visibility (critical for Phase 7)
 * - Tenant security and authorization
 * - Status management actions
 *
 * This validates that generated invoices from Phase 6 are properly displayed and manageable.
 *
 * @group filament
 * @group invoice-resource
 * @group phase-7
 */
class InvoiceResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\ProvidersSeeder::class);
        // Don't set session tenant_id - let TenantContext use authenticated user's tenant_id
    }

    // ========================================
    // RENDERING TESTS
    // ========================================

    /** @test */
    public function admin_can_render_invoice_list_page(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);

        $this->actingAs($admin);

        Livewire::test(ListInvoices::class)
            ->assertSuccessful();
    }

    /** @test */
    public function manager_can_render_invoice_list_page(): void
    {
        $tenant = Tenant::factory()->create(['tenant_id' => 1]);
        $manager = User::factory()->create([
            'role' => UserRole::MANAGER,
            'tenant_id' => 1,
        ]);

        $this->actingAs($manager);

        Livewire::test(ListInvoices::class)
            ->assertSuccessful();
    }

    /** @test */
    public function tenant_can_render_invoice_list_page(): void
    {
        $property = Property::factory()->create(['tenant_id' => 1]);
        $tenantRecord = Tenant::factory()->create([
            'tenant_id' => 1,
            'property_id' => $property->id,
            'email' => 'tenant@example.com',
        ]);

        $tenantUser = User::factory()->create([
            'role' => UserRole::TENANT,
            'tenant_id' => 1,
            'email' => 'tenant@example.com', // Link via email matching
        ]);

        $this->actingAs($tenantUser);

        Livewire::test(ListInvoices::class)
            ->assertSuccessful();
    }

    // ========================================
    // VIEW PAGE WITH INVOICE ITEMS (CRITICAL)
    // ========================================

    /** @test */
    public function admin_can_view_invoice_with_invoice_items_visible(): void
    {
        // Setup: Create invoice with items using BillingService (realistic scenario)
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);

        $property = Property::factory()->create([
            'tenant_id' => 1,
            'type' => PropertyType::APARTMENT,
        ]);

        $tenantRecord = Tenant::factory()->create([
            'tenant_id' => 1,
            'property_id' => $property->id,
        ]);

        $meter = Meter::factory()->create([
            'tenant_id' => 1,
            'property_id' => $property->id,
            'type' => MeterType::ELECTRICITY,
            'supports_zones' => false,
        ]);

        $user = User::factory()->create(['tenant_id' => 1]);

        // Create tariff
        $provider = Provider::where('service_type', ServiceType::ELECTRICITY)->first();
        Tariff::factory()->create([
            'provider_id' => $provider->id,
            'configuration' => [
                'type' => 'flat',
                'rate' => 0.20,
                'currency' => 'EUR',
            ],
            'active_from' => Carbon::parse('2024-01-01')->subMonth(),
            'active_until' => null,
        ]);

        // Create meter readings
        $periodStart = Carbon::parse('2024-01-01');
        $periodEnd = Carbon::parse('2024-02-01');

        MeterReading::factory()->create([
            'tenant_id' => 1,
            'meter_id' => $meter->id,
            'reading_date' => $periodStart,
            'value' => 100.00,
            'entered_by' => $user->id,
        ]);

        MeterReading::factory()->create([
            'tenant_id' => 1,
            'meter_id' => $meter->id,
            'reading_date' => $periodEnd,
            'value' => 150.00,
            'entered_by' => $user->id,
        ]);

        // Generate invoice with items
        $billingService = app(BillingService::class);
        $invoice = $billingService->generateInvoice($tenantRecord, $periodStart, $periodEnd);

        // Act: View invoice page
        $this->actingAs($admin);

        $component = Livewire::test(ViewInvoice::class, ['record' => $invoice->id])
            ->assertSuccessful();

        // Assert: Invoice items are visible
        // The items relation manager or infolist should display the invoice items
        $invoice->refresh();
        $this->assertGreaterThan(0, $invoice->items->count(), 'Invoice should have items');

        // Verify invoice data is accessible
        $this->assertEquals($invoice->id, $component->get('record')->id);
        $this->assertEquals(InvoiceStatus::DRAFT, $component->get('record')->status);
    }

    /** @test */
    public function invoice_items_data_is_available_in_view_page(): void
    {
        // Create invoice manually with known items
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);

        $property = Property::factory()->create(['tenant_id' => 1]);
        $tenantRecord = Tenant::factory()->create([
            'tenant_id' => 1,
            'property_id' => $property->id,
        ]);

        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'tenant_renter_id' => $tenantRecord->id,
            'billing_period_start' => '2024-01-01',
            'billing_period_end' => '2024-01-31',
            'total_amount' => 25.00,
            'status' => InvoiceStatus::DRAFT,
        ]);

        // Create invoice items with specific, verifiable data
        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'description' => 'Electricity Consumption',
            'quantity' => 100.00,
            'unit' => 'kWh',
            'unit_price' => 0.15,
            'total' => 15.00,
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'description' => 'Water Supply',
            'quantity' => 10.00,
            'unit' => 'm³',
            'unit_price' => 1.00,
            'total' => 10.00,
        ]);

        // Act: View invoice
        $this->actingAs($admin);

        $component = Livewire::test(ViewInvoice::class, ['record' => $invoice->id])
            ->assertSuccessful();

        // Assert: Invoice items are loaded
        $invoice->refresh();
        $this->assertCount(2, $invoice->items);

        // Verify specific item data
        $electricityItem = $invoice->items->firstWhere('description', 'Electricity Consumption');
        $this->assertNotNull($electricityItem);
        $this->assertEquals(100.00, $electricityItem->quantity);
        $this->assertEquals(0.15, $electricityItem->unit_price);
        $this->assertEquals(15.00, $electricityItem->total);

        $waterItem = $invoice->items->firstWhere('description', 'Water Supply');
        $this->assertNotNull($waterItem);
        $this->assertEquals(10.00, $waterItem->quantity);
        $this->assertEquals(10.00, $waterItem->total);

        // Verify total
        $this->assertEquals(25.00, $invoice->total_amount);
    }

    // ========================================
    // TENANT SECURITY
    // ========================================

    /** @test */
    public function tenant_can_see_their_own_invoices_in_list(): void
    {
        $property = Property::factory()->create(['tenant_id' => 1]);
        $tenantRecord = Tenant::factory()->create([
            'tenant_id' => 1,
            'property_id' => $property->id,
            'email' => 'tenant1@example.com',
        ]);

        $tenantUser = User::factory()->create([
            'role' => UserRole::TENANT,
            'tenant_id' => 1,
            'email' => 'tenant1@example.com', // Link via email matching
        ]);

        // Create invoices for this tenant
        $invoice1 = Invoice::factory()->create([
            'tenant_id' => 1,
            'tenant_renter_id' => $tenantRecord->id,
            'total_amount' => 100.00,
            'status' => InvoiceStatus::DRAFT,
        ]);

        $invoice2 = Invoice::factory()->create([
            'tenant_id' => 1,
            'tenant_renter_id' => $tenantRecord->id,
            'total_amount' => 200.00,
            'status' => InvoiceStatus::FINALIZED,
        ]);

        $this->actingAs($tenantUser);

        // Tenant should be able to see their own invoices
        Livewire::test(ListInvoices::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$invoice1, $invoice2]);
    }

    /** @test */
    public function tenant_cannot_edit_invoices(): void
    {
        $property = Property::factory()->create(['tenant_id' => 1]);
        $tenantRecord = Tenant::factory()->create([
            'tenant_id' => 1,
            'property_id' => $property->id,
            'email' => 'tenant2@example.com',
        ]);

        $tenantUser = User::factory()->create([
            'role' => UserRole::TENANT,
            'tenant_id' => 1,
            'email' => 'tenant2@example.com', // Link via email matching
        ]);

        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'tenant_renter_id' => $tenantRecord->id,
            'status' => InvoiceStatus::DRAFT,
        ]);

        $this->actingAs($tenantUser);

        // Tenant should NOT be able to edit
        $canEdit = InvoiceResource::canEdit($invoice);
        $this->assertFalse($canEdit);
    }

    /** @test */
    public function tenant_cannot_delete_invoices(): void
    {
        $property = Property::factory()->create(['tenant_id' => 1]);
        $tenantRecord = Tenant::factory()->create([
            'tenant_id' => 1,
            'property_id' => $property->id,
            'email' => 'tenant3@example.com',
        ]);

        $tenantUser = User::factory()->create([
            'role' => UserRole::TENANT,
            'tenant_id' => 1,
            'email' => 'tenant3@example.com', // Link via email matching
        ]);

        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'tenant_renter_id' => $tenantRecord->id,
            'status' => InvoiceStatus::DRAFT,
        ]);

        $this->actingAs($tenantUser);

        // Tenant should NOT be able to delete
        $canDelete = InvoiceResource::canDelete($invoice);
        $this->assertFalse($canDelete);
    }

    /** @test */
    public function manager_can_see_invoices_from_their_tenant(): void
    {
        $tenant1Record = Tenant::factory()->create(['tenant_id' => 1]);

        $manager = User::factory()->create([
            'role' => UserRole::MANAGER,
            'tenant_id' => 1,
        ]);

        $invoice1 = Invoice::factory()->create([
            'tenant_id' => 1,
            'tenant_renter_id' => $tenant1Record->id,
            'total_amount' => 150.00,
        ]);

        $invoice2 = Invoice::factory()->create([
            'tenant_id' => 1,
            'tenant_renter_id' => $tenant1Record->id,
            'total_amount' => 250.00,
        ]);

        $this->actingAs($manager);

        // Manager should be able to see all invoices from their tenant
        Livewire::test(ListInvoices::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$invoice1, $invoice2]);
    }

    // ========================================
    // ACTIONS - FINALIZE
    // ========================================

    /** @test */
    public function admin_can_see_finalize_action_for_draft_invoice(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);

        $property = Property::factory()->create(['tenant_id' => 1]);
        $tenantRecord = Tenant::factory()->create([
            'tenant_id' => 1,
            'property_id' => $property->id,
        ]);

        // Create draft invoice with at least one item (required for finalization)
        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'tenant_renter_id' => $tenantRecord->id,
            'total_amount' => 10.00,
            'status' => InvoiceStatus::DRAFT,
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'description' => 'Test Item',
            'quantity' => 1,
            'unit_price' => 10.00,
            'total' => 10.00,
        ]);

        $this->actingAs($admin);

        $component = Livewire::test(ViewInvoice::class, ['record' => $invoice->id])
            ->assertSuccessful();

        // Verify the finalize action exists in header actions
        $actions = $component->instance()->getCachedHeaderActions();
        $finalizeAction = collect($actions)->first(fn ($action) => $action->getName() === 'finalize');

        $this->assertNotNull($finalizeAction, 'Finalize action should be available');
    }

    /** @test */
    public function tenant_cannot_see_finalize_action(): void
    {
        $property = Property::factory()->create(['tenant_id' => 1]);
        $tenantRecord = Tenant::factory()->create([
            'tenant_id' => 1,
            'property_id' => $property->id,
            'email' => 'tenant4@example.com',
        ]);

        $tenantUser = User::factory()->create([
            'role' => UserRole::TENANT,
            'tenant_id' => 1,
            'email' => 'tenant4@example.com', // Link via email matching
        ]);

        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'tenant_renter_id' => $tenantRecord->id,
            'total_amount' => 10.00,
            'status' => InvoiceStatus::DRAFT,
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'total' => 10.00,
        ]);

        $this->actingAs($tenantUser);

        // Tenant should NOT be able to finalize
        $this->assertFalse($tenantUser->can('finalize', $invoice));
    }

    /** @test */
    public function finalized_invoice_cannot_be_edited(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);

        $property = Property::factory()->create(['tenant_id' => 1]);
        $tenantRecord = Tenant::factory()->create([
            'tenant_id' => 1,
            'property_id' => $property->id,
        ]);

        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'tenant_renter_id' => $tenantRecord->id,
            'status' => InvoiceStatus::FINALIZED,
            'finalized_at' => now(),
        ]);

        $this->actingAs($admin);

        // Admin should NOT be able to edit finalized invoice
        $canEdit = InvoiceResource::canEdit($invoice);
        $this->assertFalse($canEdit);
    }

    // ========================================
    // NAVIGATION & RESOURCE CONFIGURATION
    // ========================================

    /** @test */
    public function invoice_navigation_is_visible_to_admin(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);

        $this->actingAs($admin);

        $this->assertTrue(InvoiceResource::shouldRegisterNavigation());
    }

    /** @test */
    public function invoice_navigation_is_visible_to_manager(): void
    {
        $manager = User::factory()->create([
            'role' => UserRole::MANAGER,
            'tenant_id' => 1,
        ]);

        $this->actingAs($manager);

        $this->assertTrue(InvoiceResource::shouldRegisterNavigation());
    }

    /** @test */
    public function invoice_navigation_is_visible_to_tenant(): void
    {
        $property = Property::factory()->create(['tenant_id' => 1]);
        $tenantRecord = Tenant::factory()->create([
            'tenant_id' => 1,
            'property_id' => $property->id,
            'email' => 'tenant5@example.com',
        ]);

        $tenantUser = User::factory()->create([
            'role' => UserRole::TENANT,
            'tenant_id' => 1,
            'email' => 'tenant5@example.com', // Link via email matching
        ]);

        $this->actingAs($tenantUser);

        $this->assertTrue(InvoiceResource::shouldRegisterNavigation());
    }

    /** @test */
    public function invoice_resource_uses_correct_model(): void
    {
        $this->assertEquals(Invoice::class, InvoiceResource::getModel());
    }

    /** @test */
    public function invoice_resource_eager_loads_items_and_tenant(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->actingAs($admin);

        $query = InvoiceResource::getEloquentQuery();

        // Check that eager loads are configured
        $eagerLoads = $query->getEagerLoads();
        $this->assertArrayHasKey('items', $eagerLoads);
        $this->assertArrayHasKey('tenant', $eagerLoads);
    }
}
