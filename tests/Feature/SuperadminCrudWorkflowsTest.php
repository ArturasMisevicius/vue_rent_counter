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
use Tests\TestCase;

class SuperadminCrudWorkflowsTest extends TestCase
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
    public function organization_create_read_update_delete_workflow(): void
    {
        // CREATE - Test organization creation
        $organizationData = [
            'name' => 'Test Organization',
            'slug' => 'test-org',
            'email' => 'admin@testorg.com',
            'phone' => '+1234567890',
            'domain' => 'testorg.com',
            'plan' => SubscriptionPlan::PROFESSIONAL->value,
            'max_properties' => 500,
            'max_users' => 50,
            'subscription_ends_at' => now()->addYear()->format('Y-m-d H:i:s'),
            'timezone' => 'America/New_York',
            'locale' => 'en',
            'currency' => 'USD',
            'is_active' => true,
        ];

        $createResponse = $this->actingAs($this->superadmin)
            ->post('/superadmin/organizations', $organizationData);

        $createResponse->assertStatus(302); // Redirect after creation
        
        $organization = Organization::where('slug', 'test-org')->first();
        $this->assertNotNull($organization);
        $this->assertEquals('Test Organization', $organization->name);
        $this->assertEquals(SubscriptionPlan::PROFESSIONAL, $organization->plan);

        // READ - Test organization viewing
        $viewResponse = $this->actingAs($this->superadmin)
            ->get("/superadmin/organizations/{$organization->id}");

        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('Test Organization');
        $viewResponse->assertSee('admin@testorg.com');
        $viewResponse->assertSee('Professional');

        // UPDATE - Test organization editing
        $updateData = [
            'name' => 'Updated Organization Name',
            'email' => 'updated@testorg.com',
            'plan' => SubscriptionPlan::ENTERPRISE->value,
            'max_properties' => 9999,
            'max_users' => 999,
        ];

        $updateResponse = $this->actingAs($this->superadmin)
            ->put("/superadmin/organizations/{$organization->id}", array_merge($organizationData, $updateData));

        $updateResponse->assertStatus(302);
        
        $organization->refresh();
        $this->assertEquals('Updated Organization Name', $organization->name);
        $this->assertEquals('updated@testorg.com', $organization->email);
        $this->assertEquals(SubscriptionPlan::ENTERPRISE, $organization->plan);

        // DELETE - Test organization deletion (should fail if has dependencies)
        $deleteResponse = $this->actingAs($this->superadmin)
            ->delete("/superadmin/organizations/{$organization->id}");

        // Should succeed since no dependencies
        $deleteResponse->assertStatus(302);
        $this->assertDatabaseMissing('organizations', ['id' => $organization->id]);
    }

    /** @test */
    public function subscription_create_read_update_delete_workflow(): void
    {
        $user = User::factory()->create();

        // CREATE - Test subscription creation
        $subscriptionData = [
            'user_id' => $user->id,
            'plan_type' => 'professional',
            'status' => SubscriptionStatus::ACTIVE->value,
            'starts_at' => now()->format('Y-m-d H:i:s'),
            'expires_at' => now()->addYear()->format('Y-m-d H:i:s'),
            'max_properties' => 500,
            'max_tenants' => 200,
            'auto_renew' => true,
            'renewal_period' => 'annually',
        ];

        $createResponse = $this->actingAs($this->superadmin)
            ->post('/superadmin/subscriptions', $subscriptionData);

        $createResponse->assertStatus(302);
        
        $subscription = Subscription::where('user_id', $user->id)->first();
        $this->assertNotNull($subscription);
        $this->assertEquals('professional', $subscription->plan_type);
        $this->assertEquals(SubscriptionStatus::ACTIVE, $subscription->status);

        // READ - Test subscription viewing
        $viewResponse = $this->actingAs($this->superadmin)
            ->get("/superadmin/subscriptions/{$subscription->id}");

        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('professional');
        $viewResponse->assertSee('Active');
        $viewResponse->assertSee($user->name);

        // UPDATE - Test subscription editing
        $updateData = [
            'plan_type' => 'enterprise',
            'max_properties' => 9999,
            'max_tenants' => 999,
            'auto_renew' => false,
        ];

        $updateResponse = $this->actingAs($this->superadmin)
            ->put("/superadmin/subscriptions/{$subscription->id}", array_merge($subscriptionData, $updateData));

        $updateResponse->assertStatus(302);
        
        $subscription->refresh();
        $this->assertEquals('enterprise', $subscription->plan_type);
        $this->assertEquals(9999, $subscription->max_properties);
        $this->assertFalse($subscription->auto_renew);

        // Test subscription renewal action
        $renewResponse = $this->actingAs($this->superadmin)
            ->post("/superadmin/subscriptions/{$subscription->id}/renew", [
                'expires_at' => now()->addYears(2)->format('Y-m-d H:i:s'),
            ]);

        $renewResponse->assertStatus(302);
        
        $subscription->refresh();
        $this->assertEquals(SubscriptionStatus::ACTIVE, $subscription->status);

        // Test subscription suspension
        $suspendResponse = $this->actingAs($this->superadmin)
            ->post("/superadmin/subscriptions/{$subscription->id}/suspend");

        $suspendResponse->assertStatus(302);
        
        $subscription->refresh();
        $this->assertEquals(SubscriptionStatus::SUSPENDED, $subscription->status);

        // DELETE - Test subscription deletion
        $deleteResponse = $this->actingAs($this->superadmin)
            ->delete("/superadmin/subscriptions/{$subscription->id}");

        $deleteResponse->assertStatus(302);
        $this->assertDatabaseMissing('subscriptions', ['id' => $subscription->id]);
    }

    /** @test */
    public function invitation_create_send_accept_workflow(): void
    {
        // CREATE - Test invitation creation
        $invitationData = [
            'organization_name' => 'New Client Organization',
            'admin_email' => 'admin@newclient.com',
            'plan_type' => 'professional',
            'max_properties' => 500,
            'max_users' => 50,
            'expires_at' => now()->addDays(7)->format('Y-m-d H:i:s'),
        ];

        $createResponse = $this->actingAs($this->superadmin)
            ->post('/superadmin/invitations', $invitationData);

        $createResponse->assertStatus(302);
        
        $invitation = OrganizationInvitation::where('email', 'admin@newclient.com')->first();
        $this->assertNotNull($invitation);
        $this->assertEquals('New Client Organization', $invitation->organization_name);
        $this->assertEquals('professional', $invitation->plan_type);
        $this->assertNotNull($invitation->token);

        // READ - Test invitation viewing
        $viewResponse = $this->actingAs($this->superadmin)
            ->get("/superadmin/invitations/{$invitation->id}");

        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('New Client Organization');
        $viewResponse->assertSee('admin@newclient.com');
        $viewResponse->assertSee('Pending');

        // Test invitation resend action
        $originalToken = $invitation->token;
        
        $resendResponse = $this->actingAs($this->superadmin)
            ->post("/superadmin/invitations/{$invitation->id}/resend");

        $resendResponse->assertStatus(302);
        
        $invitation->refresh();
        $this->assertNotEquals($originalToken, $invitation->token);

        // Test invitation acceptance (simulate)
        $acceptResponse = $this->post("/invitations/{$invitation->token}/accept", [
            'admin_name' => 'John Doe',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $acceptResponse->assertStatus(302);
        
        $invitation->refresh();
        $this->assertNotNull($invitation->accepted_at);

        // Verify organization and user were created
        $organization = Organization::where('name', 'New Client Organization')->first();
        $this->assertNotNull($organization);
        
        $adminUser = User::where('email', 'admin@newclient.com')->first();
        $this->assertNotNull($adminUser);
        $this->assertEquals($organization->id, $adminUser->tenant_id);

        // Test invitation cancellation (create new invitation first)
        $newInvitation = OrganizationInvitation::factory()->create();
        
        $cancelResponse = $this->actingAs($this->superadmin)
            ->post("/superadmin/invitations/{$newInvitation->id}/cancel");

        $cancelResponse->assertStatus(302);
        $this->assertDatabaseMissing('organization_invitations', ['id' => $newInvitation->id]);
    }

    /** @test */
    public function user_management_workflow(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create([
            'tenant_id' => $organization->id,
            'role' => UserRole::ADMIN,
            'is_active' => true,
        ]);

        // READ - Test user viewing across organizations
        $viewResponse = $this->actingAs($this->superadmin)
            ->get("/superadmin/users/{$user->id}");

        $viewResponse->assertStatus(200);
        $viewResponse->assertSee($user->name);
        $viewResponse->assertSee($user->email);
        $viewResponse->assertSee($organization->name);

        // Test password reset action
        $resetResponse = $this->actingAs($this->superadmin)
            ->post("/superadmin/users/{$user->id}/reset-password");

        $resetResponse->assertStatus(302);
        $resetResponse->assertSessionHas('success');

        // Test user deactivation
        $deactivateResponse = $this->actingAs($this->superadmin)
            ->post("/superadmin/users/{$user->id}/deactivate", [
                'reason' => 'Policy violation',
            ]);

        $deactivateResponse->assertStatus(302);
        
        $user->refresh();
        $this->assertFalse($user->is_active);
        $this->assertNotNull($user->suspended_at);

        // Test user reactivation
        $reactivateResponse = $this->actingAs($this->superadmin)
            ->post("/superadmin/users/{$user->id}/reactivate");

        $reactivateResponse->assertStatus(302);
        
        $user->refresh();
        $this->assertTrue($user->is_active);
        $this->assertNull($user->suspended_at);

        // Test impersonation action
        $impersonateResponse = $this->actingAs($this->superadmin)
            ->post("/superadmin/users/{$user->id}/impersonate", [
                'reason' => 'Support request',
            ]);

        $impersonateResponse->assertStatus(302);
        
        // Should be redirected to user's dashboard
        $impersonateResponse->assertRedirect('/admin/dashboard');
        
        // Verify impersonation session is active
        $this->assertTrue(session()->has('impersonation'));
    }

    /** @test */
    public function organization_deletion_prevents_deletion_with_dependencies(): void
    {
        $organization = Organization::factory()->create();
        
        // Create dependencies
        User::factory()->create(['tenant_id' => $organization->id]);
        \App\Models\Property::factory()->create(['tenant_id' => $organization->id]);

        $deleteResponse = $this->actingAs($this->superadmin)
            ->delete("/superadmin/organizations/{$organization->id}");

        // Should fail due to dependencies
        $deleteResponse->assertStatus(422);
        $deleteResponse->assertJsonValidationErrors(['dependencies']);
        
        // Organization should still exist
        $this->assertDatabaseHas('organizations', ['id' => $organization->id]);
    }

    /** @test */
    public function crud_operations_create_audit_logs(): void
    {
        $organization = Organization::factory()->create();

        // Perform an update operation
        $this->actingAs($this->superadmin)
            ->put("/superadmin/organizations/{$organization->id}", [
                'name' => 'Updated Name',
                'email' => $organization->email,
                'plan' => $organization->plan->value,
                'max_properties' => $organization->max_properties,
                'max_users' => $organization->max_users,
                'subscription_ends_at' => $organization->subscription_ends_at->format('Y-m-d H:i:s'),
            ]);

        // Verify audit log was created
        $this->assertDatabaseHas('organization_activity_logs', [
            'organization_id' => $organization->id,
            'user_id' => $this->superadmin->id,
            'action' => 'organization_updated',
            'resource_type' => 'Organization',
            'resource_id' => $organization->id,
        ]);
    }

    /** @test */
    public function crud_operations_handle_validation_errors(): void
    {
        // Test organization creation with invalid data
        $invalidData = [
            'name' => '', // Required field
            'email' => 'invalid-email', // Invalid email
            'plan' => 'invalid-plan', // Invalid plan
        ];

        $response = $this->actingAs($this->superadmin)
            ->post('/superadmin/organizations', $invalidData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'email', 'plan']);
    }

    /** @test */
    public function crud_operations_respect_authorization(): void
    {
        $regularUser = User::factory()->create(['role' => UserRole::ADMIN]);
        $organization = Organization::factory()->create();

        // Regular user should not be able to access superadmin CRUD
        $response = $this->actingAs($regularUser)
            ->get("/superadmin/organizations/{$organization->id}");

        $response->assertStatus(403);
    }
}