<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\PersonalAccessToken;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * User Model Security Tests
 * 
 * Tests security fixes implemented for the User model:
 * - Mass assignment protection
 * - Secure privilege escalation methods
 * - API token security requirements
 */
class UserModelSecurityTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_prevents_mass_assignment_privilege_escalation(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::TENANT,
            'tenant_id' => 1,
            'is_super_admin' => false,
        ]);
        
        $originalRole = $user->role;
        $originalTenantId = $user->tenant_id;
        $originalSuperAdmin = $user->is_super_admin;

        // Attempt mass assignment privilege escalation
        $user->fill([
            'role' => UserRole::SUPERADMIN,
            'is_super_admin' => true,
            'tenant_id' => 999,
            'system_tenant_id' => 1,
        ]);

        // Should not change sensitive fields
        $this->assertEquals($originalRole, $user->role);
        $this->assertEquals($originalTenantId, $user->tenant_id);
        $this->assertEquals($originalSuperAdmin, $user->is_super_admin);
        $this->assertNull($user->system_tenant_id);
    }

    /** @test */
    public function it_requires_admin_privileges_for_tenant_assignment(): void
    {
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);
        $regularUser = User::factory()->create(['role' => UserRole::TENANT]);

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Insufficient privileges to assign tenant');

        $tenant->assignToTenant(2, $regularUser);
    }

    /** @test */
    public function it_allows_admin_to_assign_tenant(): void
    {
        Log::fake();
        
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT, 'tenant_id' => 1]);

        $tenant->assignToTenant(2, $admin);

        $this->assertEquals(2, $tenant->fresh()->tenant_id);
        
        Log::assertLogged('info', function ($message, $context) use ($tenant, $admin) {
            return $message === 'User assigned to tenant' &&
                   $context['user_id'] === $tenant->id &&
                   $context['tenant_id'] === 2 &&
                   $context['admin_id'] === $admin->id;
        });
    }

    /** @test */
    public function it_requires_superadmin_for_promotion(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $user = User::factory()->create(['role' => UserRole::TENANT]);

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Only superadmins can promote users');

        $user->promoteToSuperAdmin($admin);
    }

    /** @test */
    public function it_allows_superadmin_to_promote_users(): void
    {
        Log::fake();
        
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $user = User::factory()->create(['role' => UserRole::ADMIN]);

        $user->promoteToSuperAdmin($superadmin);

        $fresh = $user->fresh();
        $this->assertEquals(UserRole::SUPERADMIN, $fresh->role);
        $this->assertTrue($fresh->is_super_admin);
        $this->assertNull($fresh->tenant_id);
        
        Log::assertLogged('warning', function ($message, $context) use ($user, $superadmin) {
            return $message === 'User promoted to superadmin' &&
                   $context['user_id'] === $user->id &&
                   $context['promoted_by'] === $superadmin->id;
        });
    }

    /** @test */
    public function it_revokes_tokens_when_suspending_user(): void
    {
        Log::fake();
        
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $user = User::factory()->create(['role' => UserRole::TENANT]);
        
        // Create some tokens
        $user->createApiToken('token1');
        $user->createApiToken('token2');
        
        $this->assertEquals(2, $user->getActiveTokensCount());

        $user->suspend('Policy violation', $admin);

        $fresh = $user->fresh();
        $this->assertNotNull($fresh->suspended_at);
        $this->assertEquals('Policy violation', $fresh->suspension_reason);
        $this->assertEquals(0, $fresh->getActiveTokensCount());
        
        Log::assertLogged('warning', function ($message, $context) use ($user, $admin) {
            return $message === 'User account suspended' &&
                   $context['user_id'] === $user->id &&
                   $context['reason'] === 'Policy violation' &&
                   $context['suspended_by'] === $admin->id;
        });
    }

    /** @test */
    public function it_requires_email_verification_for_api_access(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'email_verified_at' => null, // Unverified
            'suspended_at' => null,
        ]);
        
        $token = $user->createApiToken('test');

        $response = $this->withToken($token)->getJson('/api/user');

        $response->assertUnauthorized();
    }

    /** @test */
    public function it_allows_api_access_for_verified_active_users(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'email_verified_at' => now(),
            'suspended_at' => null,
        ]);
        
        $token = $user->createApiToken('test');

        $response = $this->withToken($token)->getJson('/api/user');

        $response->assertOk();
    }

    /** @test */
    public function it_denies_api_access_for_suspended_users(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'email_verified_at' => now(),
            'suspended_at' => now(),
        ]);
        
        $token = $user->createApiToken('test');

        $response = $this->withToken($token)->getJson('/api/user');

        $response->assertUnauthorized();
    }

    /** @test */
    public function it_rate_limits_token_validation_attempts(): void
    {
        // Make 61 invalid token requests (over the 60/minute limit)
        for ($i = 0; $i < 61; $i++) {
            $this->withToken('invalid-token-' . $i)->getJson('/api/user');
        }
        
        // Next request should be rate limited
        $response = $this->withToken('invalid-token-final')->getJson('/api/user');
        $response->assertStatus(429);
        $response->assertJson(['message' => 'Too many attempts']);
    }

    /** @test */
    public function it_logs_invalid_token_attempts(): void
    {
        Log::fake();
        
        $this->withToken('invalid-token')->getJson('/api/user');
        
        Log::assertLogged('warning', function ($message, $context) {
            return $message === 'Invalid token validation attempt' &&
                   isset($context['ip']) &&
                   isset($context['token_prefix']) &&
                   $context['token_prefix'] === 'invalid-..';
        });
    }

    /** @test */
    public function it_clears_rate_limit_on_successful_authentication(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        
        $token = $user->createApiToken('test');

        // Make some failed attempts first
        for ($i = 0; $i < 5; $i++) {
            $this->withToken('invalid-token')->getJson('/api/user');
        }

        // Successful authentication should clear rate limit
        $response = $this->withToken($token)->getJson('/api/user');
        $response->assertOk();

        // Should be able to make more requests
        for ($i = 0; $i < 10; $i++) {
            $response = $this->withToken($token)->getJson('/api/user');
            $response->assertOk();
        }
    }

    /** @test */
    public function it_updates_token_last_used_timestamp(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        
        $tokenString = $user->createApiToken('test');
        $token = PersonalAccessToken::findToken($tokenString);
        
        $this->assertNull($token->last_used_at);

        $this->withToken($tokenString)->getJson('/api/user');

        $this->assertNotNull($token->fresh()->last_used_at);
    }

    /** @test */
    public function it_prevents_access_with_expired_tokens(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        
        // Create token that expires immediately
        $tokenString = $user->createApiToken('test', null, now()->subMinute());

        $response = $this->withToken($tokenString)->getJson('/api/user');

        $response->assertUnauthorized();
    }
}