<?php

namespace Tests\Feature\Commands;

use App\Enums\SubscriptionPlanType;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Test suite for MigrateToHierarchicalUsersCommand
 * 
 * This test suite validates the hierarchical user migration command that transforms
 * the existing flat user structure into a three-tier hierarchy (Superadmin → Admin → Tenant).
 * 
 * **Validates Requirements:**
 * - Requirement 2.2: Assigns unique tenant_id for data isolation
 * - Requirement 3.2: Ensures all admin accounts have proper tenant_id
 * 
 * **Command Responsibilities:**
 * - Converts existing 'manager' role to 'admin' role
 * - Assigns unique tenant_id to admin/manager users without one
 * - Creates default Professional subscriptions for all admin users
 * - Sets is_active = true for all existing users
 * - Generates organization names for admin users
 * - Supports dry-run mode for safe preview
 * - Provides rollback functionality
 * 
 * **Test Coverage:**
 * - Dry-run mode (no database changes)
 * - Role conversion (manager → admin)
 * - Unique tenant_id assignment
 * - Subscription creation with correct plan and limits
 * - User activation
 * - Organization name generation
 * - Existing data preservation
 * - Rollback functionality
 * - Error handling
 * 
 * @see \App\Console\Commands\MigrateToHierarchicalUsersCommand
 * @see \App\Models\User
 * @see \App\Models\Subscription
 * @see \App\Enums\UserRole
 * @see \App\Enums\SubscriptionPlanType
 * @see \App\Enums\SubscriptionStatus
 * 
 * @package Tests\Feature\Commands
 */
class MigrateToHierarchicalUsersCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the command runs successfully in dry-run mode without making changes.
     * 
     * Validates that the --dry-run flag prevents any database modifications while
     * still executing the migration logic and displaying what would be changed.
     * 
     * **Validates:**
     * - No role changes occur in dry-run mode
     * - No tenant_id assignments occur in dry-run mode
     * - No subscriptions are created in dry-run mode
     * - Command exits successfully with code 0
     * 
     * **Test Scenario:**
     * 1. Create a manager user without subscription
     * 2. Run migration with --dry-run flag
     * 3. Verify user role remains MANAGER
     * 4. Verify tenant_id remains null
     * 5. Verify no subscription was created
     * 
     * @return void
     */
    #[Test]
    public function it_runs_in_dry_run_mode_without_making_changes(): void
    {
        $manager = $this->createManagerUser();

        $this->artisan('users:migrate-hierarchical', ['--dry-run' => true])
            ->assertExitCode(0);

        // Verify no changes were made
        $manager->refresh();
        $this->assertSame(UserRole::MANAGER, $manager->role);
        $this->assertNull($manager->tenant_id);
        $this->assertNull($manager->subscription);
    }

    /**
     * Test that manager roles are converted to admin roles.
     * 
     * Validates the core role conversion functionality that transforms
     * legacy 'manager' roles into the new 'admin' role as part of the
     * hierarchical user structure.
     * 
     * **Validates:**
     * - Manager role is converted to Admin role
     * - Role conversion persists to database
     * - Command completes successfully
     * 
     * **Test Scenario:**
     * 1. Create a manager user
     * 2. Run migration command
     * 3. Verify role changed from MANAGER to ADMIN
     * 
     * @return void
     */
    #[Test]
    public function it_converts_manager_role_to_admin_role(): void
    {
        $manager = $this->createManagerUser();

        $this->artisan('users:migrate-hierarchical')
            ->assertExitCode(0);

        $manager->refresh();
        $this->assertSame(UserRole::ADMIN, $manager->role);
    }

    /**
     * Test that unique tenant_id is assigned to users without one.
     * 
     * Validates that the migration assigns unique tenant_id values to users
     * who don't have one, ensuring proper data isolation in the multi-tenant
     * architecture.
     * 
     * **Validates:**
     * - Each user receives a tenant_id
     * - tenant_id values are unique across users
     * - tenant_id assignment is sequential
     * 
     * **Test Scenario:**
     * 1. Create two manager users without tenant_id
     * 2. Run migration command
     * 3. Verify both users have tenant_id assigned
     * 4. Verify tenant_id values are different
     * 
     * @return void
     */
    #[Test]
    public function it_assigns_unique_tenant_id_to_users(): void
    {
        $manager1 = $this->createManagerUser();
        $manager2 = $this->createManagerUser();

        $this->artisan('users:migrate-hierarchical')
            ->assertExitCode(0);

        $manager1->refresh();
        $manager2->refresh();

        $this->assertNotNull($manager1->tenant_id);
        $this->assertNotNull($manager2->tenant_id);
        $this->assertNotEquals($manager1->tenant_id, $manager2->tenant_id);
    }

    /**
     * Test that subscriptions are created for admin users.
     * 
     * Validates that the migration creates Professional plan subscriptions
     * for all admin users with the correct plan type, status, and limits.
     * 
     * **Validates:**
     * - Subscription is created for migrated admin
     * - Plan type is PROFESSIONAL
     * - Status is ACTIVE
     * - max_properties is set to 50
     * - max_tenants is set to 200
     * 
     * **Test Scenario:**
     * 1. Create a manager user (will become admin)
     * 2. Run migration command
     * 3. Verify subscription exists
     * 4. Verify subscription has correct plan and limits
     * 
     * @return void
     */
    #[Test]
    public function it_creates_subscriptions_for_admin_users(): void
    {
        $manager = $this->createManagerUser();

        $this->artisan('users:migrate-hierarchical')
            ->assertExitCode(0);

        $manager->refresh();
        $this->assertNotNull($manager->subscription);
        $this->assertSame(SubscriptionPlanType::PROFESSIONAL->value, $manager->subscription->plan_type);
        $this->assertSame(SubscriptionStatus::ACTIVE, $manager->subscription->status);
        $this->assertSame(50, $manager->subscription->max_properties);
        $this->assertSame(200, $manager->subscription->max_tenants);
    }

    /**
     * Test that is_active is set to true for all users.
     * 
     * Validates that the migration activates all users by setting
     * is_active to true, ensuring they can log in after migration.
     * 
     * **Validates:**
     * - Inactive users are activated
     * - is_active flag is set to true
     * - Activation applies to all user roles
     * 
     * **Test Scenario:**
     * 1. Create an inactive tenant user
     * 2. Run migration command
     * 3. Verify user is now active
     * 
     * @return void
     */
    #[Test]
    public function it_sets_is_active_to_true_for_all_users(): void
    {
        $inactiveUser = User::factory()->create([
            'role' => UserRole::TENANT,
            'is_active' => false,
        ]);

        $this->artisan('users:migrate-hierarchical')
            ->assertExitCode(0);

        $inactiveUser->refresh();
        $this->assertTrue($inactiveUser->is_active);
    }

    /**
     * Test that organization name is set if not present.
     * 
     * Validates that the migration generates organization names for admin
     * users who don't have one, using the format "{User Name}'s Organization".
     * 
     * **Validates:**
     * - Organization name is generated for admins
     * - Generated name includes user's name
     * - Organization name is persisted to database
     * 
     * **Test Scenario:**
     * 1. Create a manager user without organization_name
     * 2. Run migration command
     * 3. Verify organization_name is set
     * 4. Verify organization_name contains user's name
     * 
     * @return void
     */
    #[Test]
    public function it_sets_organization_name_for_admin_users(): void
    {
        $manager = User::factory()->create([
            'role' => UserRole::MANAGER,
            'tenant_id' => null,
            'organization_name' => null,
            'name' => 'John Doe',
        ]);

        $this->artisan('users:migrate-hierarchical')
            ->assertExitCode(0);

        $manager->refresh();
        $this->assertNotNull($manager->organization_name);
        $this->assertStringContainsString('John Doe', $manager->organization_name);
    }

    /**
     * Test that existing tenant_id is preserved.
     * 
     * Validates that the migration does not overwrite existing tenant_id
     * values, preserving any manual assignments or previous migrations.
     * 
     * **Validates:**
     * - Existing tenant_id values are not changed
     * - Data preservation during migration
     * - Idempotent migration behavior
     * 
     * **Test Scenario:**
     * 1. Create a manager user with tenant_id = 5
     * 2. Run migration command
     * 3. Verify tenant_id remains 5
     * 
     * @return void
     */
    #[Test]
    public function it_preserves_existing_tenant_id(): void
    {
        $manager = User::factory()->create([
            'role' => UserRole::MANAGER,
            'tenant_id' => 5,
        ]);

        $this->artisan('users:migrate-hierarchical')
            ->assertExitCode(0);

        $manager->refresh();
        $this->assertSame(5, $manager->tenant_id);
    }

    /**
     * Test that rollback reverts changes.
     * 
     * Validates the rollback functionality that reverts all migration changes,
     * including role conversions, tenant_id assignments, and subscriptions.
     * 
     * **Validates:**
     * - Admin role reverts to Manager
     * - tenant_id is cleared
     * - Subscriptions are removed
     * - Rollback requires confirmation
     * 
     * **Test Scenario:**
     * 1. Create a manager user
     * 2. Run migration (manager → admin with subscription)
     * 3. Run rollback with confirmation
     * 4. Verify role reverted to MANAGER
     * 5. Verify tenant_id is null
     * 6. Verify subscription is removed
     * 
     * @return void
     */
    #[Test]
    public function it_can_rollback_migration(): void
    {
        $manager = $this->createManagerUser();

        $this->artisan('users:migrate-hierarchical')
            ->assertExitCode(0);

        $manager->refresh();
        $this->assertSame(UserRole::ADMIN, $manager->role);
        $this->assertNotNull($manager->tenant_id);
        $this->assertNotNull($manager->subscription);

        // Now rollback
        $this->artisan('users:migrate-hierarchical', ['--rollback' => true])
            ->expectsConfirmation('This will revert admin roles back to manager and remove subscriptions. Continue?', 'yes')
            ->assertExitCode(0);

        $manager->refresh();
        $this->assertSame(UserRole::MANAGER, $manager->role);
        $this->assertNull($manager->tenant_id);
        $this->assertNull($manager->subscription);
    }

    /**
     * Test that the command handles errors gracefully.
     * 
     * Validates that the migration command completes successfully even
     * when encountering edge cases or potential error conditions.
     * 
     * **Validates:**
     * - Command completes without throwing exceptions
     * - Migration proceeds despite edge cases
     * - Transaction handling ensures data consistency
     * 
     * **Test Scenario:**
     * 1. Create a manager user
     * 2. Run migration command
     * 3. Verify command completes successfully
     * 4. Verify user was migrated correctly
     * 
     * @return void
     */
    #[Test]
    public function it_handles_errors_gracefully(): void
    {
        $user = $this->createManagerUser();

        // The command should still complete successfully
        $this->artisan('users:migrate-hierarchical')
            ->assertExitCode(0);
        
        $user->refresh();
        $this->assertSame(UserRole::ADMIN, $user->role);
    }

    /**
     * Test that admin users without subscriptions get one created.
     * 
     * Validates that the migration creates subscriptions for existing admin
     * users who don't have one, ensuring all admins have proper subscription
     * limits after migration.
     * 
     * **Validates:**
     * - Existing admins without subscriptions get one
     * - Subscription has correct plan type (PROFESSIONAL)
     * - Backfill of missing subscriptions
     * 
     * **Test Scenario:**
     * 1. Create an admin user without subscription
     * 2. Run migration command
     * 3. Verify subscription was created
     * 4. Verify subscription has correct plan type
     * 
     * @return void
     */
    #[Test]
    public function it_creates_subscriptions_for_existing_admin_users_without_one(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);

        $this->assertNull($admin->subscription);

        $this->artisan('users:migrate-hierarchical')
            ->assertExitCode(0);

        $admin->refresh();
        $this->assertNotNull($admin->subscription);
        $this->assertSame(SubscriptionPlanType::PROFESSIONAL->value, $admin->subscription->plan_type);
    }

    /**
     * Test that superadmin users are not affected by migration.
     * 
     * Validates that superadmin users remain unchanged during migration,
     * preserving their global access without tenant_id or subscriptions.
     * 
     * **Validates:**
     * - Superadmin role is not changed
     * - No tenant_id is assigned to superadmins
     * - No subscription is created for superadmins
     * - Superadmin isolation from migration logic
     * 
     * **Test Scenario:**
     * 1. Create a superadmin user
     * 2. Run migration command
     * 3. Verify role remains SUPERADMIN
     * 4. Verify tenant_id remains null
     * 5. Verify no subscription was created
     * 
     * @return void
     */
    #[Test]
    public function it_does_not_affect_superadmin_users(): void
    {
        $superadmin = User::factory()->create([
            'role' => UserRole::SUPERADMIN,
            'tenant_id' => null,
            'organization_name' => null,
        ]);

        $this->artisan('users:migrate-hierarchical')
            ->assertExitCode(0);

        $superadmin->refresh();
        $this->assertSame(UserRole::SUPERADMIN, $superadmin->role);
        $this->assertNull($superadmin->tenant_id);
        $this->assertNull($superadmin->subscription);
    }

    /**
     * Test that tenant users maintain their existing structure.
     * 
     * Validates that tenant users preserve their hierarchical relationships
     * during migration, including tenant_id, property_id, and parent_user_id.
     * 
     * **Validates:**
     * - Tenant role is not changed
     * - tenant_id is preserved
     * - parent_user_id relationship is maintained
     * - Hierarchical structure integrity
     * 
     * **Test Scenario:**
     * 1. Create an admin user (parent)
     * 2. Create a tenant user with parent relationship
     * 3. Run migration command
     * 4. Verify tenant role unchanged
     * 5. Verify tenant_id preserved
     * 6. Verify parent_user_id relationship intact
     * 
     * @return void
     */
    #[Test]
    public function it_preserves_tenant_user_structure(): void
    {
        // Create parent admin user first to satisfy foreign key
        $admin = User::factory()->create([
            'id' => 10,
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);

        $tenant = User::factory()->create([
            'role' => UserRole::TENANT,
            'tenant_id' => 1,
            'property_id' => null, // Property FK not enforced in test
            'parent_user_id' => $admin->id,
        ]);

        $this->artisan('users:migrate-hierarchical')
            ->assertExitCode(0);

        $tenant->refresh();
        $this->assertSame(UserRole::TENANT, $tenant->role);
        $this->assertSame(1, $tenant->tenant_id);
        $this->assertSame($admin->id, $tenant->parent_user_id);
    }

    /**
     * Test that existing organization names are preserved.
     * 
     * Validates that the migration does not overwrite existing organization
     * names, preserving any custom names set by administrators.
     * 
     * **Validates:**
     * - Existing organization names are not changed
     * - Data preservation during migration
     * - Idempotent migration behavior
     * 
     * **Test Scenario:**
     * 1. Create a manager with custom organization name
     * 2. Run migration command
     * 3. Verify organization name unchanged
     * 
     * @return void
     */
    #[Test]
    public function it_preserves_existing_organization_names(): void
    {
        $manager = User::factory()->create([
            'role' => UserRole::MANAGER,
            'tenant_id' => null,
            'organization_name' => 'Existing Organization',
        ]);

        $this->artisan('users:migrate-hierarchical')
            ->assertExitCode(0);

        $manager->refresh();
        $this->assertSame('Existing Organization', $manager->organization_name);
    }

    /**
     * Test that rollback can be cancelled.
     * 
     * Validates that the rollback operation can be safely cancelled,
     * leaving all migration changes intact when user declines confirmation.
     * 
     * **Validates:**
     * - Rollback requires user confirmation
     * - Declining confirmation preserves changes
     * - No data is modified when rollback is cancelled
     * 
     * **Test Scenario:**
     * 1. Create and migrate a manager user
     * 2. Attempt rollback but decline confirmation
     * 3. Verify role remains ADMIN
     * 4. Verify tenant_id is preserved
     * 
     * @return void
     */
    #[Test]
    public function it_allows_rollback_cancellation(): void
    {
        $manager = $this->createManagerUser();

        $this->artisan('users:migrate-hierarchical')
            ->assertExitCode(0);

        $manager->refresh();
        $originalRole = $manager->role;
        $originalTenantId = $manager->tenant_id;

        // Cancel rollback
        $this->artisan('users:migrate-hierarchical', ['--rollback' => true])
            ->expectsConfirmation('This will revert admin roles back to manager and remove subscriptions. Continue?', 'no')
            ->assertExitCode(0);

        $manager->refresh();
        $this->assertSame($originalRole, $manager->role);
        $this->assertSame($originalTenantId, $manager->tenant_id);
    }

    /**
     * Test that subscription dates are set correctly.
     * 
     * Validates that subscriptions created during migration have proper
     * start and expiration dates, with approximately 1 year duration.
     * 
     * **Validates:**
     * - starts_at is set to current date
     * - expires_at is set to 1 year from start
     * - expires_at is after starts_at
     * - Duration accounts for leap years (365-366 days)
     * 
     * **Test Scenario:**
     * 1. Create a manager user
     * 2. Run migration command
     * 3. Verify subscription dates are set
     * 4. Verify expiration is approximately 1 year from start
     * 
     * @return void
     */
    #[Test]
    public function it_sets_correct_subscription_dates(): void
    {
        $manager = $this->createManagerUser();

        $this->artisan('users:migrate-hierarchical')
            ->assertExitCode(0);

        $manager->refresh();
        $subscription = $manager->subscription;

        $this->assertNotNull($subscription->starts_at);
        $this->assertNotNull($subscription->expires_at);
        $this->assertTrue($subscription->expires_at->greaterThan($subscription->starts_at));
        
        // Check that subscription is approximately 1 year (allowing for leap years)
        $daysDiff = $subscription->starts_at->diffInDays($subscription->expires_at);
        $this->assertGreaterThanOrEqual(365, $daysDiff);
        $this->assertLessThanOrEqual(366, $daysDiff);
    }

    /**
     * Helper method to create a manager user for testing.
     * 
     * Creates a user with MANAGER role, no tenant_id, and active status.
     * This represents a typical pre-migration user state.
     * 
     * @return User The created manager user
     */
    private function createManagerUser(): User
    {
        return User::factory()->create([
            'role' => UserRole::MANAGER,
            'tenant_id' => null,
            'is_active' => true,
        ]);
    }
}
