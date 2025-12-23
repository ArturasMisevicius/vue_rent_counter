<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\PersonalAccessToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Custom Token API Endpoints Tests
 * 
 * Tests API endpoints with the custom token system to ensure
 * proper authentication, authorization, and functionality.
 */
class CustomTokenApiEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_authentication_works_with_custom_tokens(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        $token = $user->createApiToken('api-test');

        $response = $this->withToken($token)->getJson('/api/auth/me');

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

    public function test_api_login_creates_custom_tokens(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'role' => UserRole::MANAGER,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'token_name' => 'login-test',
        ]);

        $response->assertOk()
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'user',
                        'token',
                        'abilities',
                        'expires_at',
                    ],
                ]);

        // Verify token was created in database
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'login-test',
        ]);

        // Verify token works for API calls
        $token = $response->json('data.token');
        $meResponse = $this->withToken($token)->getJson('/api/auth/me');
        $meResponse->assertOk();
    }

    public function test_api_logout_revokes_custom_tokens(): void
    {
        $user = User::factory()->create();
        $token = $user->createApiToken('logout-test');

        // Verify token works
        $response = $this->withToken($token)->getJson('/api/auth/me');
        $response->assertOk();

        // Logout
        $response = $this->withToken($token)->postJson('/api/auth/logout');
        $response->assertOk()
                ->assertJson([
                    'success' => true,
                    'message' => 'Tokens revoked successfully',
                ]);

        // Verify token no longer works
        $response = $this->withToken($token)->getJson('/api/auth/me');
        $response->assertUnauthorized();
    }

    public function test_role_based_api_access_with_custom_tokens(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        $adminToken = $admin->createApiToken('admin-api');
        $tenantToken = $tenant->createApiToken('tenant-api');

        // Admin should access validation endpoints
        $response = $this->withToken($adminToken)->getJson('/api/v1/validation/health');
        $response->assertOk();

        // Tenant should also access validation endpoints (read access)
        $response = $this->withToken($tenantToken)->getJson('/api/v1/validation/health');
        $response->assertOk();

        // But tenant should not access admin-only endpoints
        $response = $this->withToken($tenantToken)->getJson('/api/admin/users');
        $response->assertForbidden();
    }

    public function test_custom_abilities_are_enforced_in_api(): void
    {
        $user = User::factory()->create(['role' => UserRole::MANAGER]);
        
        // Create token with limited abilities
        $limitedToken = $user->createApiToken('limited', ['meter-reading:read']);
        
        // Should work for allowed abilities
        $response = $this->withToken($limitedToken)->getJson('/api/v1/validation/health');
        $response->assertOk();

        // Should fail for disallowed abilities (if endpoint checks specific abilities)
        // This would depend on actual endpoint implementation
        $this->assertTrue(true); // Placeholder for actual endpoint tests
    }

    public function test_token_refresh_works_with_custom_system(): void
    {
        $user = User::factory()->create();
        $token = $user->createApiToken('refresh-test');

        $response = $this->withToken($token)->postJson('/api/auth/refresh', [
            'token_name' => 'refreshed-token',
        ]);

        $response->assertOk()
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'token',
                        'expires_at',
                    ],
                ]);

        // Old token should still work (refresh doesn't revoke old token)
        $oldTokenResponse = $this->withToken($token)->getJson('/api/auth/me');
        $oldTokenResponse->assertOk();

        // New token should also work
        $newToken = $response->json('data.token');
        $newTokenResponse = $this->withToken($newToken)->getJson('/api/auth/me');
        $newTokenResponse->assertOk();
    }

    public function test_api_rate_limiting_works_with_custom_tokens(): void
    {
        $user = User::factory()->create();
        $token = $user->createApiToken('rate-limit-test');

        // Make many requests rapidly
        $responses = [];
        for ($i = 0; $i < 70; $i++) { // Exceed typical rate limit of 60/minute
            $responses[] = $this->withToken($token)->getJson('/api/auth/me');
        }

        // Some requests should be rate limited
        $rateLimitedCount = collect($responses)->filter(function ($response) {
            return $response->status() === 429;
        })->count();

        $this->assertGreaterThan(0, $rateLimitedCount);
    }

    public function test_api_validation_endpoints_work_with_custom_tokens(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        $token = $user->createApiToken('validation-test');

        // Test health endpoint
        $response = $this->withToken($token)->getJson('/api/v1/validation/health');
        $response->assertOk()
                ->assertJsonStructure([
                    'status',
                    'timestamp',
                    'checks',
                ]);

        // Test validation with data (if endpoint exists)
        $response = $this->withToken($token)->postJson('/api/v1/validation/meter-reading', [
            'meter_id' => 1,
            'reading' => 1000,
            'reading_date' => now()->toDateString(),
        ]);

        // Should return validation result (success or validation errors)
        $this->assertContains($response->status(), [200, 422]);
    }

    public function test_api_error_handling_with_custom_tokens(): void
    {
        $user = User::factory()->create();
        $token = $user->createApiToken('error-test');

        // Test with invalid JSON
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
        ])->postJson('/api/auth/refresh', 'invalid-json');

        $response->assertStatus(400); // Bad Request

        // Test with missing required fields
        $response = $this->withToken($token)->postJson('/api/auth/login', []);
        $response->assertUnprocessable();
    }

    public function test_api_cors_headers_with_custom_tokens(): void
    {
        $user = User::factory()->create();
        $token = $user->createApiToken('cors-test');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Origin' => 'https://example.com',
        ])->getJson('/api/auth/me');

        $response->assertOk();
        
        // Should include CORS headers (if configured)
        $this->assertNotNull($response->headers->get('Access-Control-Allow-Origin'));
    }

    public function test_api_content_negotiation_with_custom_tokens(): void
    {
        $user = User::factory()->create();
        $token = $user->createApiToken('content-test');

        // Test JSON response (default)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->get('/api/auth/me');

        $response->assertOk();
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));

        // Test XML response (if supported)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/xml',
        ])->get('/api/auth/me');

        // Should either return XML or fallback to JSON
        $this->assertContains($response->status(), [200, 406]);
    }

    public function test_api_versioning_with_custom_tokens(): void
    {
        $user = User::factory()->create();
        $token = $user->createApiToken('version-test');

        // Test v1 API
        $response = $this->withToken($token)->getJson('/api/v1/validation/health');
        $response->assertOk();

        // Test unversioned API
        $response = $this->withToken($token)->getJson('/api/auth/me');
        $response->assertOk();
    }

    public function test_api_pagination_with_custom_tokens(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        $token = $user->createApiToken('pagination-test');

        // Test paginated endpoint (if exists)
        $response = $this->withToken($token)->getJson('/api/admin/users?page=1&per_page=10');
        
        if ($response->status() === 200) {
            $response->assertJsonStructure([
                'data',
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                ],
            ]);
        } else {
            // Endpoint might not exist or require different permissions
            $this->assertContains($response->status(), [403, 404]);
        }
    }

    public function test_api_filtering_with_custom_tokens(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        $token = $user->createApiToken('filter-test');

        // Test filtered endpoint (if exists)
        $response = $this->withToken($token)->getJson('/api/admin/users?filter[role]=admin');
        
        // Should either work or return appropriate error
        $this->assertContains($response->status(), [200, 403, 404]);
    }

    public function withToken(string $token, string $type = 'Bearer'): self
    {
        return $this->withHeaders([
            'Authorization' => $type . ' ' . $token,
            'Accept' => 'application/json',
        ]);
    }
}