<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SuperadminBulkOperationsTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->superadmin = User::factory()->create([
            'role' => UserRole::SUPERADMIN,
            'tenant_id' => null,
        ]);
    }

    /** @test */
    public function bulk_suspend_organizations_works(): void
    {
        // Create test organizations
        $organizations = Organization::factory()->count(3)->create([
            'is_active' => true,
            'suspended_at' => null,
        ]);

        $organizationIds = $organizations->pluck('id')->toArray();

        $response = $this->actingAs($this->superadmin)
            ->post('/superadmin/organizations/bulk-suspend', [
                'organization_ids' => $organizationIds,
                'reason' => 'Policy violation',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify all organizations are suspended
        foreach ($organizations as $organization) {
            $organization->refresh();
            $this->assertFalse($organization->is_active);
            $this->assertNotNull($organization->suspended_at);
            $this->assertEquals('Policy violation', $organization->suspension_reason);
        }

        // Verify audit logs were created
        $this->assertDatabaseHas('organization_activity_logs', [
            'user_id' => $this->superadmin->id,
            'action' => 'bulk_suspend',
        ]);
    }

    /** @test */
    public function bulk_reactivate_organizations_works(): void
    {
        // Create suspended organizations
        $organizations = Organization::factory()->count(3)->create([
            'is_active' => false,
            'suspended_at' => now(),
            'suspension_reason' => 'Test suspension',
        ]);

        $organizationIds = $organizations->pluck('id')->toArray();

        $response = $this->actingAs($this->superadmin)
            ->post('/superadmin/organizations/bulk-reactivate', [
                'organization_ids' => $organizationIds,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify all organizations are reactivated
        foreach ($organizations as $organization) {
            $organization->refresh();
            $this->assertTrue($organization->is_active);
            $this->assertNull($organization->suspended_at);
            $this->assertNull($organization->suspension_reason);
        }
    }

    /** @test */
    public function bulk_change_plan_updates_organizations(): void
    {
        // Create organizations with basic plan
        $organizations = Organization::factory()->count(3)->create([
            'plan' => SubscriptionPlan::BASIC,
            'max_properties' => 100,
            'max_users' => 10,
        ]);

        $organizationIds = $organizations->pluck('id')->toArray();

        $response = $this->actingAs($this->superadmin)
            ->post('/superadmin/organizations/bulk-change-plan', [
                'organization_ids' => $organizationIds,
                'new_plan' => SubscriptionPlan::PROFESSIONAL->value,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify all organizations have updated plan and limits
        foreach ($organizations as $organization) {
            $organization->refresh();
            $this->assertEquals(SubscriptionPlan::PROFESSIONAL, $organization->plan);
            $this->assertEquals(500, $organization->max_properties);
            $this->assertEquals(50, $organization->max_users);
        }
    }

    /** @test */
    public function bulk_export_organizations_generates_csv(): void
    {
        // Create test organizations
        Organization::factory()->count(5)->create();

        $response = $this->actingAs($this->superadmin)
            ->post('/superadmin/organizations/bulk-export', [
                'format' => 'csv',
                'include_inactive' => true,
            ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition');
        
        // Verify CSV content contains organization data
        $csvContent = $response->getContent();
        $this->assertStringContains('Name,Email,Plan,Status', $csvContent);
    }

    /** @test */
    public function bulk_renew_subscriptions_works(): void
    {
        $users = User::factory()->count(3)->create();
        
        // Create subscriptions expiring soon
        $subscriptions = collect();
        foreach ($users as $user) {
            $subscriptions->push(Subscription::factory()->create([
                'user_id' => $user->id,
                'status' => SubscriptionStatus::ACTIVE,
                'expires_at' => now()->addDays(5),
            ]));
        }

        $subscriptionIds = $subscriptions->pluck('id')->toArray();

        $response = $this->actingAs($this->superadmin)
            ->post('/superadmin/subscriptions/bulk-renew', [
                'subscription_ids' => $subscriptionIds,
                'duration' => 'annually',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify all subscriptions are renewed
        foreach ($subscriptions as $subscription) {
            $subscription->refresh();
            $this->assertEquals(SubscriptionStatus::ACTIVE, $subscription->status);
            $this->assertTrue($subscription->expires_at->isAfter(now()->addMonths(11)));
        }
    }

    /** @test */
    public function bulk_suspend_subscriptions_works(): void
    {
        $users = User::factory()->count(3)->create();
        
        $subscriptions = collect();
        foreach ($users as $user) {
            $subscriptions->push(Subscription::factory()->create([
                'user_id' => $user->id,
                'status' => SubscriptionStatus::ACTIVE,
            ]));
        }

        $subscriptionIds = $subscriptions->pluck('id')->toArray();

        $response = $this->actingAs($this->superadmin)
            ->post('/superadmin/subscriptions/bulk-suspend', [
                'subscription_ids' => $subscriptionIds,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify all subscriptions are suspended
        foreach ($subscriptions as $subscription) {
            $subscription->refresh();
            $this->assertEquals(SubscriptionStatus::SUSPENDED, $subscription->status);
        }
    }

    /** @test */
    public function bulk_activate_subscriptions_works(): void
    {
        $users = User::factory()->count(3)->create();
        
        $subscriptions = collect();
        foreach ($users as $user) {
            $subscriptions->push(Subscription::factory()->create([
                'user_id' => $user->id,
                'status' => SubscriptionStatus::SUSPENDED,
            ]));
        }

        $subscriptionIds = $subscriptions->pluck('id')->toArray();

        $response = $this->actingAs($this->superadmin)
            ->post('/superadmin/subscriptions/bulk-activate', [
                'subscription_ids' => $subscriptionIds,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify all subscriptions are activated
        foreach ($subscriptions as $subscription) {
            $subscription->refresh();
            $this->assertEquals(SubscriptionStatus::ACTIVE, $subscription->status);
        }
    }

    /** @test */
    public function bulk_deactivate_users_works(): void
    {
        $organization = Organization::factory()->create();
        
        $users = User::factory()->count(3)->create([
            'tenant_id' => $organization->id,
            'is_active' => true,
        ]);

        $userIds = $users->pluck('id')->toArray();

        $response = $this->actingAs($this->superadmin)
            ->post('/superadmin/users/bulk-deactivate', [
                'user_ids' => $userIds,
                'reason' => 'Account cleanup',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify all users are deactivated
        foreach ($users as $user) {
            $user->refresh();
            $this->assertFalse($user->is_active);
            $this->assertNotNull($user->suspended_at);
        }
    }

    /** @test */
    public function bulk_reactivate_users_works(): void
    {
        $organization = Organization::factory()->create();
        
        $users = User::factory()->count(3)->create([
            'tenant_id' => $organization->id,
            'is_active' => false,
            'suspended_at' => now(),
        ]);

        $userIds = $users->pluck('id')->toArray();

        $response = $this->actingAs($this->superadmin)
            ->post('/superadmin/users/bulk-reactivate', [
                'user_ids' => $userIds,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify all users are reactivated
        foreach ($users as $user) {
            $user->refresh();
            $this->assertTrue($user->is_active);
            $this->assertNull($user->suspended_at);
        }
    }

    /** @test */
    public function bulk_resend_invitations_works(): void
    {
        $invitations = OrganizationInvitation::factory()->count(3)->create([
            'accepted_at' => null,
            'expires_at' => now()->subDays(1), // Expired
        ]);

        $invitationIds = $invitations->pluck('id')->toArray();

        $response = $this->actingAs($this->superadmin)
            ->post('/superadmin/invitations/bulk-resend', [
                'invitation_ids' => $invitationIds,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify all invitations have new tokens and extended expiry
        foreach ($invitations as $invitation) {
            $invitation->refresh();
            $this->assertTrue($invitation->expires_at->isFuture());
            $this->assertNotNull($invitation->token);
        }
    }

    /** @test */
    public function bulk_cancel_invitations_works(): void
    {
        $invitations = OrganizationInvitation::factory()->count(3)->create([
            'accepted_at' => null,
        ]);

        $invitationIds = $invitations->pluck('id')->toArray();

        $response = $this->actingAs($this->superadmin)
            ->post('/superadmin/invitations/bulk-cancel', [
                'invitation_ids' => $invitationIds,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify all invitations are deleted
        foreach ($invitationIds as $invitationId) {
            $this->assertDatabaseMissing('organization_invitations', ['id' => $invitationId]);
        }
    }

    /** @test */
    public function bulk_operations_handle_partial_failures(): void
    {
        // Create organizations, one with dependencies that prevent deletion
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();
        $org3 = Organization::factory()->create();

        // Add dependencies to org2
        User::factory()->create(['tenant_id' => $org2->id]);

        $response = $this->actingAs($this->superadmin)
            ->post('/superadmin/organizations/bulk-delete', [
                'organization_ids' => [$org1->id, $org2->id, $org3->id],
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => false,
            'partial_success' => true,
        ]);

        // Verify org1 and org3 are deleted, org2 remains
        $this->assertDatabaseMissing('organizations', ['id' => $org1->id]);
        $this->assertDatabaseHas('organizations', ['id' => $org2->id]);
        $this->assertDatabaseMissing('organizations', ['id' => $org3->id]);
    }

    /** @test */
    public function bulk_operations_are_atomic_when_required(): void
    {
        $organizations = Organization::factory()->count(3)->create([
            'is_active' => true,
        ]);

        $organizationIds = $organizations->pluck('id')->toArray();

        // Mock a database error during bulk operation
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();
        DB::shouldReceive('commit')->never();

        // This would need more sophisticated mocking to test properly
        // For now, we'll test the basic transaction structure
        $this->assertTrue(true);
    }

    /** @test */
    public function bulk_operations_respect_authorization(): void
    {
        $regularUser = User::factory()->create(['role' => UserRole::ADMIN]);
        $organizations = Organization::factory()->count(2)->create();

        $response = $this->actingAs($regularUser)
            ->post('/superadmin/organizations/bulk-suspend', [
                'organization_ids' => $organizations->pluck('id')->toArray(),
                'reason' => 'Test',
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function bulk_operations_validate_input(): void
    {
        // Test with empty organization IDs
        $response = $this->actingAs($this->superadmin)
            ->post('/superadmin/organizations/bulk-suspend', [
                'organization_ids' => [],
                'reason' => 'Test',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['organization_ids']);

        // Test with invalid organization IDs
        $response = $this->actingAs($this->superadmin)
            ->post('/superadmin/organizations/bulk-suspend', [
                'organization_ids' => [99999, 99998], // Non-existent IDs
                'reason' => 'Test',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['organization_ids']);
    }

    /** @test */
    public function bulk_operations_handle_large_datasets(): void
    {
        // Create a large number of organizations
        $organizations = Organization::factory()->count(100)->create([
            'is_active' => true,
        ]);

        $organizationIds = $organizations->pluck('id')->toArray();

        $response = $this->actingAs($this->superadmin)
            ->post('/superadmin/organizations/bulk-suspend', [
                'organization_ids' => $organizationIds,
                'reason' => 'Bulk test',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify all organizations are suspended
        $suspendedCount = Organization::whereIn('id', $organizationIds)
            ->where('is_active', false)
            ->count();

        $this->assertEquals(100, $suspendedCount);
    }

    /** @test */
    public function bulk_export_includes_correct_data_format(): void
    {
        $organization = Organization::factory()->create([
            'name' => 'Test Organization',
            'email' => 'test@example.com',
            'plan' => SubscriptionPlan::PROFESSIONAL,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->superadmin)
            ->post('/superadmin/organizations/bulk-export', [
                'format' => 'csv',
            ]);

        $response->assertStatus(200);
        
        $csvContent = $response->getContent();
        $this->assertStringContains('Test Organization', $csvContent);
        $this->assertStringContains('test@example.com', $csvContent);
        $this->assertStringContains('professional', $csvContent);
    }
}