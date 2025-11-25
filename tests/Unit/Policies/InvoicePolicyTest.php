<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\User;
use App\Policies\InvoicePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * InvoicePolicyTest
 * 
 * Tests authorization rules for invoice operations.
 * 
 * Requirements:
 * - 11.1: Verify user's role using Laravel Policies
 * - 11.3: Manager can create and view invoices
 * - 11.4: Tenant can only view their own invoices
 * - 7.3: Cross-tenant access prevention
 */
class InvoicePolicyTest extends TestCase
{
    use RefreshDatabase;

    private InvoicePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new InvoicePolicy();
    }

    /**
     * Test that all roles can view invoices list.
     * 
     * Requirements: 11.1, 11.4
     */
    public function test_all_roles_can_view_any_invoices(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        $this->assertTrue($this->policy->viewAny($superadmin));
        $this->assertTrue($this->policy->viewAny($admin));
        $this->assertTrue($this->policy->viewAny($manager));
        $this->assertTrue($this->policy->viewAny($tenant));
    }

    /**
     * Test that managers can view invoices within their tenant.
     * 
     * Requirements: 11.3, 7.3
     */
    public function test_managers_can_view_invoices_within_tenant(): void
    {
        $tenantId = 1;
        $manager = User::factory()->create([
            'role' => UserRole::MANAGER,
            'tenant_id' => $tenantId,
        ]);

        $invoice = Invoice::factory()->create(['tenant_id' => $tenantId]);
        $otherInvoice = Invoice::factory()->create(['tenant_id' => 2]);

        $this->assertTrue($this->policy->view($manager, $invoice));
        $this->assertFalse($this->policy->view($manager, $otherInvoice)); // Requirement 7.3
    }

    /**
     * Test that tenants can only view their own invoices.
     * 
     * Requirements: 11.4
     */
    public function test_tenants_can_only_view_own_invoices(): void
    {
        $tenantId = 1;
        $email = 'tenant@example.com';
        
        $tenantRecord = Tenant::factory()->create([
            'tenant_id' => $tenantId,
            'email' => $email,
        ]);
        
        $user = User::factory()->create([
            'role' => UserRole::TENANT,
            'tenant_id' => $tenantId,
            'email' => $email,
        ]);

        $ownInvoice = Invoice::factory()->create([
            'tenant_id' => $tenantId,
            'tenant_renter_id' => $tenantRecord->id,
        ]);
        $otherInvoice = Invoice::factory()->create([
            'tenant_id' => $tenantId,
            'tenant_renter_id' => 999,
        ]);

        $this->assertTrue($this->policy->view($user, $ownInvoice));
        $this->assertFalse($this->policy->view($user, $otherInvoice)); // Requirement 11.4
    }

    /**
     * Test that admins and managers can create invoices.
     * 
     * Requirements: 11.3
     */
    public function test_admins_and_managers_can_create_invoices(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        $this->assertTrue($this->policy->create($superadmin));
        $this->assertTrue($this->policy->create($admin));
        $this->assertTrue($this->policy->create($manager)); // Requirement 11.3
        $this->assertFalse($this->policy->create($tenant));
    }

    /**
     * Test that admins and managers can finalize invoices.
     * 
     * Requirements: 11.3
     */
    public function test_admins_and_managers_can_finalize_invoices(): void
    {
        $tenantId = 1;
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => $tenantId,
        ]);
        $manager = User::factory()->create([
            'role' => UserRole::MANAGER,
            'tenant_id' => $tenantId,
        ]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        $draftInvoice = Invoice::factory()->create([
            'tenant_id' => $tenantId,
            'status' => InvoiceStatus::DRAFT,
        ]);

        $this->assertTrue($this->policy->finalize($admin, $draftInvoice));
        $this->assertTrue($this->policy->finalize($manager, $draftInvoice)); // Requirement 11.3
        $this->assertFalse($this->policy->finalize($tenant, $draftInvoice));
    }

    /**
     * Test that finalized invoices cannot be finalized again.
     * 
     * Requirements: 11.1
     */
    public function test_finalized_invoices_cannot_be_finalized_again(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $finalizedInvoice = Invoice::factory()->create([
            'status' => InvoiceStatus::FINALIZED,
        ]);

        $this->assertFalse($this->policy->finalize($admin, $finalizedInvoice));
    }

    /**
     * Test cross-tenant access prevention.
     * 
     * Requirements: 7.3
     */
    public function test_cross_tenant_access_prevention(): void
    {
        $manager = User::factory()->create([
            'role' => UserRole::MANAGER,
            'tenant_id' => 1,
        ]);

        $otherTenantInvoice = Invoice::factory()->create(['tenant_id' => 2]);

        $this->assertFalse($this->policy->view($manager, $otherTenantInvoice));
        $this->assertFalse($this->policy->update($manager, $otherTenantInvoice));
        $this->assertFalse($this->policy->finalize($manager, $otherTenantInvoice));
    }
}
