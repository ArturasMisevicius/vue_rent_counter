<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Contracts\TenantContextInterface;
use App\Enums\UserRole;
use App\Exceptions\UnauthorizedTenantSwitchException;
use App\Models\Organization;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Integration tests for TenantContext service.
 * 
 * Tests the complete tenant context functionality with real session
 * storage, database interactions, and audit logging.
 */
final class TenantContextIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private TenantContextInterface $tenantContext;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantContext = app(TenantContextInterface::class);
    }

    public function test_service_is_bound_in_container(): void
    {
        $service = app(TenantContextInterface::class);

        $this->assertInstanceOf(TenantContext::class, $service);
    }

    public function test_session_persistence_works_correctly(): void
    {
        $organization = Organization::factory()->create();

        // Set tenant context
        $this->tenantContext->set($organization->id);

        // Verify it's stored in session
        $this->assertEquals($organization->id, Session::get('tenant_context'));

        // Verify we can retrieve it
        $this->assertEquals($organization->id, $this->tenantContext->get());
    }

    public function test_context_switching_for_superadmin(): void
    {
        $organization1 = Organization::factory()->create(['name' => 'Organization 1']);
        $organization2 = Organization::factory()->create(['name' => 'Organization 2']);
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);

        Log::fake();

        // Switch to first organization
        $this->tenantContext->switch($organization1->id, $superadmin);
        $this->assertEquals($organization1->id, $this->tenantContext->get());

        // Switch to second organization
        $this->tenantContext->switch($organization2->id, $superadmin);
        $this->assertEquals($organization2->id, $this->tenantContext->get());

        // Verify audit logging
        Log::assertLogged('info', function ($message, $context) use ($organization1) {
            return $message === 'Tenant context switched' &&
                   $context['new_tenant_id'] === $organization1->id &&
                   $context['organization_name'] === $organization1->name;
        });

        Log::assertLogged('info', function ($message, $context) use ($organization2) {
            return $message === 'Tenant context switched' &&
                   $context['new_tenant_id'] === $organization2->id &&
                   $context['organization_name'] === $organization2->name;
        });
    }

    public function test_validation_prevents_unauthorized_access(): void
    {
        $organization1 = Organization::factory()->create();
        $organization2 = Organization::factory()->create();
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => $organization1->id,
        ]);

        // Set context to organization2
        $this->tenantContext->set($organization2->id);

        // Admin should not be able to access organization2
        $this->assertFalse($this->tenantContext->validate($admin));

        // Set context to admin's own organization
        $this->tenantContext->set($organization1->id);

        // Admin should be able to access their own organization
        $this->assertTrue($this->tenantContext->validate($admin));
    }

    public function test_fallback_mechanism_for_non_superadmin(): void
    {
        $organization = Organization::factory()->create();
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => $organization->id,
        ]);

        // Admin cannot switch to different organization
        $otherOrganization = Organization::factory()->create();

        $this->expectException(UnauthorizedTenantSwitchException::class);
        $this->tenantContext->switch($otherOrganization->id, $admin);
    }

    public function test_initialization_sets_default_tenant(): void
    {
        $organization = Organization::factory()->create();
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => $organization->id,
        ]);

        Log::fake();

        // Initialize context for admin
        $this->tenantContext->initialize($admin);

        // Should set to admin's default tenant
        $this->assertEquals($organization->id, $this->tenantContext->get());

        // Verify logging
        Log::assertLogged('info', function ($message, $context) use ($organization) {
            return $message === 'Tenant context set' &&
                   $context['tenant_id'] === $organization->id;
        });
    }

    public function test_initialization_resets_invalid_context(): void
    {
        $validOrganization = Organization::factory()->create();
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => $validOrganization->id,
        ]);

        Log::fake();

        // Set invalid context manually
        Session::put('tenant_context', 999);

        // Initialize should reset to valid context
        $this->tenantContext->initialize($admin);

        // Should reset to admin's valid tenant
        $this->assertEquals($validOrganization->id, $this->tenantContext->get());

        // Verify warning was logged
        Log::assertLogged('warning', function ($message, $context) {
            return $message === 'Invalid tenant context reset' &&
                   $context['invalid_tenant_id'] === 999;
        });
    }

    public function test_superadmin_has_no_default_tenant(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);

        $defaultTenant = $this->tenantContext->getDefaultTenant($superadmin);

        $this->assertNull($defaultTenant);
    }

    public function test_clear_removes_context_and_logs(): void
    {
        $organization = Organization::factory()->create();

        Log::fake();

        // Set context
        $this->tenantContext->set($organization->id);
        $this->assertEquals($organization->id, $this->tenantContext->get());

        // Clear context
        $this->tenantContext->clear();
        $this->assertNull($this->tenantContext->get());

        // Verify logging
        Log::assertLogged('info', function ($message, $context) use ($organization) {
            return $message === 'Tenant context cleared' &&
                   $context['previous_tenant_id'] === $organization->id;
        });
    }

    public function test_can_switch_to_validates_organization_existence(): void
    {
        $organization = Organization::factory()->create();
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => $organization->id,
        ]);

        // Superadmin can switch to existing organization
        $this->assertTrue($this->tenantContext->canSwitchTo($organization->id, $superadmin));

        // Admin can switch to their own organization
        $this->assertTrue($this->tenantContext->canSwitchTo($organization->id, $admin));

        // Nobody can switch to non-existent organization
        $this->assertFalse($this->tenantContext->canSwitchTo(999, $superadmin));
        $this->assertFalse($this->tenantContext->canSwitchTo(999, $admin));
    }

    public function test_tenant_user_cannot_switch_context(): void
    {
        $organization = Organization::factory()->create();
        $tenant = User::factory()->create([
            'role' => UserRole::TENANT,
            'tenant_id' => $organization->id,
        ]);

        // Tenant cannot switch to any organization, even their own
        $this->assertFalse($this->tenantContext->canSwitchTo($organization->id, $tenant));

        // Attempting to switch should throw exception
        $this->expectException(UnauthorizedTenantSwitchException::class);
        $this->tenantContext->switch($organization->id, $tenant);
    }

    public function test_manager_can_access_own_tenant(): void
    {
        $organization = Organization::factory()->create();
        $manager = User::factory()->create([
            'role' => UserRole::MANAGER,
            'tenant_id' => $organization->id,
        ]);

        // Manager can switch to their own organization
        $this->assertTrue($this->tenantContext->canSwitchTo($organization->id, $manager));

        // Manager can switch to their own organization
        $this->tenantContext->switch($organization->id, $manager);
        $this->assertEquals($organization->id, $this->tenantContext->get());
    }

    public function test_audit_logging_captures_request_context(): void
    {
        $organization = Organization::factory()->create(['name' => 'Test Org']);
        $superadmin = User::factory()->create([
            'role' => UserRole::SUPERADMIN,
            'email' => 'admin@example.com',
        ]);

        Log::fake();

        // Simulate request context
        $this->withHeaders([
            'User-Agent' => 'Test Browser',
        ])->withServerVariables([
            'REMOTE_ADDR' => '192.168.1.1',
        ]);

        $this->tenantContext->switch($organization->id, $superadmin);

        // Verify audit log includes request context
        Log::assertLogged('info', function ($message, $context) use ($superadmin, $organization) {
            return $message === 'Tenant context switched' &&
                   $context['user_id'] === $superadmin->id &&
                   $context['user_email'] === $superadmin->email &&
                   $context['user_role'] === $superadmin->role->value &&
                   $context['new_tenant_id'] === $organization->id &&
                   $context['organization_name'] === $organization->name &&
                   isset($context['ip_address']) &&
                   isset($context['user_agent']) &&
                   isset($context['session_id']) &&
                   isset($context['timestamp']);
        });
    }

    public function test_error_handling_for_invalid_operations(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);

        // Test invalid tenant ID
        $this->expectException(InvalidArgumentException::class);
        $this->tenantContext->set(-1);
    }

    public function test_concurrent_session_isolation(): void
    {
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        // Simulate first session
        $this->tenantContext->set($org1->id);
        $this->assertEquals($org1->id, $this->tenantContext->get());

        // Start new session (simulated by clearing and starting fresh)
        Session::flush();
        Session::regenerate();

        // Second session should have no context
        $this->assertNull($this->tenantContext->get());

        // Set different context in second session
        $this->tenantContext->set($org2->id);
        $this->assertEquals($org2->id, $this->tenantContext->get());
    }

    public function test_context_persistence_across_requests(): void
    {
        $organization = Organization::factory()->create();

        // Set context in first "request"
        $this->tenantContext->set($organization->id);

        // Create new service instance (simulating new request)
        $newTenantContext = app(TenantContextInterface::class);

        // Context should persist
        $this->assertEquals($organization->id, $newTenantContext->get());
    }
}