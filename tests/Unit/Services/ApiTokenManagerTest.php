<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\UserRole;
use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Services\ApiTokenManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * API Token Manager Unit Tests
 * 
 * Tests the custom API token management system to ensure
 * it maintains the same functionality as HasApiTokens trait.
 */
class ApiTokenManagerTest extends TestCase
{
    use RefreshDatabase;

    private ApiTokenManager $tokenManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenManager = app(ApiTokenManager::class);
    }

    public function test_create_token_generates_valid_token(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);

        $token = $this->tokenManager->createToken($user, 'test-token');

        $this->assertIsString($token);
        $this->assertStringContainsString('|', $token);
        
        // Verify token can be found
        $foundToken = PersonalAccessToken::findToken($token);
        $this->assertNotNull($foundToken);
        $this->assertEquals($user->id, $foundToken->tokenable_id);
        $this->assertEquals('test-token', $foundToken->name);
    }

    public function test_create_token_assigns_role_based_abilities(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        $adminToken = $this->tokenManager->createToken($admin, 'admin-token');
        $tenantToken = $this->tokenManager->createToken($tenant, 'tenant-token');

        $adminTokenModel = PersonalAccessToken::findToken($adminToken);
        $tenantTokenModel = PersonalAccessToken::findToken($tenantToken);

        $this->assertContains('property:write', $adminTokenModel->abilities);
        $this->assertNotContains('property:write', $tenantTokenModel->abilities);
        $this->assertContains('meter-reading:read', $tenantTokenModel->abilities);
    }

    public function test_create_token_with_custom_abilities(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        $customAbilities = ['custom:read', 'custom:write'];

        $token = $this->tokenManager->createToken($user, 'custom-token', $customAbilities);

        $tokenModel = PersonalAccessToken::findToken($token);
        $this->assertEquals($customAbilities, $tokenModel->abilities);
    }

    public function test_get_user_tokens_returns_active_tokens(): void
    {
        $user = User::factory()->create();
        
        // Create active token
        $this->tokenManager->createToken($user, 'active-token');
        
        // Create expired token
        PersonalAccessToken::create([
            'tokenable_type' => User::class,
            'tokenable_id' => $user->id,
            'name' => 'expired-token',
            'token' => 'hash',
            'abilities' => ['*'],
            'expires_at' => now()->subDay(),
        ]);

        $tokens = $this->tokenManager->getUserTokens($user);

        $this->assertCount(1, $tokens);
        $this->assertEquals('active-token', $tokens->first()->name);
    }

    public function test_get_active_token_count_returns_correct_count(): void
    {
        $user = User::factory()->create();
        
        $this->tokenManager->createToken($user, 'token-1');
        $this->tokenManager->createToken($user, 'token-2');

        $count = $this->tokenManager->getActiveTokenCount($user);

        $this->assertEquals(2, $count);
    }

    public function test_revoke_all_tokens_removes_all_user_tokens(): void
    {
        $user = User::factory()->create();
        
        $this->tokenManager->createToken($user, 'token-1');
        $this->tokenManager->createToken($user, 'token-2');

        $revokedCount = $this->tokenManager->revokeAllTokens($user);

        $this->assertEquals(2, $revokedCount);
        $this->assertEquals(0, $this->tokenManager->getActiveTokenCount($user));
    }

    public function test_revoke_token_removes_specific_token(): void
    {
        $user = User::factory()->create();
        
        $token1 = $this->tokenManager->createToken($user, 'token-1');
        $token2 = $this->tokenManager->createToken($user, 'token-2');

        $token1Model = PersonalAccessToken::findToken($token1);
        $revoked = $this->tokenManager->revokeToken($user, $token1Model->id);

        $this->assertTrue($revoked);
        $this->assertEquals(1, $this->tokenManager->getActiveTokenCount($user));
        $this->assertNull(PersonalAccessToken::findToken($token1));
        $this->assertNotNull(PersonalAccessToken::findToken($token2));
    }

    public function test_find_token_returns_correct_token(): void
    {
        $user = User::factory()->create();
        $plainTextToken = $this->tokenManager->createToken($user, 'test-token');

        $foundToken = $this->tokenManager->findToken($plainTextToken);

        $this->assertNotNull($foundToken);
        $this->assertEquals($user->id, $foundToken->tokenable_id);
        $this->assertEquals('test-token', $foundToken->name);
    }

    public function test_find_token_returns_null_for_invalid_token(): void
    {
        $foundToken = $this->tokenManager->findToken('invalid-token');

        $this->assertNull($foundToken);
    }

    public function test_has_ability_checks_current_token_abilities(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        $token = $this->tokenManager->createToken($user, 'test-token');
        
        $tokenModel = PersonalAccessToken::findToken($token);
        $user->currentAccessToken = $tokenModel;

        $this->assertTrue($this->tokenManager->hasAbility($user, 'property:read'));
        $this->assertFalse($this->tokenManager->hasAbility($user, 'nonexistent:ability'));
    }

    public function test_prune_expired_tokens_removes_old_tokens(): void
    {
        $user = User::factory()->create();
        
        // Create expired token
        PersonalAccessToken::create([
            'tokenable_type' => User::class,
            'tokenable_id' => $user->id,
            'name' => 'expired-token',
            'token' => 'hash',
            'abilities' => ['*'],
            'expires_at' => now()->subDays(2),
            'created_at' => now()->subDays(3),
        ]);

        // Create active token
        $this->tokenManager->createToken($user, 'active-token');

        $prunedCount = $this->tokenManager->pruneExpiredTokens(24);

        $this->assertEquals(1, $prunedCount);
        $this->assertEquals(1, PersonalAccessToken::count());
    }

    public function test_token_statistics_returns_correct_data(): void
    {
        $user1 = User::factory()->create(['role' => UserRole::ADMIN]);
        $user2 = User::factory()->create(['role' => UserRole::TENANT]);
        
        $this->tokenManager->createToken($user1, 'admin-token');
        $this->tokenManager->createToken($user2, 'tenant-token');

        $stats = $this->tokenManager->getTokenStatistics();

        $this->assertEquals(2, $stats['total_tokens']);
        $this->assertEquals(2, $stats['active_tokens']);
        $this->assertEquals(0, $stats['expired_tokens']);
        $this->assertArrayHasKey('tokens_by_user_role', $stats);
    }

    public function test_caching_works_correctly(): void
    {
        $user = User::factory()->create();
        
        Cache::flush();
        
        // First call should cache
        $count1 = $this->tokenManager->getActiveTokenCount($user);
        
        // Second call should use cache
        $count2 = $this->tokenManager->getActiveTokenCount($user);
        
        $this->assertEquals($count1, $count2);
        $this->assertTrue(Cache::has('api_tokens:token_count:' . $user->id));
    }

    public function test_cache_is_cleared_on_token_operations(): void
    {
        $user = User::factory()->create();
        
        // Cache some data
        $this->tokenManager->getActiveTokenCount($user);
        $this->assertTrue(Cache::has('api_tokens:token_count:' . $user->id));
        
        // Create token should clear cache
        $this->tokenManager->createToken($user, 'test-token');
        $this->assertFalse(Cache::has('api_tokens:token_count:' . $user->id));
    }
}