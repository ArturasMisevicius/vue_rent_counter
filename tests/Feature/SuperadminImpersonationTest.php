<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\OrganizationActivityLog;
use App\Models\User;
use App\Services\ImpersonationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class SuperadminImpersonationTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;
    private User $targetUser;
    private Organization $organization;
    private ImpersonationService $impersonationService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->superadmin = User::factory()->create([
            'role' => UserRole::SUPERADMIN,
            'tenant_id' => null,
        ]);

        $this->organization = Organization::factory()->create();
        
        $this->targetUser = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => $this->organization->id,
        ]);

        $this->impersonationService = app(ImpersonationService::class);
    }

    /** @test */
    public function superadmin_can_start_impersonation_with_audit_logging(): void
    {
        $response = $this->actingAs($this->superadmin)
            ->post("/superadmin/users/{$this->targetUser->id}/impersonate", [
                'reason' => 'Support request from user',
            ]);

        $response->assertStatus(302);
        $response->assertRedirect('/admin/dashboard');

        // Verify impersonation session is active
        $this->assertTrue(Session::has('impersonation'));
        
        $impersonationData = Session::get('impersonation');
        $this->assertEquals($this->superadmin->id, $impersonationData['superadmin_id']);
        $this->assertEquals($this->targetUser->id, $impersonationData['target_user_id']);
        $this->assertEquals('Support request from user', $impersonationData['reason']);

        // Verify audit log was created
        $this->assertDatabaseHas('organization_activity_logs', [
            'organization_id' => $this->organization->id,
            'user_id' => $this->superadmin->id,
            'action' => 'impersonation_started',
            'resource_type' => 'User',
            'resource_id' => $this->targetUser->id,
        ]);

        // Verify user context has switched
        $this->assertEquals($this->targetUser->id, Auth::id());
    }

    /** @test */
    public function impersonation_displays_banner_indicating_active_session(): void
    {
        // Start impersonation
        $this->impersonationService->startImpersonation($this->targetUser, 'Testing');

        $response = $this->get('/admin/dashboard');

        $response->assertStatus(200);
        
        // Check for impersonation banner
        $response->assertSee('You are impersonating');
        $response->assertSee($this->targetUser->name);
        $response->assertSee('End Impersonation');
    }

    /** @test */
    public function superadmin_can_end_impersonation_with_session_cleanup(): void
    {
        // Start impersonation first
        $this->impersonationService->startImpersonation($this->targetUser, 'Testing');
        
        // Verify impersonation is active
        $this->assertTrue($this->impersonationService->isImpersonating());
        $this->assertEquals($this->targetUser->id, Auth::id());

        // End impersonation
        $response = $this->post('/superadmin/impersonation/end');

        $response->assertStatus(302);
        $response->assertRedirect('/superadmin/dashboard');

        // Verify impersonation session is cleared
        $this->assertFalse(Session::has('impersonation'));
        $this->assertFalse($this->impersonationService->isImpersonating());

        // Verify user context has switched back to superadmin
        $this->assertEquals($this->superadmin->id, Auth::id());

        // Verify audit log for impersonation end
        $this->assertDatabaseHas('organization_activity_logs', [
            'organization_id' => $this->organization->id,
            'user_id' => $this->superadmin->id,
            'action' => 'impersonation_ended',
            'resource_type' => 'User',
            'resource_id' => $this->targetUser->id,
        ]);
    }

    /** @test */
    public function impersonation_context_switching_works_correctly(): void
    {
        // Create properties for the target user's organization
        $property = \App\Models\Property::factory()->create([
            'tenant_id' => $this->organization->id,
        ]);

        // Start impersonation
        $this->impersonationService->startImpersonation($this->targetUser, 'Testing context');

        // Access a tenant-scoped resource
        $response = $this->get("/admin/properties/{$property->id}");

        $response->assertStatus(200);
        $response->assertSee($property->name);

        // Verify the user can only see their organization's data
        $otherOrganization = Organization::factory()->create();
        $otherProperty = \App\Models\Property::factory()->create([
            'tenant_id' => $otherOrganization->id,
        ]);

        $response = $this->get("/admin/properties/{$otherProperty->id}");
        $response->assertStatus(404); // Should not be able to access other org's data
    }

    /** @test */
    public function impersonation_timeout_works_correctly(): void
    {
        // Mock session data with old timestamp (over 30 minutes ago)
        Session::put('impersonation', [
            'superadmin_id' => $this->superadmin->id,
            'target_user_id' => $this->targetUser->id,
            'started_at' => now()->subMinutes(35)->toIso8601String(),
            'reason' => 'Testing timeout',
        ]);

        // Check if impersonation has timed out
        $this->assertTrue($this->impersonationService->hasTimedOut());

        // Accessing any page should automatically end impersonation
        $response = $this->get('/admin/dashboard');

        $response->assertStatus(302);
        $response->assertRedirect('/superadmin/dashboard');
        
        // Verify session is cleared
        $this->assertFalse(Session::has('impersonation'));
    }

    /** @test */
    public function impersonation_prevents_impersonating_other_superadmins(): void
    {
        $otherSuperadmin = User::factory()->create([
            'role' => UserRole::SUPERADMIN,
            'tenant_id' => null,
        ]);

        $response = $this->actingAs($this->superadmin)
            ->post("/superadmin/users/{$otherSuperadmin->id}/impersonate", [
                'reason' => 'Should not work',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['target_user']);

        // Verify no impersonation session was created
        $this->assertFalse(Session::has('impersonation'));
    }

    /** @test */
    public function impersonation_prevents_self_impersonation(): void
    {
        $response = $this->actingAs($this->superadmin)
            ->post("/superadmin/users/{$this->superadmin->id}/impersonate", [
                'reason' => 'Should not work',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['target_user']);

        // Verify no impersonation session was created
        $this->assertFalse(Session::has('impersonation'));
    }

    /** @test */
    public function non_superadmin_cannot_impersonate_users(): void
    {
        $regularAdmin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => $this->organization->id,
        ]);

        $response = $this->actingAs($regularAdmin)
            ->post("/superadmin/users/{$this->targetUser->id}/impersonate", [
                'reason' => 'Should not work',
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function impersonation_history_is_tracked(): void
    {
        // Start and end impersonation
        $this->impersonationService->startImpersonation($this->targetUser, 'Support session 1');
        sleep(1); // Ensure different timestamps
        $this->impersonationService->endImpersonation();

        // Start another impersonation session
        $this->impersonationService->startImpersonation($this->targetUser, 'Support session 2');
        $this->impersonationService->endImpersonation();

        // Check impersonation history
        $response = $this->actingAs($this->superadmin)
            ->get('/superadmin/impersonation/history');

        $response->assertStatus(200);
        $response->assertSee('Support session 1');
        $response->assertSee('Support session 2');
        $response->assertSee($this->targetUser->name);
        $response->assertSee($this->superadmin->name);
    }

    /** @test */
    public function impersonation_history_shows_duration_and_actions(): void
    {
        // Start impersonation
        $this->impersonationService->startImpersonation($this->targetUser, 'Testing duration');

        // Perform some actions while impersonating
        $property = \App\Models\Property::factory()->create([
            'tenant_id' => $this->organization->id,
        ]);

        $this->put("/admin/properties/{$property->id}", [
            'name' => 'Updated Property Name',
            'address' => $property->address,
            'property_type' => $property->property_type->value,
        ]);

        // End impersonation
        $this->impersonationService->endImpersonation();

        // Check that actions taken during impersonation are logged
        $this->assertDatabaseHas('organization_activity_logs', [
            'organization_id' => $this->organization->id,
            'user_id' => $this->superadmin->id, // Should be logged under superadmin
            'action' => 'property_updated',
            'resource_type' => 'Property',
            'resource_id' => $property->id,
        ]);
    }

    /** @test */
    public function impersonation_session_data_is_secure(): void
    {
        $this->impersonationService->startImpersonation($this->targetUser, 'Security test');

        $impersonationData = Session::get('impersonation');

        // Verify session contains required security information
        $this->assertArrayHasKey('superadmin_id', $impersonationData);
        $this->assertArrayHasKey('target_user_id', $impersonationData);
        $this->assertArrayHasKey('started_at', $impersonationData);
        $this->assertArrayHasKey('ip_address', $impersonationData);
        $this->assertArrayHasKey('user_agent', $impersonationData);

        // Verify IP address is recorded
        $this->assertNotNull($impersonationData['ip_address']);
    }

    /** @test */
    public function impersonation_middleware_detects_active_session(): void
    {
        $this->impersonationService->startImpersonation($this->targetUser, 'Middleware test');

        // Any request should include impersonation context
        $response = $this->get('/admin/dashboard');

        $response->assertStatus(200);
        
        // Check that impersonation middleware added the banner
        $response->assertSee('impersonation-banner');
        $response->assertSee('You are currently impersonating');
    }

    /** @test */
    public function impersonation_can_be_filtered_by_superadmin_and_date(): void
    {
        $otherSuperadmin = User::factory()->create([
            'role' => UserRole::SUPERADMIN,
            'tenant_id' => null,
        ]);

        // Create impersonation sessions by different superadmins
        $this->actingAs($this->superadmin);
        $this->impersonationService->startImpersonation($this->targetUser, 'Session by superadmin 1');
        $this->impersonationService->endImpersonation();

        $this->actingAs($otherSuperadmin);
        $this->impersonationService->startImpersonation($this->targetUser, 'Session by superadmin 2');
        $this->impersonationService->endImpersonation();

        // Filter by specific superadmin
        $response = $this->actingAs($this->superadmin)
            ->get('/superadmin/impersonation/history?superadmin_id=' . $this->superadmin->id);

        $response->assertStatus(200);
        $response->assertSee('Session by superadmin 1');
        $response->assertDontSee('Session by superadmin 2');
    }

    /** @test */
    public function impersonation_logs_include_complete_context(): void
    {
        $this->impersonationService->startImpersonation($this->targetUser, 'Complete context test');

        // Get the activity log entry
        $activityLog = OrganizationActivityLog::where('action', 'impersonation_started')->first();

        $this->assertNotNull($activityLog);
        $this->assertEquals($this->organization->id, $activityLog->organization_id);
        $this->assertEquals($this->superadmin->id, $activityLog->user_id);
        $this->assertEquals('User', $activityLog->resource_type);
        $this->assertEquals($this->targetUser->id, $activityLog->resource_id);

        // Check metadata contains target user information
        $metadata = $activityLog->metadata;
        $this->assertEquals($this->targetUser->name, $metadata['target_user_name']);
        $this->assertEquals($this->targetUser->email, $metadata['target_user_email']);
        $this->assertEquals('Complete context test', $metadata['reason']);
    }
}