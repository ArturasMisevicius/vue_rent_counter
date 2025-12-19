<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\PersonalAccessToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Personal Access Token Model Tests
 * 
 * Tests the custom PersonalAccessToken model functionality.
 */
class PersonalAccessTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_find_token_returns_correct_token(): void
    {
        $user = User::factory()->create();
        $result = PersonalAccessToken::createToken($user, 'test-token', ['*']);
        
        $foundToken = PersonalAccessToken::findToken($result['plainTextToken']);
        
        $this->assertNotNull($foundToken);
        $this->assertEquals($result['accessToken']->id, $foundToken->id);
    }

    public function test_find_token_returns_null_for_invalid_format(): void
    {
        $foundToken = PersonalAccessToken::findToken('invalid-format');
        
        $this->assertNull($foundToken);
    }

    public function test_find_token_returns_null_for_wrong_hash(): void
    {
        $user = User::factory()->create();
        $result = PersonalAccessToken::createToken($user, 'test-token', ['*']);
        
        // Modify the token part
        $parts = explode('|', $result['plainTextToken']);
        $wrongToken = $parts[0] . '|wrong-token';
        
        $foundToken = PersonalAccessToken::findToken($wrongToken);
        
        $this->assertNull($foundToken);
    }

    public function test_can_method_checks_abilities_correctly(): void
    {
        $user = User::factory()->create();
        $result = PersonalAccessToken::createToken($user, 'test-token', ['read', 'write']);
        
        $token = $result['accessToken'];
        
        $this->assertTrue($token->can('read'));
        $this->assertTrue($token->can('write'));
        $this->assertFalse($token->can('delete'));
    }

    public function test_can_method_works_with_wildcard(): void
    {
        $user = User::factory()->create();
        $result = PersonalAccessToken::createToken($user, 'test-token', ['*']);
        
        $token = $result['accessToken'];
        
        $this->assertTrue($token->can('any-ability'));
        $this->assertTrue($token->can('another-ability'));
    }

    public function test_cant_method_is_opposite_of_can(): void
    {
        $user = User::factory()->create();
        $result = PersonalAccessToken::createToken($user, 'test-token', ['read']);
        
        $token = $result['accessToken'];
        
        $this->assertFalse($token->cant('read'));
        $this->assertTrue($token->cant('write'));
    }

    public function test_is_expired_returns_correct_status(): void
    {
        $user = User::factory()->create();
        
        // Active token
        $activeResult = PersonalAccessToken::createToken($user, 'active-token', ['*'], now()->addHour());
        $this->assertFalse($activeResult['accessToken']->isExpired());
        
        // Expired token
        $expiredResult = PersonalAccessToken::createToken($user, 'expired-token', ['*'], now()->subHour());
        $this->assertTrue($expiredResult['accessToken']->isExpired());
        
        // Token without expiration
        $neverExpiresResult = PersonalAccessToken::createToken($user, 'never-expires', ['*'], null);
        $this->assertFalse($neverExpiresResult['accessToken']->isExpired());
    }

    public function test_mark_as_used_updates_timestamp(): void
    {
        $user = User::factory()->create();
        $result = PersonalAccessToken::createToken($user, 'test-token', ['*']);
        
        $token = $result['accessToken'];
        $this->assertNull($token->last_used_at);
        
        $token->markAsUsed();
        $token->refresh();
        
        $this->assertNotNull($token->last_used_at);
    }

    public function test_tokenable_relationship_works(): void
    {
        $user = User::factory()->create();
        $result = PersonalAccessToken::createToken($user, 'test-token', ['*']);
        
        $token = $result['accessToken'];
        
        $this->assertEquals($user->id, $token->tokenable->id);
        $this->assertInstanceOf(User::class, $token->tokenable);
    }

    public function test_active_scope_filters_correctly(): void
    {
        $user = User::factory()->create();
        
        // Create active token
        PersonalAccessToken::createToken($user, 'active-token', ['*'], now()->addHour());
        
        // Create expired token
        PersonalAccessToken::createToken($user, 'expired-token', ['*'], now()->subHour());
        
        // Create token without expiration
        PersonalAccessToken::createToken($user, 'never-expires', ['*'], null);
        
        $activeTokens = PersonalAccessToken::active()->get();
        
        $this->assertCount(2, $activeTokens); // active + never expires
    }

    public function test_expired_scope_filters_correctly(): void
    {
        $user = User::factory()->create();
        
        // Create active token
        PersonalAccessToken::createToken($user, 'active-token', ['*'], now()->addHour());
        
        // Create expired token
        PersonalAccessToken::createToken($user, 'expired-token', ['*'], now()->subHour());
        
        $expiredTokens = PersonalAccessToken::expired()->get();
        
        $this->assertCount(1, $expiredTokens);
        $this->assertEquals('expired-token', $expiredTokens->first()->name);
    }

    public function test_recently_used_scope_filters_correctly(): void
    {
        $user = User::factory()->create();
        
        // Create token and mark as recently used
        $recentResult = PersonalAccessToken::createToken($user, 'recent-token', ['*']);
        $recentResult['accessToken']->update(['last_used_at' => now()->subDays(5)]);
        
        // Create token and mark as old usage
        $oldResult = PersonalAccessToken::createToken($user, 'old-token', ['*']);
        $oldResult['accessToken']->update(['last_used_at' => now()->subDays(35)]);
        
        $recentTokens = PersonalAccessToken::recentlyUsed(30)->get();
        
        $this->assertCount(1, $recentTokens);
        $this->assertEquals('recent-token', $recentTokens->first()->name);
    }

    public function test_get_abilities_returns_correct_array(): void
    {
        $user = User::factory()->create();
        $abilities = ['read', 'write', 'delete'];
        $result = PersonalAccessToken::createToken($user, 'test-token', $abilities);
        
        $token = $result['accessToken'];
        
        $this->assertEquals($abilities, $token->getAbilities());
    }

    public function test_generate_token_hash_creates_consistent_hash(): void
    {
        $plainText = 'test-token-string';
        
        $hash1 = PersonalAccessToken::generateTokenHash($plainText);
        $hash2 = PersonalAccessToken::generateTokenHash($plainText);
        
        $this->assertEquals($hash1, $hash2);
        $this->assertEquals(64, strlen($hash1)); // SHA256 hash length
    }
}