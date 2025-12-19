<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\UserRole;
use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Services\ApiTokenManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * User Model Custom Token Tests
 * 
 * Tests the User model's custom API token methods after removal of HasApiTokens trait.
 * Ensures proper delegation to ApiTokenManager service and backward compatibility.
 */
class UserModelCustomTokenTest extends TestCase
{
    use RefreshDatabase;

    private ApiTokenManager $mockTokenManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockTokenManager = Mockery::mock(ApiTokenManager::class);
        $this->app->instance(ApiTokenManager::class, $this->mockTokenManager);
    }

    public function test_create_api_token_delegates_to_token_manager(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        $expectedToken = '1|test-token-hash';

        $this->mockTokenManager
            ->shouldReceive('createToken')
            ->once()
            ->with($user, 'test-token', null)
            ->andReturn($expectedToken);

        $result = $user->createApiToken('test-token');

        $this->assertEquals($expectedToken, $result);
    }

    public function test_create_api_token_with_custom_abilities_delegates_correctly(): void
    {
        $user = User::factory()->create(['role' => UserRole::TENANT]);
        $customAbilities = ['meter-reading:read'];
        $expectedToken = '2|custom-token-hash';

        $this->mockTokenManager
            ->shouldReceive('createToken')
            ->once()
            ->with($user, 'custom-token', $customAbilities)
            ->andReturn($expectedToken);

        $result = $user->createApiToken('custom-token', $customAbilities);

        $this->assertEquals($expectedToken, $result);
    }

    public function test_revoke_all_api_tokens_delegates_to_token_manager(): void
    {
        $user = User::factory()->create();
        $expectedCount = 3;

        $this->mockTokenManager
            ->shouldReceive('revokeAllTokens')
            ->once()
            ->with($user)
            ->andReturn($expectedCount);

        $result = $user->revokeAllApiTokens();

        $this->assertEquals($expectedCount, $result);
    }

    public function test_get_active_tokens_count_delegates_to_token_manager(): void
    {
        $user = User::factory()->create();
        $expectedCount = 5;

        $this->mockTokenManager
            ->shouldReceive('getActiveTokenCount')
            ->once()
            ->with($user)
            ->andReturn($expectedCount);

        $result = $user->getActiveTokensCount();

        $this->assertEquals($expectedCount, $result);
    }

    public function test_has_api_ability_delegates_to_token_manager(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        $ability = 'property:write';

        $this->mockTokenManager
            ->shouldReceive('hasAbility')
            ->once()
            ->with($user, $ability)
            ->andReturn(true);

        $result = $user->hasApiAbility($ability);

        $this->assertTrue($result);
    }

    public function test_has_api_ability_returns_false_for_insufficient_permissions(): void
    {
        $user = User::factory()->create(['role' => UserRole::TENANT]);
        $ability = 'property:write';

        $this->mockTokenManager
            ->shouldReceive('hasAbility')
            ->once()
            ->with($user, $ability)
            ->andReturn(false);

        $result = $user->hasApiAbility($ability);

        $this->assertFalse($result);
    }

    public function test_current_access_token_returns_set_token(): void
    {
        $user = User::factory()->create();
        $token = PersonalAccessToken::factory()->create([
            'tokenable_type' => User::class,
            'tokenable_id' => $user->id,
        ]);

        $user->currentAccessToken = $token;

        $result = $user->currentAccessToken();

        $this->assertSame($token, $result);
    }

    public function test_current_access_token_returns_null_when_not_set(): void
    {
        $user = User::factory()->create();

        $result = $user->currentAccessToken();

        $this->assertNull($result);
    }

    public function test_create_token_sanctum_compatibility_method(): void
    {
        $user = User::factory()->create(['role' => UserRole::MANAGER]);
        $abilities = ['custom:ability'];
        $expiresAt = now()->addDays(30);
        $expectedToken = '3|sanctum-compat-token';

        $this->mockTokenManager
            ->shouldReceive('createToken')
            ->once()
            ->with($user, 'compat-token', $abilities, $expiresAt)
            ->andReturn($expectedToken);

        $result = $user->createToken('compat-token', $abilities, $expiresAt);

        $this->assertIsObject($result);
        $this->assertObjectHasProperty('plainTextToken', $result);
        $this->assertEquals($expectedToken, $result->plainTextToken);
    }

    public function test_create_token_with_default_abilities(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        $expectedToken = '4|default-abilities-token';

        $this->mockTokenManager
            ->shouldReceive('createToken')
            ->once()
            ->with($user, 'default-token', ['*'], null)
            ->andReturn($expectedToken);

        $result = $user->createToken('default-token');

        $this->assertEquals($expectedToken, $result->plainTextToken);
    }

    public function test_tokens_relationship_works_correctly(): void
    {
        $user = User::factory()->create();
        
        // Create tokens directly in database
        PersonalAccessToken::factory()->count(3)->create([
            'tokenable_type' => User::class,
            'tokenable_id' => $user->id,
        ]);

        $tokens = $user->tokens;

        $this->assertCount(3, $tokens);
        $this->assertInstanceOf(PersonalAccessToken::class, $tokens->first());
    }

    public function test_api_token_manager_is_memoized(): void
    {
        $user = User::factory()->create();

        // Call method multiple times
        $user->createApiToken('token-1');
        $user->createApiToken('token-2');
        $user->getActiveTokensCount();

        // Should only create one instance of ApiTokenManager
        $this->mockTokenManager
            ->shouldReceive('createToken')
            ->twice()
            ->andReturn('mock-token');
        
        $this->mockTokenManager
            ->shouldReceive('getActiveTokenCount')
            ->once()
            ->andReturn(2);
    }

    public function test_refresh_memoized_data_clears_token_manager(): void
    {
        $user = User::factory()->create();
        
        // Access the token manager to memoize it
        $user->createApiToken('test-token');
        
        // Refresh memoized data
        $user->refreshMemoizedData();
        
        // Next call should create a new instance
        $this->mockTokenManager
            ->shouldReceive('createToken')
            ->twice()
            ->andReturn('mock-token');
        
        $user->createApiToken('test-token-2');
    }

    public function test_suspend_method_revokes_all_tokens(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $user = User::factory()->create(['role' => UserRole::TENANT]);

        $this->mockTokenManager
            ->shouldReceive('revokeAllTokens')
            ->once()
            ->with($user)
            ->andReturn(2);

        $user->suspend('Policy violation', $admin);

        $this->assertNotNull($user->suspended_at);
        $this->assertEquals('Policy violation', $user->suspension_reason);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}