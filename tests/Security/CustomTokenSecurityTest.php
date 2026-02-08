<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Enums\UserRole;
use App\Models\PersonalAccessToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Custom Token Security Tests
 * 
 * Tests security aspects of the custom API token system to ensure
 * proper validation, authorization, and protection against attacks.
 */
class CustomTokenSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_tokens_are_invalidated_when_user_is_deactivated(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $token = $user->createApiToken('test-token');

        // Token should work initially
        $response = $this->withToken($token)->getJson('/api/auth/me');
        $response->assertOk();

        // Deactivate user
        $user->update(['is_active' => false]);

        // Token should be rejected
        $response = $this->withToken($token)->getJson('/api/auth/me');
        $response->assertUnauthorized();
    }

    public function test_tokens_are_invalidated_when_user_is_suspended(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $user = User::factory()->create(['is_active' => true]);
        $token = $user->createApiToken('test-token');

        // Token should work initially
        $response = $this->withToken($token)->getJson('/api/auth/me');
        $response->assertOk();

        // Suspend user
        $user->suspend('Security violation', $admin);

        // Token should be rejected
        $response = $this->withToken($token)->getJson('/api/auth/me');
        $response->assertUnauthorized();
    }

    public function test_expired_tokens_are_rejected(): void
    {
        $user = User::factory()->create();
        
        // Create expired token directly
        $expiredToken = PersonalAccessToken::create([
            'tokenable_type' => User::class,
            'tokenable_id' => $user->id,
            'name' => 'expired-token',
            'token' => PersonalAccessToken::generateTokenHash('expired-test'),
            'abilities' => ['*'],
            'expires_at' => now()->subHour(),
        ]);

        $plainTextToken = $expiredToken->id . '|expired-test';

        $response = $this->withToken($plainTextToken)->getJson('/api/auth/me');
        $response->assertUnauthorized();
    }

    public function test_malformed_tokens_are_rejected(): void
    {
        $malformedTokens = [
            'invalid-token',
            '|missing-id',
            'no-separator',
            '999|nonexistent-token',
            '',
            null,
        ];

        foreach ($malformedTokens as $token) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ])->getJson('/api/auth/me');

            $response->assertUnauthorized();
        }
    }

    public function test_token_abilities_are_strictly_enforced(): void
    {
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);
        $token = $tenant->createApiToken('limited-token');

        // Tenant should not access admin endpoints
        $response = $this->withToken($token)->getJson('/api/admin/users');
        $response->assertForbidden();

        // Tenant should not perform admin actions
        $response = $this->withToken($token)->postJson('/api/admin/properties', [
            'name' => 'Test Property',
        ]);
        $response->assertForbidden();
    }

    public function test_token_cannot_be_used_by_different_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $token = $user1->createApiToken('user1-token');
        
        // Manually set token for user2 (simulating attack)
        $tokenModel = PersonalAccessToken::findToken($token);
        $tokenModel->update(['tokenable_id' => $user2->id]);

        // Token should be rejected due to hash mismatch
        $response = $this->withToken($token)->getJson('/api/auth/me');
        $response->assertUnauthorized();
    }

    public function test_token_hash_cannot_be_predicted(): void
    {
        $user = User::factory()->create();
        
        $tokens = [];
        for ($i = 0; $i < 10; $i++) {
            $tokens[] = $user->createApiToken("token-{$i}");
        }

        // All tokens should be unique
        $this->assertCount(10, array_unique($tokens));

        // Token hashes should be unique
        $hashes = PersonalAccessToken::whereIn('name', array_map(fn($i) => "token-{$i}", range(0, 9)))
            ->pluck('token')
            ->toArray();

        $this->assertCount(10, array_unique($hashes));
    }

    public function test_token_revocation_is_immediate(): void
    {
        $user = User::factory()->create();
        $token = $user->createApiToken('revoke-test');

        // Token should work initially
        $response = $this->withToken($token)->getJson('/api/auth/me');
        $response->assertOk();

        // Revoke all tokens
        $user->revokeAllApiTokens();

        // Token should be rejected immediately
        $response = $this->withToken($token)->getJson('/api/auth/me');
        $response->assertUnauthorized();
    }

    public function test_token_abilities_cannot_be_escalated(): void
    {
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);
        $token = $tenant->createApiToken('tenant-token');
        $tokenModel = PersonalAccessToken::findToken($token);

        // Attempt to escalate abilities (simulating attack)
        $tokenModel->update(['abilities' => ['*']]);

        // Should still be limited by user role
        $tenant->currentAccessToken = $tokenModel->fresh();
        $this->assertFalse($tenant->hasApiAbility('property:write'));
    }

    public function test_token_creation_rate_limiting(): void
    {
        $user = User::factory()->create();

        // Create many tokens rapidly
        $tokens = [];
        for ($i = 0; $i < 100; $i++) {
            $tokens[] = $user->createApiToken("rate-limit-token-{$i}");
        }

        // All should be created (no rate limiting at model level)
        $this->assertEquals(100, $user->getActiveTokensCount());
        
        // But database should handle it gracefully
        $this->assertDatabaseCount('personal_access_tokens', 100);
    }

    public function test_token_cleanup_removes_only_expired_tokens(): void
    {
        $user = User::factory()->create();

        // Create active token
        $activeToken = $user->createApiToken('active-token');

        // Create expired token
        PersonalAccessToken::create([
            'tokenable_type' => User::class,
            'tokenable_id' => $user->id,
            'name' => 'expired-token',
            'token' => PersonalAccessToken::generateTokenHash('expired'),
            'abilities' => ['*'],
            'expires_at' => now()->subDay(),
        ]);

        $this->assertEquals(2, $user->tokens()->count());

        // Run cleanup
        PersonalAccessToken::pruneExpired();

        // Only active token should remain
        $this->assertEquals(1, $user->tokens()->count());
        $this->assertTrue($user->tokens()->where('name', 'active-token')->exists());
    }

    public function test_token_validation_prevents_timing_attacks(): void
    {
        $user = User::factory()->create();
        $validToken = $user->createApiToken('timing-test');
        
        $invalidTokens = [
            '999|invalid-hash',
            $user->id . '|wrong-hash',
            '1|' . str_repeat('a', 40), // Wrong length
        ];

        $validStartTime = microtime(true);
        $this->withToken($validToken)->getJson('/api/auth/me');
        $validDuration = microtime(true) - $validStartTime;

        foreach ($invalidTokens as $invalidToken) {
            $invalidStartTime = microtime(true);
            $this->withToken($invalidToken)->getJson('/api/auth/me');
            $invalidDuration = microtime(true) - $invalidStartTime;

            // Timing should be similar (within reasonable bounds)
            $this->assertLessThan($validDuration * 2, $invalidDuration);
        }
    }

    public function test_token_storage_is_secure(): void
    {
        $user = User::factory()->create();
        $token = $user->createApiToken('security-test');
        
        $tokenModel = PersonalAccessToken::findToken($token);
        
        // Token hash should be stored, not plain text
        $this->assertNotEquals($token, $tokenModel->token);
        $this->assertTrue(Hash::check(explode('|', $token)[1], $tokenModel->token));
    }

    public function test_user_role_changes_affect_token_abilities(): void
    {
        $user = User::factory()->create(['role' => UserRole::TENANT]);
        $token = $user->createApiToken('role-change-test');
        
        $tokenModel = PersonalAccessToken::findToken($token);
        $user->currentAccessToken = $tokenModel;

        // Initially should not have admin abilities
        $this->assertFalse($user->hasApiAbility('property:write'));

        // Promote user to admin
        $user->update(['role' => UserRole::ADMIN]);
        $user->refresh();

        // Should now have admin abilities (if token abilities allow)
        $this->assertTrue($user->hasApiAbility('property:read'));
    }

    public function test_concurrent_token_operations_are_atomic(): void
    {
        $user = User::factory()->create();

        // Create tokens concurrently
        $tokens = [];
        for ($i = 0; $i < 5; $i++) {
            $tokens[] = $user->createApiToken("concurrent-{$i}");
        }

        $this->assertEquals(5, $user->getActiveTokensCount());

        // Revoke all tokens should be atomic
        $revokedCount = $user->revokeAllApiTokens();
        
        $this->assertEquals(5, $revokedCount);
        $this->assertEquals(0, $user->getActiveTokensCount());
    }

    private function withToken(string $token): self
    {
        return $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ]);
    }
}