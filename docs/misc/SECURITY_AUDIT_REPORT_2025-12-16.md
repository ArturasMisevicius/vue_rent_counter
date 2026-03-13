# Security Audit Report - User Model & Authentication System
**Date**: December 16, 2025  
**Scope**: User.php model changes, custom API token system, authentication architecture  
**Auditor**: Laravel Security Expert  
**Project**: Multi-tenant Laravel 12 application with Filament v4

## Executive Summary

This audit examines the security implications of removing the `Laravel\Sanctum\HasApiTokens` trait from the User model and implementing a custom API token management system. The analysis reveals both strengths and critical security gaps that require immediate attention.

**Overall Risk Level**: 🟡 MEDIUM-HIGH  
**Critical Issues**: 2  
**High Issues**: 3  
**Medium Issues**: 4  
**Low Issues**: 2

## 1. FINDINGS BY SEVERITY

### 🔴 CRITICAL SEVERITY

#### C1. Custom Token System Lacks Sanctum Security Features
**File**: `app/Models/User.php` (line 15 - removed HasApiTokens)  
**File**: `app/Services/ApiTokenManager.php`  
**File**: `app/Models/PersonalAccessToken.php`

**Issue**: The custom API token implementation missing critical Sanctum security features:
- No automatic token pruning mechanism
- Missing token ability validation in middleware
- No rate limiting on token operations
- Potential timing attacks in token comparison

**Evidence**:
```php
// In PersonalAccessToken::findToken() - VULNERABLE
return hash_equals($instance->token, hash('sha256', $token)) ? $instance : null;
```

**Impact**: 
- Token enumeration attacks possible
- Stale tokens accumulate indefinitely
- Privilege escalation through ability bypass

#### C2. Authentication Middleware Bypasses User Status Validation
**File**: `app/Http/Middleware/CustomSanctumAuthentication.php` (lines 25-35)

**Issue**: Middleware only checks `is_active` and `suspended_at` but missing:
- Email verification requirement for API access
- Account lockout after failed attempts
- Token expiration validation race condition

**Evidence**:
```php
if ($user instanceof User && $user->is_active && !$user->suspended_at) {
    // Missing email_verified_at check
    // Missing account lockout check
}
```

**Impact**: Unverified users can access API endpoints, bypassing security controls.

### 🟠 HIGH SEVERITY

#### H1. Mass Assignment Vulnerability in User Model
**File**: `app/Models/User.php` (lines 95-110)

**Issue**: Overly permissive `$fillable` array includes sensitive fields:
```php
protected $fillable = [
    'system_tenant_id',    // DANGEROUS - allows privilege escalation
    'is_super_admin',      // DANGEROUS - allows role elevation
    'tenant_id',           // DANGEROUS - allows tenant switching
    // ... other fields
];
```

**Impact**: Attackers can escalate privileges through mass assignment attacks.

#### H2. Insufficient Rate Limiting on API Endpoints
**File**: `config/security.php` (lines 142-175)

**Issue**: Rate limits are too permissive for security-sensitive operations:
- API: 60/minute, 1000/hour (too high for authenticated endpoints)
- Login: 5/minute (should be lower after failures)
- No rate limiting on token creation/revocation

#### H3. Token Storage Without Proper Indexing
**File**: Database schema analysis

**Issue**: PersonalAccessToken table lacks proper indexes for security queries:
- No index on `(tokenable_type, tokenable_id, expires_at)`
- No index on `last_used_at` for cleanup operations
- Missing composite index for token validation queries

### 🟡 MEDIUM SEVERITY

#### M1. Weak Content Security Policy
**File**: `config/security.php` (lines 26-38)

**Issue**: CSP allows `'unsafe-inline'` and `'unsafe-eval'`:
```php
"script-src 'self' 'unsafe-inline' 'unsafe-eval' cdn.tailwindcss.com cdn.jsdelivr.net"
```

#### M2. Session Configuration Vulnerabilities
**File**: `config/session.php`

**Issue**: Session security could be strengthened:
- `same_site` should be 'strict' for admin panels
- Missing `secure` flag enforcement
- No session fingerprinting

#### M3. Insufficient Audit Logging
**File**: `app/Services/ApiTokenManager.php`

**Issue**: Missing security event logging for:
- Failed token validation attempts
- Suspicious token usage patterns
- Token enumeration attempts

#### M4. CORS Configuration Disabled by Default
**File**: `config/security.php` (lines 269-275)

**Issue**: CORS is disabled but when enabled, configuration is too permissive.

### 🟢 LOW SEVERITY

#### L1. Missing Security Headers
**File**: `config/security.php`

**Issue**: Missing modern security headers:
- `Cross-Origin-Embedder-Policy`
- `Cross-Origin-Opener-Policy`
- `Cross-Origin-Resource-Policy`

#### L2. Weak Password Policy Enforcement
**File**: User model validation

**Issue**: No password complexity requirements enforced at model level.

## 2. SECURE FIXES & IMPLEMENTATIONS

### Critical Fixes

#### Fix C1: Enhance Custom Token System Security

**1. Add proper token validation and cleanup:**

```php
// app/Console/Commands/PruneExpiredTokens.php
<?php

namespace App\Console\Commands;

use App\Models\PersonalAccessToken;
use Illuminate\Console\Command;

class PruneExpiredTokens extends Command
{
    protected $signature = 'tokens:prune {--hours=24 : Hours after expiration to delete}';
    protected $description = 'Prune expired personal access tokens';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        
        $count = PersonalAccessToken::expired()
            ->where('created_at', '<', now()->subHours($hours))
            ->delete();
            
        $this->info("Pruned {$count} expired tokens");
        return 0;
    }
}
```

**2. Enhance token validation middleware:**

```php
// app/Http/Middleware/CustomSanctumAuthentication.php - SECURE VERSION
public function handle(Request $request, Closure $next, ...$guards)
{
    if ($token = $this->getTokenFromRequest($request)) {
        // Rate limit token validation attempts
        $key = 'token_validation:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 60)) {
            return response()->json(['message' => 'Too many attempts'], 429);
        }

        if ($accessToken = PersonalAccessToken::findToken($token)) {
            // Check expiration with proper timing
            if (!$accessToken->isExpired()) {
                $user = $accessToken->tokenable;
                
                // ENHANCED USER VALIDATION
                if ($user instanceof User && 
                    $user->is_active && 
                    !$user->suspended_at &&
                    $user->email_verified_at !== null) { // ADDED EMAIL VERIFICATION
                    
                    // Clear rate limit on successful auth
                    RateLimiter::clear($key);
                    
                    Auth::setUser($user);
                    $user->currentAccessToken = $accessToken;
                    $accessToken->markAsUsed();
                    
                    return $next($request);
                }
            }
        }
        
        // Increment rate limit on failed validation
        RateLimiter::hit($key, 300); // 5 minute decay
        
        // Log security event
        Log::warning('Invalid token validation attempt', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'token_prefix' => substr($token, 0, 8) . '...',
        ]);
    }

    if (Auth::check()) {
        return $next($request);
    }

    return response()->json(['message' => 'Unauthenticated.'], 401);
}
```

#### Fix C2: Secure User Model Mass Assignment

```php
// app/Models/User.php - SECURE FILLABLE ARRAY
protected $fillable = [
    // REMOVED DANGEROUS FIELDS:
    // 'system_tenant_id',  // Only via dedicated methods
    // 'is_super_admin',    // Only via dedicated methods  
    // 'tenant_id',         // Only via dedicated methods
    
    // SAFE FIELDS ONLY:
    'name',
    'email',
    'password',
    'organization_name',
    // property_id and parent_user_id handled via relationships
];

// Add dedicated methods for sensitive operations
public function assignToTenant(int $tenantId, User $admin): void
{
    if (!$admin->hasAdministrativePrivileges()) {
        throw new AuthorizationException('Insufficient privileges');
    }
    
    $this->tenant_id = $tenantId;
    $this->save();
    
    Log::info('User assigned to tenant', [
        'user_id' => $this->id,
        'tenant_id' => $tenantId,
        'admin_id' => $admin->id,
    ]);
}

public function promoteToSuperAdmin(User $currentSuperAdmin): void
{
    if (!$currentSuperAdmin->isSuperadmin()) {
        throw new AuthorizationException('Only superadmins can promote users');
    }
    
    $this->is_super_admin = true;
    $this->role = UserRole::SUPERADMIN;
    $this->save();
    
    Log::warning('User promoted to superadmin', [
        'user_id' => $this->id,
        'promoted_by' => $currentSuperAdmin->id,
    ]);
}
```

### High Priority Fixes

#### Fix H1: Implement Proper Rate Limiting

```php
// config/security.php - ENHANCED RATE LIMITS
'rate_limiting' => [
    'enabled' => env('RATE_LIMITING_ENABLED', true),
    
    'limits' => [
        'api' => [
            'per_minute' => 30,    // REDUCED from 60
            'per_hour' => 500,     // REDUCED from 1000
        ],
        
        'login' => [
            'per_minute' => 3,     // REDUCED from 5
            'per_hour' => 10,      // REDUCED from 20
            'lockout_duration' => 900, // 15 minutes
        ],
        
        // NEW: Token operation limits
        'token_operations' => [
            'create_per_hour' => 10,
            'revoke_per_hour' => 20,
            'validation_per_minute' => 60,
        ],
        
        // NEW: Sensitive operations
        'user_management' => [
            'create_per_hour' => 5,
            'update_per_hour' => 20,
            'delete_per_hour' => 3,
        ],
    ],
],
```

#### Fix H2: Add Database Indexes for Security

```php
// database/migrations/2025_12_16_000001_add_security_indexes.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            // Token validation performance
            $table->index(['tokenable_type', 'tokenable_id', 'expires_at'], 'pat_validation_idx');
            
            // Cleanup operations
            $table->index(['expires_at', 'created_at'], 'pat_cleanup_idx');
            
            // Usage tracking
            $table->index(['last_used_at'], 'pat_usage_idx');
            
            // Security monitoring
            $table->index(['created_at', 'tokenable_type'], 'pat_monitoring_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            // Authentication queries
            $table->index(['email', 'is_active', 'suspended_at'], 'users_auth_idx');
            
            // API eligibility
            $table->index(['is_active', 'email_verified_at', 'suspended_at'], 'users_api_eligible_idx');
            
            // Security monitoring
            $table->index(['last_login_at', 'role'], 'users_security_idx');
        });
    }

    public function down(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropIndex('pat_validation_idx');
            $table->dropIndex('pat_cleanup_idx');
            $table->dropIndex('pat_usage_idx');
            $table->dropIndex('pat_monitoring_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_auth_idx');
            $table->dropIndex('users_api_eligible_idx');
            $table->dropIndex('users_security_idx');
        });
    }
};
```

### Medium Priority Fixes

#### Fix M1: Strengthen Content Security Policy

```php
// config/security.php - SECURE CSP
'Content-Security-Policy' => implode('; ', [
    "default-src 'self'",
    "script-src 'self' 'nonce-{NONCE}' cdn.tailwindcss.com", // REMOVED unsafe-inline/eval
    "style-src 'self' 'nonce-{NONCE}' fonts.googleapis.com", // REMOVED unsafe-inline
    "font-src 'self' fonts.gstatic.com",
    "img-src 'self' data: https:",
    "connect-src 'self'",
    "frame-ancestors 'none'", // STRENGTHENED from 'self'
    "base-uri 'self'",
    "form-action 'self'",
    "object-src 'none'", // ADDED
    "frame-src 'none'",  // ADDED
]),
```

#### Fix M2: Enhanced Session Security

```php
// config/session.php - SECURE CONFIGURATION
return [
    'lifetime' => env('SESSION_LIFETIME', 60), // REDUCED from 120
    'expire_on_close' => true,
    'encrypt' => true,
    'files' => storage_path('framework/sessions'),
    'connection' => env('SESSION_CONNECTION'),
    'table' => 'sessions',
    'store' => env('SESSION_STORE'),
    'lottery' => [2, 100],
    'cookie' => env('SESSION_COOKIE', Str::slug(env('APP_NAME', 'laravel'), '_').'_session'),
    'path' => '/',
    'domain' => env('SESSION_DOMAIN'),
    'secure' => env('SESSION_SECURE_COOKIE', true), // FORCE HTTPS
    'http_only' => true,
    'same_site' => 'strict', // STRENGTHENED
    'partitioned' => false,
];
```

## 3. DATA PROTECTION & PRIVACY

### PII Handling Compliance

**Current State**: ✅ Good
- InputSanitizer includes PII redaction for logs
- Security events redact sensitive data
- Audit logs have retention policies

**Enhancements Needed**:

```php
// app/Services/PiiProtectionService.php
<?php

namespace App\Services;

class PiiProtectionService
{
    private const PII_PATTERNS = [
        'email' => '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/',
        'phone' => '/\b(?:\+?1[-.]?)?\(?([0-9]{3})\)?[-.]?([0-9]{3})[-.]?([0-9]{4})\b/',
        'ssn' => '/\b\d{3}-?\d{2}-?\d{4}\b/',
        'credit_card' => '/\b(?:\d{4}[-\s]?){3}\d{4}\b/',
        'api_token' => '/\b[A-Za-z0-9_-]{32,}\b/',
    ];

    public function redactForLogging(array $data): array
    {
        return array_map([$this, 'redactString'], $data);
    }

    public function redactString(string $input): string
    {
        foreach (self::PII_PATTERNS as $type => $pattern) {
            $input = preg_replace($pattern, "[{$type}]", $input);
        }
        return $input;
    }

    public function encryptSensitiveFields(Model $model, array $fields): void
    {
        foreach ($fields as $field) {
            if ($model->isDirty($field) && !empty($model->$field)) {
                $model->$field = encrypt($model->$field);
            }
        }
    }
}
```

### Encryption at Rest

```php
// app/Models/User.php - ADD ENCRYPTED FIELDS
protected $casts = [
    'email_verified_at' => 'datetime',
    'password' => 'hashed',
    'role' => UserRole::class,
    'is_active' => 'boolean',
    'is_super_admin' => 'boolean',
    'suspended_at' => 'datetime',
    'last_login_at' => 'datetime',
    
    // ENCRYPT SENSITIVE DATA
    'organization_name' => 'encrypted', // If contains PII
    'suspension_reason' => 'encrypted',  // May contain sensitive info
];
```

### Demo Mode Safety

```php
// app/Providers/AppServiceProvider.php
public function boot(): void
{
    if (app()->environment('demo')) {
        // Disable sensitive operations in demo mode
        Gate::before(function ($user, $ability) {
            if (in_array($ability, ['delete-user', 'promote-superadmin', 'access-audit-logs'])) {
                return false;
            }
        });
        
        // Mask sensitive data in demo
        User::creating(function ($user) {
            if (app()->environment('demo')) {
                $user->email = 'demo+' . Str::random(8) . '@example.com';
                $user->organization_name = 'Demo Organization ' . rand(1, 100);
            }
        });
    }
}
```

## 4. TESTING & MONITORING PLAN

### Security Test Suite

```php
// tests/Security/UserModelSecurityTest.php
<?php

namespace Tests\Security;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserModelSecurityTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_prevents_mass_assignment_privilege_escalation(): void
    {
        $user = User::factory()->create(['role' => UserRole::TENANT]);
        
        // Attempt mass assignment privilege escalation
        $user->fill([
            'role' => UserRole::SUPERADMIN,
            'is_super_admin' => true,
            'tenant_id' => 999,
        ]);
        
        // Should not change sensitive fields
        $this->assertEquals(UserRole::TENANT, $user->role);
        $this->assertFalse($user->is_super_admin);
        $this->assertNotEquals(999, $user->tenant_id);
    }

    /** @test */
    public function it_requires_email_verification_for_api_access(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'email_verified_at' => null, // Unverified
        ]);
        
        $token = $user->createApiToken('test');
        
        $response = $this->withToken($token)->getJson('/api/user');
        
        $response->assertUnauthorized();
    }

    /** @test */
    public function it_rate_limits_token_validation_attempts(): void
    {
        // Make 61 invalid token requests (over limit)
        for ($i = 0; $i < 61; $i++) {
            $this->withToken('invalid-token')->getJson('/api/user');
        }
        
        // Next request should be rate limited
        $response = $this->withToken('invalid-token')->getJson('/api/user');
        $response->assertStatus(429);
    }

    /** @test */
    public function it_logs_security_violations(): void
    {
        Log::fake();
        
        $user = User::factory()->create();
        
        // Attempt to create user with dangerous identifier
        try {
            app(InputSanitizer::class)->sanitizeIdentifier('../../../etc/passwd');
        } catch (\InvalidArgumentException $e) {
            // Expected
        }
        
        Log::assertLogged('warning', function ($message, $context) {
            return str_contains($message, 'Path traversal attempt detected');
        });
    }
}
```

### Performance Security Tests

```php
// tests/Performance/SecurityPerformanceTest.php
<?php

namespace Tests\Performance;

use App\Models\PersonalAccessToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityPerformanceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function token_validation_performs_within_limits(): void
    {
        $user = User::factory()->create();
        $token = $user->createApiToken('test');
        
        $startTime = microtime(true);
        
        // Perform 100 token validations
        for ($i = 0; $i < 100; $i++) {
            PersonalAccessToken::findToken($token);
        }
        
        $duration = microtime(true) - $startTime;
        
        // Should complete 100 validations in under 1 second
        $this->assertLessThan(1.0, $duration);
    }

    /** @test */
    public function database_indexes_optimize_security_queries(): void
    {
        // Create test data
        User::factory()->count(1000)->create();
        
        $startTime = microtime(true);
        
        // Query that should use security indexes
        User::where('is_active', true)
            ->whereNotNull('email_verified_at')
            ->whereNull('suspended_at')
            ->count();
            
        $duration = microtime(true) - $startTime;
        
        // Should complete in under 100ms with proper indexes
        $this->assertLessThan(0.1, $duration);
    }
}
```

### Security Headers Validation

```php
// tests/Feature/SecurityHeadersTest.php
<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    /** @test */
    public function it_includes_required_security_headers(): void
    {
        $response = $this->get('/');
        
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Strict-Transport-Security');
        $response->assertHeader('Content-Security-Policy');
    }

    /** @test */
    public function csp_header_prevents_inline_scripts(): void
    {
        $response = $this->get('/');
        
        $csp = $response->headers->get('Content-Security-Policy');
        
        $this->assertStringNotContainsString("'unsafe-inline'", $csp);
        $this->assertStringNotContainsString("'unsafe-eval'", $csp);
        $this->assertStringContainsString("'nonce-", $csp);
    }
}
```

### Monitoring & Alerting

```php
// app/Console/Commands/SecurityMonitoring.php
<?php

namespace App\Console\Commands;

use App\Models\PersonalAccessToken;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SecurityMonitoring extends Command
{
    protected $signature = 'security:monitor';
    protected $description = 'Monitor security metrics and send alerts';

    public function handle(): int
    {
        $alerts = [];
        
        // Check for suspicious token activity
        $suspiciousTokens = PersonalAccessToken::where('created_at', '>', now()->subHour())
            ->whereHas('tokenable', function ($query) {
                $query->where('role', 'superadmin');
            })
            ->count();
            
        if ($suspiciousTokens > 5) {
            $alerts[] = "High superadmin token creation rate: {$suspiciousTokens}/hour";
        }
        
        // Check for failed login attempts
        $failedLogins = cache()->get('failed_logins_last_hour', 0);
        if ($failedLogins > 50) {
            $alerts[] = "High failed login rate: {$failedLogins}/hour";
        }
        
        // Check for unverified users with tokens
        $unverifiedWithTokens = User::whereNull('email_verified_at')
            ->whereHas('tokens')
            ->count();
            
        if ($unverifiedWithTokens > 0) {
            $alerts[] = "Unverified users with active tokens: {$unverifiedWithTokens}";
        }
        
        // Send alerts if any
        if (!empty($alerts)) {
            Log::warning('Security alerts detected', ['alerts' => $alerts]);
            
            // Send email alert
            Mail::raw(
                "Security alerts detected:\n\n" . implode("\n", $alerts),
                function ($message) {
                    $message->to(config('security.monitoring.alert_channels.email'))
                           ->subject('Security Alert - ' . config('app.name'));
                }
            );
        }
        
        $this->info('Security monitoring completed. Alerts: ' . count($alerts));
        return 0;
    }
}
```

## 5. COMPLIANCE CHECKLIST

### ✅ Authentication & Authorization
- [x] Multi-factor authentication support (via email verification)
- [x] Role-based access control (RBAC) implemented
- [x] Principle of least privilege enforced
- [x] Session management with secure cookies
- [ ] **MISSING**: Account lockout after failed attempts
- [ ] **MISSING**: Password complexity requirements
- [ ] **MISSING**: Regular password rotation enforcement

### ✅ Data Protection
- [x] Input sanitization implemented
- [x] SQL injection prevention (Eloquent ORM)
- [x] XSS prevention (InputSanitizer)
- [x] CSRF protection enabled
- [x] PII redaction in logs
- [ ] **MISSING**: Field-level encryption for sensitive data
- [ ] **MISSING**: Data retention policies

### ✅ Network Security
- [x] HTTPS enforcement configuration
- [x] Security headers implemented
- [x] Content Security Policy (needs strengthening)
- [x] Rate limiting implemented
- [ ] **MISSING**: IP whitelisting for admin operations
- [ ] **MISSING**: Geographic access restrictions

### ✅ Monitoring & Logging
- [x] Security event logging
- [x] Audit trail for sensitive operations
- [x] Log retention policies
- [x] PII redaction in logs
- [ ] **MISSING**: Real-time security monitoring
- [ ] **MISSING**: Automated incident response

### ✅ Configuration Security
- [x] Environment-based configuration
- [x] Secure session configuration
- [x] Database connection security
- [ ] **MISSING**: Secrets management (consider Laravel Vault)
- [ ] **MISSING**: Configuration drift detection

### Deployment Security Checklist

```bash
# Production deployment security checklist
# Add to deployment scripts

# 1. Environment Variables
echo "Checking environment security..."
if [ "$APP_DEBUG" = "true" ]; then
    echo "❌ APP_DEBUG must be false in production"
    exit 1
fi

if [ "$APP_ENV" != "production" ]; then
    echo "❌ APP_ENV must be 'production'"
    exit 1
fi

if [ -z "$APP_KEY" ]; then
    echo "❌ APP_KEY must be set"
    exit 1
fi

# 2. HTTPS Configuration
if [ "$FORCE_HTTPS" != "true" ]; then
    echo "❌ FORCE_HTTPS must be true"
    exit 1
fi

if [ "$SESSION_SECURE_COOKIE" != "true" ]; then
    echo "❌ SESSION_SECURE_COOKIE must be true"
    exit 1
fi

# 3. Database Security
if [[ "$DB_PASSWORD" == *"password"* ]] || [[ "$DB_PASSWORD" == *"123"* ]]; then
    echo "❌ Weak database password detected"
    exit 1
fi

# 4. File Permissions
echo "Setting secure file permissions..."
chmod 644 .env
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/

# 5. Security Headers Test
echo "Testing security headers..."
curl -I https://$APP_URL | grep -q "X-Frame-Options" || {
    echo "❌ Security headers not configured"
    exit 1
}

echo "✅ Security checks passed"
```

## 6. IMMEDIATE ACTION ITEMS

### 🔴 CRITICAL (Fix within 24 hours)
1. **Implement email verification requirement for API access**
2. **Secure User model fillable array** (remove dangerous fields)
3. **Add rate limiting to token validation middleware**
4. **Implement automatic token pruning**

### 🟠 HIGH (Fix within 1 week)
1. **Add database security indexes**
2. **Strengthen rate limiting configuration**
3. **Implement account lockout mechanism**
4. **Add comprehensive security monitoring**

### 🟡 MEDIUM (Fix within 2 weeks)
1. **Strengthen Content Security Policy**
2. **Enhance session security configuration**
3. **Implement field-level encryption**
4. **Add security performance tests**

### 🟢 LOW (Fix within 1 month)
1. **Add modern security headers**
2. **Implement password complexity requirements**
3. **Add geographic access restrictions**
4. **Implement secrets management**

## 7. CONCLUSION

The removal of `HasApiTokens` trait and implementation of a custom API token system introduces significant security risks that require immediate attention. While the application has strong foundational security measures (input sanitization, security headers, audit logging), the custom authentication system needs hardening to maintain security parity with Laravel Sanctum.

**Priority Actions**:
1. Secure the custom token system with proper validation and cleanup
2. Fix mass assignment vulnerabilities in the User model
3. Implement comprehensive rate limiting
4. Add missing security indexes for performance and security

**Estimated Effort**: 2-3 developer weeks for critical and high-priority fixes.

**Risk Assessment**: Current implementation is suitable for development but requires security hardening before production deployment.

---
**Report Generated**: December 16, 2025  
**Next Review**: January 16, 2026  
**Contact**: security@company.com