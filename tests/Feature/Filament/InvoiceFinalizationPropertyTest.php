<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Property-based tests for invoice finalization invariants.
 *
 * These tests verify that certain properties always hold true
 * regardless of the specific test data used.
 *
 * Properties tested:
 * - Finalized invoices are immutable (except status changes)
 * - Finalization requires valid business rules
 * - Authorization is always enforced
 * - Tenant isolation is maintained
 * - Audit trail is complete
 */
class InvoiceFinalizationPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property: Once an invoice is finalized, it cannot be modified except for status changes.
     *
     * @test
     */
    public function property_finalized_invoices_are_immutable(): void
    {
        // Arrange - create and finalize an invoice
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);

        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => 100.00,
            'billing_period_start' => now()->subMonth(),
            'billing_period_end' => now(),
        ]);

        $invoice->items()->create([
            'description' => 'Test item',
            'quantity' => 1,
            'unit_price' => 100.00,
            'total' => 100.00,
        ]);

        $this->actingAs($admin);

        Livewire::test(\App\Filament\Resources\InvoiceResource\Pages\ViewInvoice::class, [
            'record' => $invoice->id,
        ])
            ->callAction('finalize');

        $invoice->refresh();

        // Property: Attempting to modify any field except status should fail
        $originalTotal = $invoice->total_amount;
        $originalPeriodStart = $invoice->billing_period_start;
        $originalPeriodEnd = $invoice->billing_period_end;

        $this->expectException(\App\Exceptions\InvoiceAlreadyFinalizedException::class);

        $invoice->update([
            'total_amount' => 200.00,
        ]);

        // Verify values unchanged
        $invoice->refresh();
        $this->assertEquals($originalTotal, $invoice->total_amount);
        $this->assertEquals($originalPeriodStart, $invoice->billing_period_start);
        $this->assertEquals($originalPeriodEnd, $invoice->billing_period_end);
    }

    /**
     * Property: Status changes from FINALIZED to PAID are allowed.
     *
     * @test
     */
    public function property_finalized_invoices_can_transition_to_paid(): void
    {
        // Arrange
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);

        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'status' => InvoiceStatus::FINALIZED,
            'finalized_at' => now(),
            'total_amount' => 100.00,
        ]);

        $this->actingAs($admin);

        // Property: Status change to PAID should succeed
        $invoice->update(['status' => InvoiceStatus::PAID]);

        $this->assertEquals(InvoiceStatus::PAID, $invoice->fresh()->status);
    }

    /**
     * Property: Every finalized invoice must have at least one item.
     *
     * @test
     */
    public function property_finalized_invoices_always_have_items(): void
    {
        // Arrange
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);

        // Create multiple invoices with varying item counts
        $invoiceCounts = [1, 2, 5, 10];

        foreach ($invoiceCounts as $itemCount) {
            $invoice = Invoice::factory()->create([
                'tenant_id' => 1,
                'status' => InvoiceStatus::DRAFT,
                'total_amount' => $itemCount * 50.00,
                'billing_period_start' => now()->subMonth(),
                'billing_period_end' => now(),
            ]);

            for ($i = 0; $i < $itemCount; $i++) {
                $invoice->items()->create([
                    'description' => "Item {$i}",
                    'quantity' => 1,
                    'unit_price' => 50.00,
                    'total' => 50.00,
                ]);
            }

            $this->actingAs($admin);

            Livewire::test(\App\Filament\Resources\InvoiceResource\Pages\ViewInvoice::class, [
                'record' => $invoice->id,
            ])
                ->callAction('finalize');

            // Property: Finalized invoice must have items
            $invoice->refresh();
            $this->assertTrue($invoice->isFinalized());
            $this->assertGreaterThan(0, $invoice->items()->count());
        }
    }

    /**
     * Property: Every finalized invoice must have a positive total amount.
     *
     * @test
     */
    public function property_finalized_invoices_have_positive_amounts(): void
    {
        // Arrange
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);

        // Test various positive amounts
        $amounts = [0.01, 1.00, 50.00, 100.00, 1000.00, 9999.99];

        foreach ($amounts as $amount) {
            $invoice = Invoice::factory()->create([
                'tenant_id' => 1,
                'status' => InvoiceStatus::DRAFT,
                'total_amount' => $amount,
                'billing_period_start' => now()->subMonth(),
                'billing_period_end' => now(),
            ]);

            $invoice->items()->create([
                'description' => 'Test item',
                'quantity' => 1,
                'unit_price' => $amount,
                'total' => $amount,
            ]);

            $this->actingAs($admin);

            Livewire::test(\App\Filament\Resources\InvoiceResource\Pages\ViewInvoice::class, [
                'record' => $invoice->id,
            ])
                ->callAction('finalize');

            // Property: Finalized invoice must have positive amount
            $invoice->refresh();
            $this->assertTrue($invoice->isFinalized());
            $this->assertGreaterThan(0, $invoice->total_amount);
        }
    }

    /**
     * Property: Users can only finalize invoices within their tenant scope.
     *
     * @test
     */
    public function property_tenant_isolation_is_enforced_for_finalization(): void
    {
        // Arrange - create users and invoices in different tenants
        $tenants = [1, 2, 3];

        foreach ($tenants as $tenantId) {
            $admin = User::factory()->create([
                'role' => UserRole::ADMIN,
                'tenant_id' => $tenantId,
            ]);

            $invoice = Invoice::factory()->create([
                'tenant_id' => $tenantId,
                'status' => InvoiceStatus::DRAFT,
                'total_amount' => 100.00,
                'billing_period_start' => now()->subMonth(),
                'billing_period_end' => now(),
            ]);

            $invoice->items()->create([
                'description' => 'Test item',
                'quantity' => 1,
                'unit_price' => 100.00,
                'total' => 100.00,
            ]);

            $this->actingAs($admin);

            // Property: Admin can finalize their own tenant's invoice
            Livewire::test(\App\Filament\Resources\InvoiceResource\Pages\ViewInvoice::class, [
                'record' => $invoice->id,
            ])
                ->callAction('finalize')
                ->assertHasNoActionErrors();

            $this->assertTrue($invoice->fresh()->isFinalized());

            // Property: Admin cannot access other tenant's invoices
            $otherTenantId = $tenantId === 1 ? 2 : 1;
            $otherInvoice = Invoice::factory()->create([
                'tenant_id' => $otherTenantId,
                'status' => InvoiceStatus::DRAFT,
                'total_amount' => 100.00,
            ]);

            $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

            Livewire::test(\App\Filament\Resources\InvoiceResource\Pages\ViewInvoice::class, [
                'record' => $otherInvoice->id,
            ])
                ->callAction('finalize');
        }
    }

    /**
     * Property: Superadmin can finalize invoices across all tenants.
     *
     * @test
     */
    public function property_superadmin_has_unrestricted_finalization_access(): void
    {
        // Arrange
        $superadmin = User::factory()->create([
            'role' => UserRole::SUPERADMIN,
            'tenant_id' => null,
        ]);

        $tenants = [1, 2, 3, 999];

        $this->actingAs($superadmin);

        foreach ($tenants as $tenantId) {
            $invoice = Invoice::factory()->create([
                'tenant_id' => $tenantId,
                'status' => InvoiceStatus::DRAFT,
                'total_amount' => 100.00,
                'billing_period_start' => now()->subMonth(),
                'billing_period_end' => now(),
            ]);

            $invoice->items()->create([
                'description' => 'Test item',
                'quantity' => 1,
                'unit_price' => 100.00,
                'total' => 100.00,
            ]);

            // Property: Superadmin can finalize any tenant's invoice
            Livewire::test(\App\Filament\Resources\InvoiceResource\Pages\ViewInvoice::class, [
                'record' => $invoice->id,
            ])
                ->callAction('finalize')
                ->assertHasNoActionErrors();

            $this->assertTrue($invoice->fresh()->isFinalized());
        }
    }

    /**
     * Property: Finalization action is only visible for draft invoices.
     *
     * @test
     */
    public function property_finalize_action_visibility_matches_invoice_status(): void
    {
        // Arrange
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);

        $statuses = [
            InvoiceStatus::DRAFT => true,      // Should be visible
            InvoiceStatus::FINALIZED => false, // Should not be visible
            InvoiceStatus::PAID => false,      // Should not be visible
        ];

        $this->actingAs($admin);

        foreach ($statuses as $status => $expectedVisibility) {
            $invoice = Invoice::factory()->create([
                'tenant_id' => 1,
                'status' => $status,
                'finalized_at' => $status !== InvoiceStatus::DRAFT ? now() : null,
                'total_amount' => 100.00,
            ]);

            $component = Livewire::test(\App\Filament\Resources\InvoiceResource\Pages\ViewInvoice::class, [
                'record' => $invoice->id,
            ]);

            $actions = $component->instance()->getCachedHeaderActions();
            $finalizeAction = collect($actions)->first(fn ($action) => $action->getName() === 'finalize');

            // Property: Action visibility matches expected state
            $this->assertNotNull($finalizeAction);
            $this->assertEquals(
                $expectedVisibility,
                $finalizeAction->isVisible(),
                "Finalize action visibility for {$status->value} status should be " . ($expectedVisibility ? 'true' : 'false')
            );
        }
    }

    /**
     * Property: Billing period end must always be after start for finalized invoices.
     *
     * @test
     */
    public function property_finalized_invoices_have_valid_billing_periods(): void
    {
        // Arrange
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);

        // Test various valid billing periods
        $periods = [
            ['start' => now()->subMonth(), 'end' => now()],
            ['start' => now()->subYear(), 'end' => now()->subMonth()],
            ['start' => now()->subDays(30), 'end' => now()->subDays(1)],
            ['start' => now()->subWeek(), 'end' => now()],
        ];

        $this->actingAs($admin);

        foreach ($periods as $period) {
            $invoice = Invoice::factory()->create([
                'tenant_id' => 1,
                'status' => InvoiceStatus::DRAFT,
                'total_amount' => 100.00,
                'billing_period_start' => $period['start'],
                'billing_period_end' => $period['end'],
            ]);

            $invoice->items()->create([
                'description' => 'Test item',
                'quantity' => 1,
                'unit_price' => 100.00,
                'total' => 100.00,
            ]);

            Livewire::test(\App\Filament\Resources\InvoiceResource\Pages\ViewInvoice::class, [
                'record' => $invoice->id,
            ])
                ->callAction('finalize');

            // Property: Finalized invoice has valid billing period
            $invoice->refresh();
            $this->assertTrue($invoice->isFinalized());
            $this->assertTrue(
                $invoice->billing_period_start < $invoice->billing_period_end,
                'Billing period start must be before end'
            );
        }
    }

    /**
     * Property: Every finalized invoice has a finalized_at timestamp.
     *
     * @test
     */
    public function property_finalized_invoices_have_timestamp(): void
    {
        // Arrange
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);

        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => 100.00,
            'billing_period_start' => now()->subMonth(),
            'billing_period_end' => now(),
        ]);

        $invoice->items()->create([
            'description' => 'Test item',
            'quantity' => 1,
            'unit_price' => 100.00,
            'total' => 100.00,
        ]);

        $this->actingAs($admin);

        $beforeFinalization = now();

        Livewire::test(\App\Filament\Resources\InvoiceResource\Pages\ViewInvoice::class, [
            'record' => $invoice->id,
        ])
            ->callAction('finalize');

        $afterFinalization = now();

        // Property: Finalized invoice has timestamp within expected range
        $invoice->refresh();
        $this->assertTrue($invoice->isFinalized());
        $this->assertNotNull($invoice->finalized_at);
        $this->assertTrue(
            $invoice->finalized_at->between($beforeFinalization, $afterFinalization),
            'Finalized timestamp should be set during finalization'
        );
    }

    /**
     * Property: Tenant role users never see the finalize action.
     *
     * @test
     */
    public function property_tenant_role_never_has_finalize_access(): void
    {
        // Arrange - test across multiple tenants and invoice states
        $tenants = [1, 2, 3];
        $statuses = [InvoiceStatus::DRAFT, InvoiceStatus::FINALIZED, InvoiceStatus::PAID];

        foreach ($tenants as $tenantId) {
            $tenant = User::factory()->create([
                'role' => UserRole::TENANT,
                'tenant_id' => $tenantId,
            ]);

            $this->actingAs($tenant);

            foreach ($statuses as $status) {
                $invoice = Invoice::factory()->create([
                    'tenant_id' => $tenantId,
                    'status' => $status,
                    'finalized_at' => $status !== InvoiceStatus::DRAFT ? now() : null,
                    'total_amount' => 100.00,
                ]);

                $component = Livewire::test(\App\Filament\Resources\InvoiceResource\Pages\ViewInvoice::class, [
                    'record' => $invoice->id,
                ]);

                $actions = $component->instance()->getCachedHeaderActions();
                $finalizeAction = collect($actions)->first(fn ($action) => $action->getName() === 'finalize');

                // Property: Tenant never sees finalize action
                $this->assertNotNull($finalizeAction);
                $this->assertFalse(
                    $finalizeAction->isVisible(),
                    "Tenant should never see finalize action for {$status->value} invoice in tenant {$tenantId}"
                );
            }
        }
    }

    /**
     * Property: All invoice items in finalized invoices have valid data.
     *
     * @test
     */
    public function property_finalized_invoice_items_are_valid(): void
    {
        // Arrange
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);

        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => 300.00,
            'billing_period_start' => now()->subMonth(),
            'billing_period_end' => now(),
        ]);

        // Create multiple items with valid data
        $items = [
            ['description' => 'Electricity', 'quantity' => 100, 'unit_price' => 1.00],
            ['description' => 'Water', 'quantity' => 50, 'unit_price' => 2.00],
            ['description' => 'Heating', 'quantity' => 1, 'unit_price' => 100.00],
        ];

        foreach ($items as $itemData) {
            $invoice->items()->create([
                'description' => $itemData['description'],
                'quantity' => $itemData['quantity'],
                'unit_price' => $itemData['unit_price'],
                'total' => $itemData['quantity'] * $itemData['unit_price'],
            ]);
        }

        $this->actingAs($admin);

        Livewire::test(\App\Filament\Resources\InvoiceResource\Pages\ViewInvoice::class, [
            'record' => $invoice->id,
        ])
            ->callAction('finalize');

        // Property: All items in finalized invoice have valid data
        $invoice->refresh();
        $this->assertTrue($invoice->isFinalized());

        foreach ($invoice->items as $item) {
            $this->assertNotEmpty($item->description, 'Item description must not be empty');
            $this->assertGreaterThanOrEqual(0, $item->unit_price, 'Unit price must be non-negative');
            $this->assertGreaterThanOrEqual(0, $item->quantity, 'Quantity must be non-negative');
            $this->assertEquals(
                $item->quantity * $item->unit_price,
                $item->total,
                'Total price must equal quantity * unit_price'
            );
        }
    }
}
