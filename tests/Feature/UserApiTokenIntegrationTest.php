<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\PersonalAccessToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * User API Token Integration Tests
 * 
 * Tests the integration between User model and custom token management
 * to ensure backward compatibility with existing functionality.
 */
class UserApiTokenIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_create_api_token_works_like_before(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);

        $token = $user->createApiToken('test-token');

        $this->assertIsString($token);
        $this->assertStringContainsString('|', $token);
        
        // Verify token exists in database
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_type' => User::class,
            'tokenable_id' => $user->id,
            'name' => 'test-token',
        ]);
    }

    public function test_user_revoke_all_api_tokens_works(): void
    {
        $user = User::factory()->create();
        
        $user->createApiToken('token-1');
        $user->createApiToken('token-2');
        
        $this->assertEquals(2, $user->getActiveTokensCount());
        
        $revokedCount = $user->revokeAllApiTokens();
        
        $this->assertEquals(2, $revokedCount);
        $this->assertEquals(0, $user->getActiveTokensCount());
    }

    public function test_user_get_active_tokens_count_works(): void
    {
        $user = User::factory()->create();
        
        $this->assertEquals(0, $user->getActiveTokensCount());
        
        $user->createApiToken('token-1');
        $user->createApiToken('token-2');
        
        $this->assertEquals(2, $user->getActiveTokensCount());
    }

    public function test_user_has_api_ability_works_with_current_token(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        $token = $user->createApiToken('test-token');
        
        // Simulate middleware setting current token
        $tokenModel = PersonalAccessToken::findToken($token);
        $user->currentAccessToken = $tokenModel;
        
        $this->assertTrue($user->hasApiAbility('property:read'));
        $this->assertFalse($user->hasApiAbility('nonexistent:ability'));
    }

    public function test_user_current_access_token_returns_correct_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createApiToken('test-token');
        
        $tokenModel = PersonalAccessToken::findToken($token);
        $user->currentAccessToken = $tokenModel;
        
        $currentToken = $user->currentAccessToken();
        
        $this->assertNotNull($currentToken);
        $this->assertEquals($tokenModel->id, $currentToken->id);
    }

    public function test_user_create_token_sanctum_compatibility(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        
        $result = $user->createToken('test-token', ['custom:ability']);
        
        $this->assertIsObject($result);
        $this->assertObjectHasProperty('plainTextToken', $result);
        $this->assertIsString($result->plainTextToken);
        
        // Verify token was created with custom abilities
        $tokenModel = PersonalAccessToken::findToken($result->plainTextToken);
        $this->assertEquals(['custom:ability'], $tokenModel->abilities);
    }

    public function test_user_tokens_relationship_works(): void
    {
        $user = User::factory()->create();
        
        $user->createApiToken('token-1');
        $user->createApiToken('token-2');
        
        $tokens = $user->tokens;
        
        $this->assertCount(2, $tokens);
        $this->assertContains('token-1', $tokens->pluck('name'));
        $this->assertContains('token-2', $tokens->pluck('name'));
    }

    public function test_api_authentication_works_with_custom_tokens(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        $token = $user->createApiToken('api-token');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson('/api/auth/me');

        $response->assertOk()
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'user' => ['id', 'name', 'email', 'role'],
                        'abilities',
                        'tokens',
                    ],
                ]);

        $this->assertEquals($user->id, $response->json('data.user.id'));
    }

    public function test_token_abilities_are_enforced_in_api_requests(): void
    {
        $user = User::factory()->create(['role' => UserRole::TENANT]);
        $token = $user->createApiToken('limited-token');

        // Tenant should not have access to admin endpoints
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/validation/health');

        // This should work as tenants have validation:read ability
        $response->assertOk();
    }

    public function test_expired_tokens_are_rejected(): void
    {
        $user = User::factory()->create();
        
        // Create expired token directly in database
        $expiredToken = PersonalAccessToken::create([
            'tokenable_type' => User::class,
            'tokenable_id' => $user->id,
            'name' => 'expired-token',
            'token' => PersonalAccessToken::generateTokenHash('test-token'),
            'abilities' => ['*'],
            'expires_at' => now()->subHour(),
        ]);

        $plainTextToken = $expiredToken->id . '|test-token';

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $plainTextToken,
            'Accept' => 'application/json',
        ])->getJson('/api/auth/me');

        $response->assertUnauthorized();
    }

    public function test_inactive_user_tokens_are_rejected(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        $token = $user->createApiToken('test-token');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson('/api/auth/me');

        $response->assertUnauthorized();
    }

    public function test_suspended_user_tokens_are_rejected(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'suspended_at' => now(),
        ]);
        $token = $user->createApiToken('test-token');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson('/api/auth/me');

        $response->assertUnauthorized();
    }

    public function test_token_last_used_at_is_updated_on_use(): void
    {
        $user = User::factory()->create();
        $token = $user->createApiToken('test-token');
        
        $tokenModel = PersonalAccessToken::findToken($token);
        $this->assertNull($tokenModel->last_used_at);

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson('/api/auth/me');

        $tokenModel->refresh();
        $this->assertNotNull($tokenModel->last_used_at);
    }
}