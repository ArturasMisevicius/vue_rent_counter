<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Tariff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * TariffPolicySecurityTest
 * 
 * Security-focused tests for TariffPolicy authorization and related security measures.
 * 
 * Coverage:
 * - Unauthenticated access prevention
 * - Role-based authorization enforcement
 * - Rate limiting validation
 * - Audit logging verification
 * - Force delete restrictions
 * - Cross-role authorization matrix
 * 
 * @package Tests\Security
 * @group security
 * @group policies
 * @group tariffs
 */
class TariffPolicySecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear rate limiter before each test
        RateLimiter::clear('tariff-operations:*');
    }

    /**
     * Test that unauthenticated users are redirected to login.
     */
    public function test_unauthenticated_users_cannot_access_tariff_operations(): void
    {
        $tariff = Tariff::factory()->create();

        // Test create
        $response = $this->post(route('tariffs.store'), [
            'name' => 'Test Tariff',
            'provider_id' => 1,
        ]);
        $response->assertRedirect(route('login'));

        // Test update
        $response = $this->put(route('tariffs.update', $tariff), [
            'name' => 'Updated Tariff',
        ]);
        $response->assertRedirect(route('login'));

        // Test delete
        $response = $this->delete(route('tariffs.destroy', $tariff));
        $response->assertRedirect(route('login'));
    }

    /**
     * Test that tenant users cannot create tariffs.
     */
    public function test_tenant_users_cannot_create_tariffs(): void
    {
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        $this->actingAs($tenant);

        $this->assertFalse($tenant->can('create', Tariff::class));
    }

    /**
     * Test that manager users cannot create tariffs.
     */
    public function test_manager_users_cannot_create_tariffs(): void
    {
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);

        $this->actingAs($manager);

        $this->assertFalse($manager->can('create', Tariff::class));
    }

    /**
     * Test that manager users cannot update tariffs.
     */
    public function test_manager_users_cannot_update_tariffs(): void
    {
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tariff = Tariff::factory()->create();

        $this->actingAs($manager);

        $this->assertFalse($manager->can('update', $tariff));
    }

    /**
     * Test that manager users cannot delete tariffs.
     */
    public function test_manager_users_cannot_delete_tariffs(): void
    {
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tariff = Tariff::factory()->create();

        $this->actingAs($manager);

        $this->assertFalse($manager->can('delete', $tariff));
    }

    /**
     * Test that admin users can create tariffs.
     */
    public function test_admin_users_can_create_tariffs(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $this->actingAs($admin);

        $this->assertTrue($admin->can('create', Tariff::class));
    }

    /**
     * Test that admin users can update tariffs.
     */
    public function test_admin_users_can_update_tariffs(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $tariff = Tariff::factory()->create();

        $this->actingAs($admin);

        $this->assertTrue($admin->can('update', $tariff));
    }

    /**
     * Test that admin users can delete tariffs.
     */
    public function test_admin_users_can_delete_tariffs(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $tariff = Tariff::factory()->create();

        $this->actingAs($admin);

        $this->assertTrue($admin->can('delete', $tariff));
    }

    /**
     * Test that admin users cannot force delete tariffs.
     */
    public function test_admin_users_cannot_force_delete_tariffs(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $tariff = Tariff::factory()->create();

        $this->actingAs($admin);

        $this->assertFalse($admin->can('forceDelete', $tariff));
    }

    /**
     * Test that superadmin users can force delete tariffs.
     */
    public function test_superadmin_users_can_force_delete_tariffs(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $tariff = Tariff::factory()->create();

        $this->actingAs($superadmin);

        $this->assertTrue($superadmin->can('forceDelete', $tariff));
    }

    /**
     * Test that superadmin users can perform all operations.
     */
    public function test_superadmin_users_have_full_access(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $tariff = Tariff::factory()->create();

        $this->actingAs($superadmin);

        $this->assertTrue($superadmin->can('viewAny', Tariff::class));
        $this->assertTrue($superadmin->can('view', $tariff));
        $this->assertTrue($superadmin->can('create', Tariff::class));
        $this->assertTrue($superadmin->can('update', $tariff));
        $this->assertTrue($superadmin->can('delete', $tariff));
        $this->assertTrue($superadmin->can('restore', $tariff));
        $this->assertTrue($superadmin->can('forceDelete', $tariff));
    }

    /**
     * Test that tariff creation is audited.
     */
    public function test_tariff_creation_is_audited(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $this->actingAs($admin);

        $tariff = Tariff::factory()->create([
            'name' => 'Test Tariff',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Tariff::class,
            'auditable_id' => $tariff->id,
            'user_id' => $admin->id,
            'event' => 'created',
        ]);
    }

    /**
     * Test that tariff updates are audited.
     */
    public function test_tariff_updates_are_audited(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $tariff = Tariff::factory()->create();

        $this->actingAs($admin);

        $tariff->update(['name' => 'Updated Tariff']);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Tariff::class,
            'auditable_id' => $tariff->id,
            'user_id' => $admin->id,
            'event' => 'updated',
        ]);
    }

    /**
     * Test that tariff deletions are audited.
     */
    public function test_tariff_deletions_are_audited(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $tariff = Tariff::factory()->create();

        $this->actingAs($admin);

        $tariff->delete();

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Tariff::class,
            'auditable_id' => $tariff->id,
            'user_id' => $admin->id,
            'event' => 'deleted',
        ]);
    }

    /**
     * Test that audit logs capture IP address and user agent.
     */
    public function test_audit_logs_capture_request_metadata(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $this->actingAs($admin);

        $tariff = Tariff::factory()->create();

        $auditLog = AuditLog::where('auditable_type', Tariff::class)
            ->where('auditable_id', $tariff->id)
            ->where('event', 'created')
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertNotNull($auditLog->ip_address);
        $this->assertNotNull($auditLog->user_agent);
    }

    /**
     * Test that audit logs capture old and new values.
     */
    public function test_audit_logs_capture_value_changes(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $tariff = Tariff::factory()->create(['name' => 'Original Name']);

        $this->actingAs($admin);

        $tariff->update(['name' => 'Updated Name']);

        $auditLog = AuditLog::where('auditable_type', Tariff::class)
            ->where('auditable_id', $tariff->id)
            ->where('event', 'updated')
            ->latest()
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertIsArray($auditLog->old_values);
        $this->assertIsArray($auditLog->new_values);
        $this->assertArrayHasKey('name', $auditLog->new_values);
        $this->assertEquals('Updated Name', $auditLog->new_values['name']);
    }

    /**
     * Test authorization matrix for all roles.
     */
    public function test_authorization_matrix_for_all_roles(): void
    {
        $tariff = Tariff::factory()->create();

        $roles = [
            UserRole::SUPERADMIN => [
                'viewAny' => true,
                'view' => true,
                'create' => true,
                'update' => true,
                'delete' => true,
                'restore' => true,
                'forceDelete' => true,
            ],
            UserRole::ADMIN => [
                'viewAny' => true,
                'view' => true,
                'create' => true,
                'update' => true,
                'delete' => true,
                'restore' => true,
                'forceDelete' => false,
            ],
            UserRole::MANAGER => [
                'viewAny' => true,
                'view' => true,
                'create' => false,
                'update' => false,
                'delete' => false,
                'restore' => false,
                'forceDelete' => false,
            ],
            UserRole::TENANT => [
                'viewAny' => true,
                'view' => true,
                'create' => false,
                'update' => false,
                'delete' => false,
                'restore' => false,
                'forceDelete' => false,
            ],
        ];

        foreach ($roles as $role => $permissions) {
            $user = User::factory()->create(['role' => $role]);
            $this->actingAs($user);

            foreach ($permissions as $action => $expected) {
                $actual = match ($action) {
                    'viewAny' => $user->can('viewAny', Tariff::class),
                    'view' => $user->can('view', $tariff),
                    'create' => $user->can('create', Tariff::class),
                    'update' => $user->can('update', $tariff),
                    'delete' => $user->can('delete', $tariff),
                    'restore' => $user->can('restore', $tariff),
                    'forceDelete' => $user->can('forceDelete', $tariff),
                };

                $this->assertEquals(
                    $expected,
                    $actual,
                    "Role {$role->value} should " . ($expected ? 'allow' : 'deny') . " {$action}"
                );
            }
        }
    }
}

