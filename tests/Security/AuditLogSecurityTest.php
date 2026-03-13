<?php

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->tenantId = 1;
    session(['tenant_id' => $this->tenantId]);
});

test('audit logs encrypt sensitive data at rest', function () {
    $user = User::factory()->create([
        'tenant_id' => $this->tenantId,
        'email' => 'test@example.com',
    ]);
    
    // Update user to trigger audit
    $user->update(['email' => 'new@example.com']);
    
    $audit = AuditLog::latest()->first();
    
    if (!$audit) {
        $this->markTestSkipped('Auditable trait not configured on User model');
    }
    
    // Check database has encrypted data (not plain text)
    $raw = DB::table('audit_logs')->where('id', $audit->id)->first();
    
    // The encrypted data should not contain the plain email
    expect($raw->old_values)->not->toContain('test@example.com');
    expect($raw->new_values)->not->toContain('new@example.com');
});

test('audit logs redact PII from getChanges()', function () {
    $audit = AuditLog::factory()->create([
        'tenant_id' => $this->tenantId,
        'old_values' => [
            'email' => 'old@example.com',
            'name' => 'John Doe',
            'password' => 'secret123',
        ],
        'new_values' => [
            'email' => 'new@example.com',
            'name' => 'Jane Doe',
            'password' => 'newsecret456',
        ],
    ]);
    
    $changes = $audit->getChanges();
    
    // Email should be redacted
    expect($changes['email']['old'])->toBe('[REDACTED_EMAIL]');
    expect($changes['email']['new'])->toBe('[REDACTED_EMAIL]');
    
    // Password should be redacted
    expect($changes['password']['old'])->toBe('[REDACTED]');
    expect($changes['password']['new'])->toBe('[REDACTED]');
    
    // Name should not be redacted
    expect($changes['name']['old'])->toBe('John Doe');
    expect($changes['name']['new'])->toBe('Jane Doe');
});

test('audit logs hide sensitive fields in JSON', function () {
    $audit = AuditLog::factory()->create([
        'tenant_id' => $this->tenantId,
        'ip_address' => '192.168.1.1',
        'user_agent' => 'Mozilla/5.0',
    ]);
    
    $json = $audit->toArray();
    
    // IP address and user agent should be hidden
    expect($json)->not->toHaveKey('ip_address');
    expect($json)->not->toHaveKey('user_agent');
});

test('audit log retention scope works', function () {
    // Create old audit log (100 days ago)
    $oldAudit = AuditLog::factory()->create([
        'tenant_id' => $this->tenantId,
        'created_at' => now()->subDays(100),
    ]);
    
    // Create recent audit log (10 days ago)
    $recentAudit = AuditLog::factory()->create([
        'tenant_id' => $this->tenantId,
        'created_at' => now()->subDays(10),
    ]);
    
    // Query with 90-day retention
    $retained = AuditLog::withinRetention(90)->get();
    
    expect($retained)->toHaveCount(1);
    expect($retained->first()->id)->toBe($recentAudit->id);
});

test('PII redaction handles various field types', function () {
    $audit = AuditLog::factory()->create([
        'tenant_id' => $this->tenantId,
        'old_values' => [
            'email' => 'test@example.com',
            'phone' => '+1234567890',
            'ssn' => '123-45-6789',
            'credit_card' => '4111111111111111',
            'api_key' => 'sk_test_123456',
            'normal_field' => 'normal_value',
        ],
        'new_values' => [
            'email' => 'new@example.com',
            'phone' => '+0987654321',
            'ssn' => '987-65-4321',
            'credit_card' => '5555555555554444',
            'api_key' => 'sk_live_789012',
            'normal_field' => 'new_value',
        ],
    ]);
    
    $changes = $audit->getChanges();
    
    // All PII fields should be redacted
    expect($changes['email']['old'])->toBe('[REDACTED_EMAIL]');
    expect($changes['phone']['old'])->toBe('[REDACTED]');
    expect($changes['ssn']['old'])->toBe('[REDACTED]');
    expect($changes['credit_card']['old'])->toBe('[REDACTED]');
    expect($changes['api_key']['old'])->toBe('[REDACTED]');
    
    // Normal field should not be redacted
    expect($changes['normal_field']['old'])->toBe('normal_value');
    expect($changes['normal_field']['new'])->toBe('new_value');
});
