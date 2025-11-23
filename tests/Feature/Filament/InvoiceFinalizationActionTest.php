<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Feature test suite for invoice finalization action in Filament.
 *
 * Tests cover:
 * - Happy path: successful finalization
 * - Authorization: role-based access control
 * - Validation: business rule enforcement
 * - Rate limiting: throttle protection
 * - Audit logging: security trail
 * - Error handling: graceful failures
 * - UI behavior: action visibility and feedback
 */
class InvoiceFinalizationActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('invoice-finalize:*');
    }

    /** @test */
    public function admin_can_finalize_valid_draft_invoice(): void
    {
        // Arrange
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);

        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => 150.00,
            'billing_period_start' => now()->subMonth(),
            'billing_period_end' => now(),
        ]);

        $invoice->items()->create([
            'description' => 'Electricity - Day Rate',
            'quantity' => 100,
            'unit_price' => 1.50,
            'total' => 150.00,
        ]);

        $this->actingAs($admin);

        // Act
        Livewire::test(InvoiceResource\Pages\ViewInvoice::class, [
            'record' => $invoice->id,
        ])
            ->callAction('finalize')
            ->assertHasNoActionErrors()
            ->assertNotified();

        // Assert
        $invoice->refresh();
        $this->assertTrue($invoice->isFinalized());
        $this->assertNotNull($invoice->finalized_at);
        $this->assertEquals(InvoiceStatus::FINALIZED, $invoice->status);
    }

    /** @test */
    public function manager_can_finalize_invoice_in_their_tenant(): void
    {
        // Arrange
        $manager = User::factory()->create([
            'role' => UserRole::MANAGER,
            'tenant_id' => 2,
        ]);

        $invoice = Invoice::factory()->create([
            'tenant_id' => 2,
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => 200.00,
            'billing_period_start' => now()->subMonth(),
            'billing_period_end' => now(),
        ]);

        $invoice->items()->create([
            'description' => 'Water',
            'quantity' => 50,
            'unit_price' => 4.00,
            'total' => 200.00,
        ]);

        $this->actingAs($manager);

        // Act & Assert
        Livewire::test(InvoiceResource\Pages\ViewInvoice::class, [
            'record' => $invoice->id,
        ])
            ->callAction('finalize')
            ->assertHasNoActionErrors();

        $this->assertTrue($invoice->fresh()->isFinalized());
    }

    /** @test */
    public function superadmin_can_finalize_any_tenant_invoice(): void
    {
        // Arrange
        $superadmin = User::factory()->create([
            'role' => UserRole::SUPERADMIN,
            'tenant_id' => null,
        ]);

        $invoice = Invoice::factory()->create([
            'tenant_id' => 999,
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => 100.00,
            'billing_period_start' => now()->subMonth(),
            'billing_period_end' => now(),
        ]);

        $invoice->items()->create([
            'description' => 'Heating',
            'quantity' => 1,
            'unit_price' => 100.00,
            'total' => 100.00,
        ]);

        $this->actingAs($superadmin);

        // Act & Assert
        Livewire::test(InvoiceResource\Pages\ViewInvoice::class, [
            'record' => $invoice->id,
        ])
            ->callAction('finalize')
            ->assertHasNoActionErrors();

        $this->assertTrue($invoice->fresh()->isFinalized());
    }

    /** @test */
    public function tenant_cannot_see_finalize_action(): void
    {
        // Arrange
        $tenant = User::factory()->create([
            'role' => UserRole::TENANT,
            'tenant_id' => 1,
        ]);

        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => 100.00,
        ]);

        $invoice->items()->create([
            'description' => 'Test',
            'quantity' => 1,
            'unit_price' => 100.00,
            'total' => 100.00,
        ]);

        $this->actingAs($tenant);

        // Act
        $component = Livewire::test(InvoiceResource\Pages\ViewInvoice::class, [
            'record' => $invoice->id,
        ]);

        // Assert - action should not be visible
        $actions = $component->instance()->getCachedHeaderActions();
        $finalizeAction = collect($actions)->first(fn ($action) => $action->getName() === 'finalize');

        $this->assertNotNull($finalizeAction);
        $this->assertFalse($finalizeAction->isVisible());
    }

    /** @test */
    public function admin_cannot_finalize_invoice_from_different_tenant(): void
    {
        // Arrange
        $admin1 = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);

        $invoice = Invoice::factory()->create([
            'tenant_id' => 2,
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => 100.00,
        ]);

        $this->actingAs($admin1);

        // Act & Assert - should throw authorization exception
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        Livewire::test(InvoiceResource\Pages\ViewInvoice::class, [
            'record' => $invoice->id,
        ])
            ->callAction('finalize');
    }

    /** @test */
    public function finalize_action_not_visible_for_finalized_invoice(): void
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

        // Act
        $component = Livewire::test(InvoiceResource\Pages\ViewInvoice::class, [
            'record' => $invoice->id,
        ]);

        // Assert
        $actions = $component->instance()->getCachedHeaderActions();
        $finalizeAction = collect($actions)->first(fn ($action) => $action->getName() === 'finalize');

        $this->assertNotNull($finalizeAction);
        $this->assertFalse($finalizeAction->isVisible());
    }

    /** @test */
    public function finalize_action_not_visible_for_paid_invoice(): void
    {
        // Arrange
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);

        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'status' => InvoiceStatus::PAID,
            'finalized_at' => now()->subDay(),
            'total_amount' => 100.00,
        ]);

        $this->actingAs($admin);

        // Act
        $component = Livewire::test(InvoiceResource\Pages\ViewInvoice::class, [
            'record' => $invoice->id,
        ]);

        // Assert
        $actions = $component->instance()->getCachedHeaderActions();
        $finalizeAction = collect($actions)->first(fn ($action) => $action->getName() === 'finalize');

        $this->assertNotNull($finalizeAction);
        $this->assertFalse($finalizeAction->isVisible());
    }

    /** @test */
    public function cannot_finalize_invoice_without_items(): void
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
        // No items added

        $this->actingAs($admin);

        // Act & Assert
        Livewire::test(InvoiceResource\Pages\ViewInvoice::class, [
            'record' => $invoice->id,
        ])
            ->callAction('finalize')
            ->assertHasActionErrors();

        $this->assertTrue($invoice->fresh()->isDraft());
    }

    /** @test */
    public function cannot_finalize_invoice_with_zero_total(): void
    {
        // Arrange
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);

        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => 0.00,
            'billing_period_start' => now()->subMonth(),
            'billing_period_end' => now(),
        ]);

        $invoice->items()->create([
            'description' => 'Free item',
            'quantity' => 1,
            'unit_price' => 0.00,
            'total' => 0.00,
        ]);

        $this->actingAs($admin);

        // Act & Assert
        Livewire::test(InvoiceResource\Pages\ViewInvoice::class, [
            'record' => $invoice->id,
        ])
            ->callAction('finalize')
            ->assertHasActionErrors();

        $this->assertTrue($invoice->fresh()->isDraft());
    }

    /** @test */
    public function cannot_finalize_invoice_with_invalid_billing_period(): void
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
            'billing_period_start' => now(),
            'billing_period_end' => now()->subMonth(), // End before start
        ]);

        $invoice->items()->create([
            'description' => 'Test item',
            'quantity' => 1,
            'unit_price' => 100.00,
            'total' => 100.00,
        ]);

        $this->actingAs($admin);

        // Act & Assert
        Livewire::test(InvoiceResource\Pages\ViewInvoice::class, [
            'record' => $invoice->id,
        ])
            ->callAction('finalize')
            ->assertHasActionErrors();

        $this->assertTrue($invoice->fresh()->isDraft());
    }

    /** @test */
    public function cannot_finalize_invoice_with_invalid_items(): void
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
            'description' => '', // Empty description
            'quantity' => 1,
            'unit_price' => 100.00,
            'total' => 100.00,
        ]);

        $this->actingAs($admin);

        // Act & Assert
        Livewire::test(InvoiceResource\Pages\ViewInvoice::class, [
            'record' => $invoice->id,
        ])
            ->callAction('finalize')
            ->assertHasActionErrors();

        $this->assertTrue($invoice->fresh()->isDraft());
    }

    /** @test */
    public function finalization_is_rate_limited(): void
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

        // Act - attempt 11 times (limit is 10)
        for ($i = 0; $i < 11; $i++) {
            try {
                Livewire::test(InvoiceResource\Pages\ViewInvoice::class, [
                    'record' => $invoice->id,
                ])
                    ->callAction('finalize');
            } catch (\Exception $e) {
                // Ignore exceptions
            }
        }

        // Assert
        $rateLimitKey = 'invoice-finalize:'.$admin->id;
        $this->assertTrue(
            RateLimiter::tooManyAttempts($rateLimitKey, 10),
            'Rate limit should be exceeded after 11 attempts'
        );
    }

    /** @test */
    public function finalization_attempt_is_audit_logged(): void
    {
        // Arrange
        Log::spy();

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

        // Act
        Livewire::test(InvoiceResource\Pages\ViewInvoice::class, [
            'record' => $invoice->id,
        ])
            ->callAction('finalize');

        // Assert
        Log::shouldHaveReceived('info')
            ->with('Invoice finalization attempt', \Mockery::on(function ($context) use ($admin, $invoice) {
                return $context['user_id'] === $admin->id
                    && $context['invoice_id'] === $invoice->id
                    && $context['tenant_id'] === $invoice->tenant_id
                    && isset($context['user_role'])
                    && isset($context['invoice_status']);
            }));
    }

    /** @test */
    public function successful_finalization_is_audit_logged(): void
    {
        // Arrange
        Log::spy();

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

        // Act
        Livewire::test(InvoiceResource\Pages\ViewInvoice::class, [
            'record' => $invoice->id,
        ])
            ->callAction('finalize');

        // Assert
        Log::shouldHaveReceived('info')
            ->with('Invoice finalized successfully', \Mockery::on(function ($context) use ($admin, $invoice) {
                return $context['user_id'] === $admin->id
                    && $context['invoice_id'] === $invoice->id
                    && isset($context['finalized_at']);
            }));
    }

    /** @test */
    public function validation_failure_is_audit_logged(): void
    {
        // Arrange
        Log::spy();

        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);

        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => 0.00, // Invalid
        ]);

        $this->actingAs($admin);

        // Act
        try {
            Livewire::test(InvoiceResource\Pages\ViewInvoice::class, [
                'record' => $invoice->id,
            ])
                ->callAction('finalize');
        } catch (\Exception $e) {
            // Expected to fail
        }

        // Assert
        Log::shouldHaveReceived('warning')
            ->with('Invoice finalization validation failed', \Mockery::on(function ($context) use ($admin, $invoice) {
                return $context['user_id'] === $admin->id
                    && $context['invoice_id'] === $invoice->id
                    && isset($context['errors']);
            }));
    }

    /** @test */
    public function finalization_refreshes_form_data(): void
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

        // Act
        $component = Livewire::test(InvoiceResource\Pages\ViewInvoice::class, [
            'record' => $invoice->id,
        ])
            ->callAction('finalize')
            ->assertHasNoActionErrors();

        // Assert - verify the component reflects updated status
        $invoice->refresh();
        $this->assertEquals(InvoiceStatus::FINALIZED, $invoice->status);
        $this->assertNotNull($invoice->finalized_at);
    }

    /** @test */
    public function concurrent_finalization_is_prevented(): void
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

        // Act - first finalization should succeed
        Livewire::test(InvoiceResource\Pages\ViewInvoice::class, [
            'record' => $invoice->id,
        ])
            ->callAction('finalize')
            ->assertHasNoActionErrors();

        // Second finalization should fail
        $this->expectException(\App\Exceptions\InvoiceAlreadyFinalizedException::class);

        Livewire::test(InvoiceResource\Pages\ViewInvoice::class, [
            'record' => $invoice->fresh()->id,
        ])
            ->callAction('finalize');
    }

    /** @test */
    public function rate_limit_key_is_user_specific(): void
    {
        // Arrange
        $admin1 = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);

        $admin2 = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);

        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => 100.00,
        ]);

        // Act - Admin1 hits rate limit
        $this->actingAs($admin1);
        for ($i = 0; $i < 11; $i++) {
            try {
                Livewire::test(InvoiceResource\Pages\ViewInvoice::class, [
                    'record' => $invoice->id,
                ])
                    ->callAction('finalize');
            } catch (\Exception $e) {
                // Ignore
            }
        }

        $rateLimitKey1 = 'invoice-finalize:'.$admin1->id;
        $this->assertTrue(RateLimiter::tooManyAttempts($rateLimitKey1, 10));

        // Assert - Admin2 should not be affected
        $rateLimitKey2 = 'invoice-finalize:'.$admin2->id;
        $this->assertFalse(RateLimiter::tooManyAttempts($rateLimitKey2, 10));
    }

    /** @test */
    public function edit_action_visible_for_draft_invoice(): void
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
        ]);

        $this->actingAs($admin);

        // Act
        $component = Livewire::test(InvoiceResource\Pages\ViewInvoice::class, [
            'record' => $invoice->id,
        ]);

        // Assert
        $actions = $component->instance()->getCachedHeaderActions();
        $editAction = collect($actions)->first(fn ($action) => $action->getName() === 'edit');

        $this->assertNotNull($editAction);
        $this->assertTrue($editAction->isVisible());
    }

    /** @test */
    public function edit_action_not_visible_for_finalized_invoice(): void
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

        // Act
        $component = Livewire::test(InvoiceResource\Pages\ViewInvoice::class, [
            'record' => $invoice->id,
        ]);

        // Assert
        $actions = $component->instance()->getCachedHeaderActions();
        $editAction = collect($actions)->first(fn ($action) => $action->getName() === 'edit');

        $this->assertNotNull($editAction);
        $this->assertFalse($editAction->isVisible());
    }
}
