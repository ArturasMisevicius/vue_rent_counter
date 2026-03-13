<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Enums\UserRole;
use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Services\ApiTokenManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Custom Token System Integration Tests
 * 
 * Tests the integration between User model and ApiTokenManager service
 * to ensure the custom token system works correctly end-to-end.
 */
class CustomTokenSystemIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private ApiTokenManager $tokenManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenManager = app(ApiTokenManager::class);
    }

    public function test_user_can_create_tokens_with_role_based_abilities(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        $adminToken = $admin->createApiToken('admin-token');
        $tenantToken = $tenant->createApiToken('tenant-token');

        $this->assertIsString($adminToken);
        $this->assertIsString($tenantToken);

        // Verify tokens exist in database
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $admin->id,
            'name' => 'admin-token',
        ]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $tenant->id,
            'name' => 'tenant-token',
        ]);

        // Verify abilities are set correctly
        $adminTokenModel = PersonalAccessToken::findToken($adminToken);
        $tenantTokenModel = PersonalAccessToken::findToken($tenantToken);

        $this->assertContains('property:read', $adminTokenModel->abilities);
        $this->assertContains('property:write', $adminTokenModel->abilities);
        $this->assertContains('meter-reading:read', $tenantTokenModel->abilities);
        $this->assertNotContains('property:write', $tenantTokenModel->abilities);
    }

    public function test_user_can_create_tokens_with_custom_abilities(): void
    {
        $user = User::factory()->create(['role' => UserRole::MANAGER]);
        $customAbilities = ['custom:read', 'custom:write'];

        $token = $user->createApiToken('custom-token', $customAbilities);

        $tokenModel = PersonalAccessToken::findToken($token);
        $this->assertEquals($customAbilities, $tokenModel->abilities);
    }

    public function test_user_can_revoke_all_tokens(): void
    {
        $user = User::factory()->create();

        // Create multiple tokens
        $user->createApiToken('token-1');
        $user->createApiToken('token-2');
        $user->createApiToken('token-3');

        $this->assertEquals(3, $user->getActiveTokensCount());

        $revokedCount = $user->revokeAllApiTokens();

        $this->assertEquals(3, $revokedCount);
        $this->assertEquals(0, $user->getActiveTokensCount());

        // Verify tokens are deleted from database
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    }

    public function test_token_abilities_are_enforced_correctly(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        $adminToken = $admin->createApiToken('admin-token');
        $tenantToken = $tenant->createApiToken('tenant-token');

        // Set current tokens for ability checking
        $admin->currentAccessToken = PersonalAccessToken::findToken($adminToken);
        $tenant->currentAccessToken = PersonalAccessToken::findToken($tenantToken);

        // Admin should have property management abilities
        $this->assertTrue($admin->hasApiAbility('property:read'));
        $this->assertTrue($admin->hasApiAbility('property:write'));
        $this->assertTrue($admin->hasApiAbility('meter-reading:read'));

        // Tenant should have limited abilities
        $this->assertTrue($tenant->hasApiAbility('meter-reading:read'));
        $this->assertFalse($tenant->hasApiAbility('property:write'));
        $this->assertFalse($tenant->hasApiAbility('invoice:write'));
    }

    public function test_superadmin_has_all_abilities(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $token = $superadmin->createApiToken('superadmin-token');

        $tokenModel = PersonalAccessToken::findToken($token);
        $superadmin->currentAccessToken = $tokenModel;

        $this->assertEquals(['*'], $tokenModel->abilities);
        $this->assertTrue($superadmin->hasApiAbility('any:ability'));
        $this->assertTrue($superadmin->hasApiAbility('system:manage'));
    }

    public function test_token_expiration_is_handled_correctly(): void
    {
        $user = User::factory()->create();
        $expiresAt = now()->addDays(7);

        $result = $user->createToken('expiring-token', ['*'], $expiresAt);
        $tokenModel = PersonalAccessToken::findToken($result->plainTextToken);

        $this->assertEquals($expiresAt->toDateTimeString(), $tokenModel->expires_at->toDateTimeString());
    }

    public function test_token_validation_checks_user_status(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $token = $user->createApiToken('test-token');
        $tokenModel = PersonalAccessToken::findToken($token);

        // Active user should validate
        $this->assertTrue($tokenModel->validateForUser());

        // Inactive user should not validate
        $user->update(['is_active' => false]);
        $user->refresh();
        $this->assertFalse($tokenModel->validateForUser());

        // Suspended user should not validate
        $user->update(['is_active' => true, 'suspended_at' => now()]);
        $user->refresh();
        $this->assertFalse($tokenModel->validateForUser());
    }

    public function test_token_last_used_tracking_works(): void
    {
        $user = User::factory()->create();
        $token = $user->createApiToken('tracking-token');
        $tokenModel = PersonalAccessToken::findToken($token);

        $this->assertNull($tokenModel->last_used_at);

        // Simulate token usage
        $tokenModel->markAsUsed();

        $this->assertNotNull($tokenModel->fresh()->last_used_at);
    }

    public function test_token_cleanup_removes_expired_tokens(): void
    {
        $user = User::factory()->create();

        // Create expired token
        $expiredToken = PersonalAccessToken::create([
            'tokenable_type' => User::class,
            'tokenable_id' => $user->id,
            'name' => 'expired-token',
            'token' => PersonalAccessToken::generateTokenHash('test'),
            'abilities' => ['*'],
            'expires_at' => now()->subDay(),
        ]);

        // Create active token
        $activeToken = $user->createApiToken('active-token');

        $this->assertEquals(2, $user->tokens()->count());

        // Run cleanup
        PersonalAccessToken::pruneExpired();

        $this->assertEquals(1, $user->tokens()->count());
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $expiredToken->id,
        ]);
    }

    public function test_bulk_token_operations_are_efficient(): void
    {
        $users = User::factory()->count(10)->create();

        // Create tokens for all users
        foreach ($users as $user) {
            $user->createApiToken('bulk-token');
        }

        $this->assertEquals(10, PersonalAccessToken::count());

        // Bulk revoke all tokens
        $totalRevoked = 0;
        foreach ($users as $user) {
            $totalRevoked += $user->revokeAllApiTokens();
        }

        $this->assertEquals(10, $totalRevoked);
        $this->assertEquals(0, PersonalAccessToken::count());
    }

    public function test_token_abilities_can_be_updated(): void
    {
        $user = User::factory()->create(['role' => UserRole::MANAGER]);
        $token = $user->createApiToken('updatable-token');
        $tokenModel = PersonalAccessToken::findToken($token);

        $originalAbilities = $tokenModel->abilities;
        $newAbilities = ['limited:read'];

        $tokenModel->update(['abilities' => $newAbilities]);

        $this->assertNotEquals($originalAbilities, $tokenModel->fresh()->abilities);
        $this->assertEquals($newAbilities, $tokenModel->fresh()->abilities);
    }

    public function test_concurrent_token_operations_are_safe(): void
    {
        $user = User::factory()->create();

        // Simulate concurrent token creation
        $tokens = [];
        for ($i = 0; $i < 5; $i++) {
            $tokens[] = $user->createApiToken("concurrent-token-{$i}");
        }

        $this->assertCount(5, array_unique($tokens));
        $this->assertEquals(5, $user->getActiveTokensCount());

        // Simulate concurrent revocation
        $revokedCount = $user->revokeAllApiTokens();
        $this->assertEquals(5, $revokedCount);
    }

    public function test_token_security_hash_is_unique(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $token1 = $user1->createApiToken('security-token');
        $token2 = $user2->createApiToken('security-token');

        $tokenModel1 = PersonalAccessToken::findToken($token1);
        $tokenModel2 = PersonalAccessToken::findToken($token2);

        $this->assertNotEquals($tokenModel1->token, $tokenModel2->token);
        $this->assertNotEquals($token1, $token2);
    }
}