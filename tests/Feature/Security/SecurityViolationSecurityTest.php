<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Enums\SecuritySeverity;
use App\Enums\ThreatClassification;
use App\Enums\UserRole;
use App\Models\SecurityViolation;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Security Violation Security Tests
 * 
 * Tests security aspects of the security violation system including
 * authorization, data protection, and attack prevention.
 */
final class SecurityViolationSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthorized_users_cannot_access_violations(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => UserRole::TENANT,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/security/violations');

        $response->assertStatus(403);
    }

    public function test_users_cannot_access_other_tenant_violations(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();
        
        $user = User::factory()->create([
            'tenant_id' => $tenant1->id,
            'role' => UserRole::ADMIN,
        ]);

        $violation = SecurityViolation::factory()->create([
            'tenant_id' => $tenant2->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/security/violations/{$violation->id}");

        $response->assertStatus(404); // Should not find violation from other tenant
    }

    public function test_csp_violation_rate_limiting(): void
    {
        // Simulate rapid CSP violation reports
        for ($i = 0; $i < 60; $i++) {
            $response = $this->postJson('/api/csp-report', [
                'csp-report' => [
                    'violated-directive' => 'script-src',
                    'document-uri' => 'https://example.com/test',
                    'blocked-uri' => 'https://malicious.com/script.js',
                ],
            ]);

            if ($i < 50) {
                $response->assertStatus(201);
            } else {
                $response->assertStatus(429); // Rate limited
            }
        }
    }

    public function test_csp_violation_input_sanitization(): void
    {
        $maliciousPayload = [
            'csp-report' => [
                'violated-directive' => 'script-src',
                'document-uri' => 'javascript:alert("xss")',
                'blocked-uri' => '<script>alert("xss")</script>',
                'source-file' => 'data:text/html,<script>alert("xss")</script>',
                'line-number' => 'not-a-number',
                'column-number' => -1,
            ],
        ];

        $response = $this->postJson('/api/csp-report', $maliciousPayload);

        $response->assertStatus(422); // Validation should fail
    }

    public function test_sensitive_data_is_encrypted(): void
    {
        $violation = SecurityViolation::factory()->create([
            'blocked_uri' => 'https://example.com/sensitive-path',
            'metadata' => ['sensitive' => 'data'],
        ]);

        // Check that sensitive data is encrypted in database
        $rawData = \DB::table('security_violations')
            ->where('id', $violation->id)
            ->first();

        $this->assertNotEquals('https://example.com/sensitive-path', $rawData->blocked_uri);
        $this->assertStringStartsWith('eyJpdiI6', $rawData->blocked_uri); // Base64 encrypted data
    }

    public function test_security_analytics_requires_authentication(): void
    {
        $response = $this->getJson('/api/security/violations');
        $response->assertStatus(401);

        $response = $this->getJson('/api/security/metrics');
        $response->assertStatus(401);

        $response = $this->getJson('/api/security/dashboard');
        $response->assertStatus(401);
    }

    public function test_security_analytics_rate_limiting(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        // Test rate limiting on analytics endpoints
        for ($i = 0; $i < 70; $i++) {
            $response = $this->actingAs($user)
                ->getJson('/api/security/violations');

            if ($i < 60) {
                $this->assertContains($response->status(), [200, 403]); // 403 if no permission
            } else {
                $response->assertStatus(429); // Rate limited
            }
        }
    }

    public function test_malicious_csp_patterns_are_detected(): void
    {
        Log::shouldReceive('alert')->once();

        $maliciousReport = [
            'csp-report' => [
                'violated-directive' => 'script-src',
                'document-uri' => 'https://example.com/test',
                'blocked-uri' => 'javascript:eval(atob("YWxlcnQoJ1hTUycpOw=="))',
            ],
        ];

        $response = $this->postJson('/api/csp-report', $maliciousReport);

        // Should still process but log as potential attack
        $response->assertStatus(201);
    }

    public function test_violation_data_is_properly_sanitized(): void
    {
        $violation = SecurityViolation::factory()->create([
            'blocked_uri' => 'https://example.com/path?token=secret123',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ]);

        // User agent should be hashed, not stored in plain text
        $this->assertNotEquals(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            $violation->user_agent
        );
    }

    public function test_superadmin_can_access_all_tenant_violations(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();
        
        $superadmin = User::factory()->create([
            'role' => UserRole::SUPERADMIN,
        ]);

        SecurityViolation::factory()->create(['tenant_id' => $tenant1->id]);
        SecurityViolation::factory()->create(['tenant_id' => $tenant2->id]);

        $response = $this->actingAs($superadmin)
            ->getJson('/api/security/violations');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    public function test_violation_export_requires_proper_authorization(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::MANAGER,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/security/export');

        $response->assertStatus(403);
    }

    public function test_csp_violation_content_length_limit(): void
    {
        $largePayload = [
            'csp-report' => [
                'violated-directive' => 'script-src',
                'document-uri' => 'https://example.com/test',
                'blocked-uri' => str_repeat('a', 15000), // Exceeds 10KB limit
            ],
        ];

        $response = $this->postJson('/api/csp-report', $largePayload);

        $response->assertStatus(413); // Request too large
    }

    public function test_security_headers_are_applied_to_security_endpoints(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/security/violations');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options');
        $response->assertHeader('Content-Security-Policy');
    }
}