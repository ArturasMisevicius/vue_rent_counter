<?php

namespace Tests\Unit;

use App\Models\OrganizationActivityLog;
use App\Models\User;
use App\Services\ImpersonationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class ImpersonationServiceTest extends TestCase
{
    use RefreshDatabase;

    private ImpersonationService $impersonationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->impersonationService = app(ImpersonationService::class);
    }

    /** @test */
    public function it_can_start_impersonation_with_audit_logging()
    {
        // Create superadmin and target user
        $superadmin = User::factory()->create(['role' => 'superadmin']);
        $targetUser = User::factory()->create(['role' => 'admin']);

        // Authenticate as superadmin
        Auth::login($superadmin);

        // Start impersonation
        $this->impersonationService->startImpersonation($targetUser, 'Testing impersonation');

        // Assert session data is stored
        $this->assertTrue(Session::has('impersonation'));
        $impersonationData = Session::get('impersonation');
        $this->assertEquals($superadmin->id, $impersonationData['superadmin_id']);
        $this->assertEquals($targetUser->id, $impersonationData['target_user_id']);
        $this->assertEquals('Testing impersonation', $impersonationData['reason']);

        // Assert user is switched to target user
        $this->assertEquals($targetUser->id, Auth::id());

        // Assert activity log is created
        $this->assertDatabaseHas('organization_activity_logs', [
            'user_id' => $superadmin->id,
            'action' => 'impersonation_started',
            'resource_type' => 'User',
            'resource_id' => $targetUser->id,
        ]);
    }

    /** @test */
    public function it_can_end_impersonation_with_audit_logging()
    {
        // Create superadmin and target user
        $superadmin = User::factory()->create(['role' => 'superadmin']);
        $targetUser = User::factory()->create(['role' => 'admin']);

        // Set up impersonation session
        Session::put('impersonation', [
            'superadmin_id' => $superadmin->id,
            'target_user_id' => $targetUser->id,
            'started_at' => now()->subMinutes(5)->toIso8601String(),
            'reason' => 'Testing',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Agent',
        ]);

        Auth::login($targetUser);

        // End impersonation
        $this->impersonationService->endImpersonation();

        // Assert session data is cleared
        $this->assertFalse(Session::has('impersonation'));

        // Assert user is switched back to superadmin
        $this->assertEquals($superadmin->id, Auth::id());

        // Assert activity log is created
        $this->assertDatabaseHas('organization_activity_logs', [
            'user_id' => $superadmin->id,
            'action' => 'impersonation_ended',
            'resource_type' => 'User',
            'resource_id' => $targetUser->id,
        ]);
    }

    /** @test */
    public function it_detects_timeout_correctly()
    {
        // Set up expired impersonation session (31 minutes ago)
        Session::put('impersonation', [
            'superadmin_id' => 1,
            'target_user_id' => 2,
            'started_at' => now()->subMinutes(31)->toIso8601String(),
            'reason' => 'Testing',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Agent',
        ]);

        $this->assertTrue($this->impersonationService->hasTimedOut());
    }

    /** @test */
    public function it_prevents_non_superadmin_from_impersonating()
    {
        // Create regular admin user and target user
        $admin = User::factory()->create(['role' => 'admin']);
        $targetUser = User::factory()->create(['role' => 'manager']);

        // Authenticate as admin (not superadmin)
        Auth::login($admin);

        // Attempt to start impersonation should throw exception
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Only superadmins can impersonate users');

        $this->impersonationService->startImpersonation($targetUser);
    }

    /** @test */
    public function it_prevents_self_impersonation()
    {
        // Create superadmin
        $superadmin = User::factory()->create(['role' => 'superadmin']);

        // Authenticate as superadmin
        Auth::login($superadmin);

        // Attempt to impersonate self should throw exception
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot impersonate yourself');

        $this->impersonationService->startImpersonation($superadmin);
    }
}