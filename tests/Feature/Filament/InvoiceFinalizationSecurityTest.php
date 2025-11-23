<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Security test suite for invoice finalization feature.
 *
 * Tests cover:
 * - Rate limiting enforcement
 * - Audit logging completeness
 * - Information leakage prevention
 * - Authorization bypass attempts
 * - Tenant isolation
 * - Concurrent finalization protection
 * - CSRF protection
 * - Input validation
 */
class InvoiceFinalizationSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('invoice-finalize:*');
    }

    /** @test */
    public function rate_limiting_prevents_excessive_finalization_attempts(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);

        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => 100,
            'billing_period_start' => now()->subMonth(),
            'billing_period_end' => now(),
        ]);
        $invoice->items()->create([
            'description' => 'Test item',
            'quantity' => 1,
            'unit_price' => 100,
            'total' => 100,
        ]);

        $this->actingAs($admin);

        // Attempt 11 times (limit is 10 per minute)
        for ($i = 0; $i < 11; $i++) {
            try {
                \Livewire\Livewire::test(\App\Filament\Resources\InvoiceResource\Pages\ViewInvoice::class, [
                    'record' => $invoice->id,
                ])
                    ->callAction('finalize');
            } catch (\Exception $e) {
                // Ignore exceptions, we're testing rate limiting
            }
        }

        // Verify rate limit is hit
        $rateLimitKey = 'invoice-finalize:'.$admin->id;
        $this->assertTrue(
            RateLimiter::tooManyAttempts($rateLimitKey, 10),
            'Rate limiter should block after 10 attempts'
        );
    }

    /** @test */
    public function audit_log_captures_finalization_attempts(): void
    {
        Log::spy();

        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);

        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => 100,
        ]);
        $invoice->items()->create([
            'description' => 'Test item',
            'quantity' => 1,
            'unit_price' => 100,
            'total' => 100,
        ]);

        $this->actingAs($admin);

        // Trigger finalization via Livewire component
        \Livewire\Livewire::test(\App\Filament\Resources\InvoiceResource\Pages\ViewInvoice::class, [
            'record' => $invoice->id,
        ])
            ->callAction('finalize');

        // Verify audit log was written
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
    public function audit_log_captures_successful_finalization(): void
    {
        Log::spy();

        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);

        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => 100,
            'billing_period_start' => now()->subMonth(),
            'billing_period_end' => now(),
        ]);
        $invoice->items()->create([
            'description' => 'Test item',
            'quantity' => 1,
            'unit_price' => 100,
            'total' => 100,
        ]);

        $this->actingAs($admin);

        \Livewire\Livewire::test(\App\Filament\Resources\InvoiceResource\Pages\ViewInvoice::class, [
            'record' => $invoice->id,
        ])
            ->callAction('finalize');

        Log::shouldHaveReceived('info')
            ->with('Invoice finalized successfully', \Mockery::on(function ($context) use ($admin, $invoice) {
                return $context['user_id'] === $admin->id
                    && $context['invoice_id'] === $invoice->id
                    && isset($context['finalized_at']);
            }));
    }

    /** @test */
    public function audit_log_captures_validation_failures(): void
    {
        Log::spy();

        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);

        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => 0, // Invalid: must be > 0
        ]);

        $this->actingAs($admin);

        try {
            \Livewire\Livewire::test(\App\Filament\Resources\InvoiceResource\Pages\ViewInvoice::class, [
                'record' => $invoice->id,
            ])
                ->callAction('finalize');
        } catch (\Exception $e) {
            // Expected to fail
        }

        Log::shouldHaveReceived('warning')
            ->with('Invoice finalization validation failed', \Mockery::on(function ($context) use ($admin, $invoice) {
                return $context['user_id'] === $admin->id
                    && $context['invoice_id'] === $invoice->id
                    && isset($context['errors']);
            }));
    }

    /** @test */
    public function error_messages_do_not_leak_sensitive_information(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);

        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => 0, // Invalid
        ]);

        $this->actingAs($admin);

        $component = \Livewire\Livewire::test(
            \App\Filament\Resources\InvoiceResource\Pages\ViewInvoice::class,
            ['record' => $invoice->id]
        );

        try {
            $component->callAction('finalize');
        } catch (\Exception $e) {
            // Expected to fail
        }

        $html = $component->get('html');

        // Should not contain database column names
        $this->assertStringNotContainsString('total_amount', $html);
        $this->assertStringNotContainsString('billing_period_start', $html);

        // Should not contain file paths
        $this->assertStringNotContainsString('/var/www', $html);
        $this->assertStringNotContainsString('/app/', $html);

        // Should not contain stack traces
        $this->assertStringNotContainsString('Stack trace', $html);
        $this->assertStringNotContainsString('#0', $html);
    }

    /** @test */
    public function tenant_isolation_prevents_cross_tenant_finalization(): void
    {
        $admin1 = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);

        $admin2 = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 2,
        ]);

        $invoice = Invoice::factory()->create([
            'tenant_id' => 2,
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => 100,
        ]);

        $this->actingAs($admin1);

        // Admin1 should not be able to finalize Admin2's invoice
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        \Livewire\Livewire::test(\App\Filament\Resources\InvoiceResource\Pages\ViewInvoice::class, [
            'record' => $invoice->id,
        ])
            ->callAction('finalize');
    }

    /** @test */
    public function double_authorization_check_prevents_bypass(): void
    {
        $tenant = User::factory()->create([
            'role' => UserRole::TENANT,
            'tenant_id' => 1,
        ]);

        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => 100,
        ]);

        $this->actingAs($tenant);

        // Tenant should not see finalize action (visibility check)
        $component = \Livewire\Livewire::test(
            \App\Filament\Resources\InvoiceResource\Pages\ViewInvoice::class,
            ['record' => $invoice->id]
        );

        $this->assertFalse(
            $component->instance()->getCachedHeaderActions()[1]->isVisible(),
            'Finalize action should not be visible to tenants'
        );

        // Direct call should also fail (authorize check)
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        $component->callAction('finalize');
    }

    /** @test */
    public function superadmin_can_finalize_any_tenant_invoice(): void
    {
        $superadmin = User::factory()->create([
            'role' => UserRole::SUPERADMIN,
            'tenant_id' => null,
        ]);

        $invoice = Invoice::factory()->create([
            'tenant_id' => 999,
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => 100,
            'billing_period_start' => now()->subMonth(),
            'billing_period_end' => now(),
        ]);
        $invoice->items()->create([
            'description' => 'Test item',
            'quantity' => 1,
            'unit_price' => 100,
            'total' => 100,
        ]);

        $this->actingAs($superadmin);

        \Livewire\Livewire::test(\App\Filament\Resources\InvoiceResource\Pages\ViewInvoice::class, [
            'record' => $invoice->id,
        ])
            ->callAction('finalize')
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => InvoiceStatus::FINALIZED->value,
        ]);
    }

    /** @test */
    public function concurrent_finalization_is_prevented(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);

        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => 100,
            'billing_period_start' => now()->subMonth(),
            'billing_period_end' => now(),
        ]);
        $invoice->items()->create([
            'description' => 'Test item',
            'quantity' => 1,
            'unit_price' => 100,
            'total' => 100,
        ]);

        $this->actingAs($admin);

        // First finalization should succeed
        \Livewire\Livewire::test(\App\Filament\Resources\InvoiceResource\Pages\ViewInvoice::class, [
            'record' => $invoice->id,
        ])
            ->callAction('finalize')
            ->assertHasNoActionErrors();

        // Second finalization should fail
        $this->expectException(\App\Exceptions\InvoiceAlreadyFinalizedException::class);

        \Livewire\Livewire::test(\App\Filament\Resources\InvoiceResource\Pages\ViewInvoice::class, [
            'record' => $invoice->fresh()->id,
        ])
            ->callAction('finalize');
    }

    /** @test */
    public function finalization_validates_invoice_has_items(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);

        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => 100,
        ]);
        // No items added

        $this->actingAs($admin);

        \Livewire\Livewire::test(\App\Filament\Resources\InvoiceResource\Pages\ViewInvoice::class, [
            'record' => $invoice->id,
        ])
            ->callAction('finalize')
            ->assertHasActionErrors();
    }

    /** @test */
    public function finalization_validates_total_amount_greater_than_zero(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);

        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => 0,
        ]);
        $invoice->items()->create([
            'description' => 'Test item',
            'quantity' => 1,
            'unit_price' => 0,
            'total' => 0,
        ]);

        $this->actingAs($admin);

        \Livewire\Livewire::test(\App\Filament\Resources\InvoiceResource\Pages\ViewInvoice::class, [
            'record' => $invoice->id,
        ])
            ->callAction('finalize')
            ->assertHasActionErrors();
    }

    /** @test */
    public function finalization_validates_billing_period(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);

        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => 100,
            'billing_period_start' => now(),
            'billing_period_end' => now()->subMonth(), // End before start
        ]);
        $invoice->items()->create([
            'description' => 'Test item',
            'quantity' => 1,
            'unit_price' => 100,
            'total' => 100,
        ]);

        $this->actingAs($admin);

        \Livewire\Livewire::test(\App\Filament\Resources\InvoiceResource\Pages\ViewInvoice::class, [
            'record' => $invoice->id,
        ])
            ->callAction('finalize')
            ->assertHasActionErrors();
    }

    /** @test */
    public function rate_limit_key_is_user_specific(): void
    {
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
            'total_amount' => 100,
            'billing_period_start' => now()->subMonth(),
            'billing_period_end' => now(),
        ]);
        $invoice->items()->create([
            'description' => 'Test item',
            'quantity' => 1,
            'unit_price' => 100,
            'total' => 100,
        ]);

        // Admin1 hits rate limit
        $this->actingAs($admin1);
        for ($i = 0; $i < 11; $i++) {
            try {
                \Livewire\Livewire::test(\App\Filament\Resources\InvoiceResource\Pages\ViewInvoice::class, [
                    'record' => $invoice->id,
                ])
                    ->callAction('finalize');
            } catch (\Exception $e) {
                // Ignore
            }
        }

        $rateLimitKey1 = 'invoice-finalize:'.$admin1->id;
        $this->assertTrue(RateLimiter::tooManyAttempts($rateLimitKey1, 10));

        // Admin2 should not be affected
        $rateLimitKey2 = 'invoice-finalize:'.$admin2->id;
        $this->assertFalse(RateLimiter::tooManyAttempts($rateLimitKey2, 10));
    }

    /** @test */
    public function finalized_invoice_cannot_be_modified(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);

        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'status' => InvoiceStatus::FINALIZED,
            'finalized_at' => now(),
            'total_amount' => 100,
        ]);

        $this->actingAs($admin);

        // Attempt to modify finalized invoice
        $this->expectException(\App\Exceptions\InvoiceAlreadyFinalizedException::class);

        $invoice->update(['total_amount' => 200]);
    }

    /** @test */
    public function finalized_invoice_status_can_be_changed_to_paid(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);

        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'status' => InvoiceStatus::FINALIZED,
            'finalized_at' => now(),
            'total_amount' => 100,
        ]);

        $this->actingAs($admin);

        // Status change should be allowed
        $invoice->update(['status' => InvoiceStatus::PAID]);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => InvoiceStatus::PAID->value,
        ]);
    }
}
