<?php

declare(strict_types=1);

namespace Tests\Property;

use App\Enums\UserRole;
use App\Models\PersonalAccessToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * User Model Token Invariants Property Tests
 * 
 * Property-based tests to ensure invariants hold for the custom token system
 * across different scenarios and edge cases.
 */
class UserModelTokenInvariantsTest extends TestCase
{
    use RefreshDatabase;

    public function test_token_creation_always_returns_valid_format(): void
    {
        $users = User::factory()->count(10)->create();
        $tokenNames = ['test', 'api-token', 'mobile-app', 'web-client', 'integration'];

        foreach ($users as $user) {
            foreach ($tokenNames as $tokenName) {
                $token = $user->createApiToken($tokenName);

                // Token should always have the format: id|hash
                $this->assertMatchesRegularExpression('/^\d+\|[a-zA-Z0-9]+$/', $token);
                
                // Token should be findable
                $tokenModel = PersonalAccessToken::findToken($token);
                $this->assertNotNull($tokenModel);
                $this->assertEquals($user->id, $tokenModel->tokenable_id);
                $this->assertEquals($tokenName, $tokenModel->name);
            }
        }
    }

    public function test_token_count_is_always_consistent(): void
    {
        $user = User::factory()->create();
        $expectedCount = 0;

        // Property: Token count should always match actual database count
        for ($i = 0; $i < 20; $i++) {
            $user->createApiToken("token-{$i}");
            $expectedCount++;

            $actualCount = $user->getActiveTokensCount();
            $dbCount = $user->tokens()->count();

            $this->assertEquals($expectedCount, $actualCount);
            $this->assertEquals($expectedCount, $dbCount);
        }

        // Revoke some tokens
        $tokensToRevoke = $user->tokens()->limit(5)->get();
        foreach ($tokensToRevoke as $token) {
            $token->delete();
            $expectedCount--;

            $actualCount = $user->getActiveTokensCount();
            $dbCount = $user->tokens()->count();

            $this->assertEquals($expectedCount, $actualCount);
            $this->assertEquals($expectedCount, $dbCount);
        }
    }

    public function test_token_abilities_are_always_role_appropriate(): void
    {
        $roles = [UserRole::SUPERADMIN, UserRole::ADMIN, UserRole::MANAGER, UserRole::TENANT];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);
            $token = $user->createApiToken('role-test');
            $tokenModel = PersonalAccessToken::findToken($token);

            switch ($role) {
                case UserRole::SUPERADMIN:
                    $this->assertEquals(['*'], $tokenModel->abilities);
                    break;
                    
                case UserRole::ADMIN:
                case UserRole::MANAGER:
                    $this->assertContains('property:read', $tokenModel->abilities);
                    $this->assertContains('property:write', $tokenModel->abilities);
                    $this->assertContains('meter-reading:read', $tokenModel->abilities);
                    break;
                    
                case UserRole::TENANT:
                    $this->assertContains('meter-reading:read', $tokenModel->abilities);
                    $this->assertNotContains('property:write', $tokenModel->abilities);
                    break;
            }
        }
    }

    public function test_token_revocation_is_always_complete(): void
    {
        $user = User::factory()->create();
        
        // Create random number of tokens
        $tokenCount = rand(1, 50);
        for ($i = 0; $i < $tokenCount; $i++) {
            $user->createApiToken("bulk-token-{$i}");
        }

        $this->assertEquals($tokenCount, $user->getActiveTokensCount());

        // Revoke all tokens
        $revokedCount = $user->revokeAllApiTokens();

        // Property: All tokens should be revoked
        $this->assertEquals($tokenCount, $revokedCount);
        $this->assertEquals(0, $user->getActiveTokensCount());
        $this->assertEquals(0, $user->tokens()->count());
    }

    public function test_token_validation_is_always_secure(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $token = $user->createApiToken('security-test');
        $tokenModel = PersonalAccessToken::findToken($token);

        // Property: Valid user with valid token should always validate
        $this->assertTrue($tokenModel->validateForUser());

        // Property: Invalid user states should never validate
        $invalidStates = [
            ['is_active' => false],
            ['suspended_at' => now()],
            ['is_active' => false, 'suspended_at' => now()],
        ];

        foreach ($invalidStates as $state) {
            $user->update($state);
            $user->refresh();
            $this->assertFalse($tokenModel->validateForUser());
        }
    }

    public function test_token_uniqueness_is_always_maintained(): void
    {
        $users = User::factory()->count(5)->create();
        $allTokens = [];

        // Create tokens for all users
        foreach ($users as $user) {
            for ($i = 0; $i < 10; $i++) {
                $token = $user->createApiToken("unique-test-{$i}");
                $allTokens[] = $token;
            }
        }

        // Property: All tokens should be unique
        $this->assertCount(50, $allTokens);
        $this->assertCount(50, array_unique($allTokens));

        // Property: All token hashes should be unique
        $tokenHashes = PersonalAccessToken::pluck('token')->toArray();
        $this->assertCount(50, $tokenHashes);
        $this->assertCount(50, array_unique($tokenHashes));
    }

    public function test_token_expiration_is_always_respected(): void
    {
        $user = User::factory()->create();

        // Create tokens with different expiration times
        $expirationTimes = [
            now()->addMinutes(1),
            now()->addHours(1),
            now()->addDays(1),
            now()->addWeeks(1),
            now()->addMonths(1),
        ];

        foreach ($expirationTimes as $expiresAt) {
            $result = $user->createToken('expiry-test', ['*'], $expiresAt);
            $tokenModel = PersonalAccessToken::findToken($result->plainTextToken);

            // Property: Expiration time should be set correctly
            $this->assertEquals(
                $expiresAt->toDateTimeString(),
                $tokenModel->expires_at->toDateTimeString()
            );

            // Property: Non-expired tokens should validate
            $this->assertTrue($tokenModel->validateForUser());
        }

        // Create expired token
        $expiredResult = $user->createToken('expired-test', ['*'], now()->subHour());
        $expiredTokenModel = PersonalAccessToken::findToken($expiredResult->plainTextToken);

        // Property: Expired tokens should not validate
        $this->assertFalse($expiredTokenModel->validateForUser());
    }

    public function test_concurrent_operations_maintain_consistency(): void
    {
        $user = User::factory()->create();

        // Simulate concurrent token creation
        $tokens = [];
        for ($i = 0; $i < 10; $i++) {
            $tokens[] = $user->createApiToken("concurrent-{$i}");
        }

        // Property: All operations should succeed
        $this->assertCount(10, $tokens);
        $this->assertEquals(10, $user->getActiveTokensCount());

        // Simulate concurrent mixed operations
        $user->createApiToken('mixed-1');
        $count1 = $user->getActiveTokensCount();
        
        $user->createApiToken('mixed-2');
        $count2 = $user->getActiveTokensCount();
        
        $revokedCount = $user->revokeAllApiTokens();
        $count3 = $user->getActiveTokensCount();

        // Property: Counts should be consistent
        $this->assertEquals(11, $count1);
        $this->assertEquals(12, $count2);
        $this->assertEquals(12, $revokedCount);
        $this->assertEquals(0, $count3);
    }

    public function test_ability_checking_is_always_deterministic(): void
    {
        $roles = [UserRole::ADMIN, UserRole::TENANT];
        $abilities = ['property:read', 'property:write', 'meter-reading:read', 'meter-reading:write'];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);
            $token = $user->createApiToken('deterministic-test');
            $tokenModel = PersonalAccessToken::findToken($token);
            $user->currentAccessToken = $tokenModel;

            foreach ($abilities as $ability) {
                // Property: Same ability check should always return same result
                $result1 = $user->hasApiAbility($ability);
                $result2 = $user->hasApiAbility($ability);
                $result3 = $user->hasApiAbility($ability);

                $this->assertEquals($result1, $result2);
                $this->assertEquals($result2, $result3);
            }
        }
    }

    public function test_token_cleanup_preserves_active_tokens(): void
    {
        $user = User::factory()->create();

        // Create mix of active and expired tokens
        $activeTokens = [];
        for ($i = 0; $i < 5; $i++) {
            $activeTokens[] = $user->createApiToken("active-{$i}");
        }

        $expiredCount = 0;
        for ($i = 0; $i < 3; $i++) {
            PersonalAccessToken::create([
                'tokenable_type' => User::class,
                'tokenable_id' => $user->id,
                'name' => "expired-{$i}",
                'token' => PersonalAccessToken::generateTokenHash("exp{$i}"),
                'abilities' => ['*'],
                'expires_at' => now()->subHours($i + 1),
            ]);
            $expiredCount++;
        }

        $totalBefore = $user->tokens()->count();
        $this->assertEquals(8, $totalBefore); // 5 active + 3 expired

        // Run cleanup
        PersonalAccessToken::pruneExpired();

        // Property: Only active tokens should remain
        $totalAfter = $user->tokens()->count();
        $this->assertEquals(5, $totalAfter);

        // Property: All active tokens should still be findable
        foreach ($activeTokens as $token) {
            $tokenModel = PersonalAccessToken::findToken($token);
            $this->assertNotNull($tokenModel);
        }
    }
}