# PII Protection Policy

## Overview

This document outlines how the application handles Personally Identifiable Information (PII) in compliance with GDPR, CCPA, and other privacy regulations.

## PII Classification

### High-Sensitivity PII
- Passwords (never logged, always hashed)
- Credit card numbers (not stored in this application)
- Social Security Numbers (not applicable)
- Biometric data (not collected)

### Medium-Sensitivity PII
- Email addresses
- Phone numbers
- IP addresses
- User IDs (when combined with other data)

### Low-Sensitivity PII
- Names (when not combined with other identifiers)
- Tenant IDs (internal identifiers)
- Timestamps

## Logging Practices

### What We Log

**Security Events**:
- Violation type (e.g., "path_traversal")
- Redacted input patterns
- Hashed IP addresses (SHA-256 with app key)
- User IDs (for authenticated users only)
- Timestamps

**Audit Events**:
- Action performed (e.g., "tariff_created")
- Resource ID (not PII)
- User ID (internal identifier)
- Tenant ID (internal identifier)
- Timestamps

### What We DON'T Log

- Raw passwords (never)
- Full email addresses in security logs (redacted to [EMAIL])
- Raw IP addresses (hashed)
- Credit card numbers (not collected)
- Session tokens (redacted)

### Log Retention

| Log Type | Retention Period | Justification |
|----------|------------------|---------------|
| Security logs | 90 days | Incident investigation |
| Audit logs | 90 days | Compliance requirements |
| Application logs | 14 days | Debugging |
| Error logs | 30 days | Issue tracking |

### Automatic Redaction

The `RedactSensitiveData` processor automatically redacts:

```php
// Email addresses
'user@example.com' → '[EMAIL_REDACTED]'

// IP addresses
'192.168.1.1' → '[IP_REDACTED]'

// Phone numbers
'+1-555-123-4567' → '[PHONE_REDACTED]'

// Tokens
'token=abc123...' → 'token=[TOKEN_REDACTED]'
```

## Data Encryption

### At Rest

**Database Encryption**:
- Sensitive fields encrypted using Laravel's `encrypted` cast
- Database-level encryption for SQLite/MySQL (if configured)
- Backup files encrypted before storage

**Example**:
```php
class User extends Model
{
    protected $casts = [
        'email' => 'encrypted', // ✅ Encrypted at rest
        'phone' => 'encrypted', // ✅ Encrypted at rest
    ];
}
```

### In Transit

**HTTPS Enforcement**:
- All production traffic over HTTPS (enforced by HSTS header)
- TLS 1.2+ required
- Strong cipher suites only

**Configuration**:
```env
# .env (production)
APP_URL=https://example.com
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=strict
```

## Demo Mode Safety

### Test Data Generation

**Seeders use fake data**:
```php
User::factory()->create([
    'email' => 'test@example.com', // ✅ Fake email
    'password' => Hash::make('password'), // ✅ Static test password
]);
```

**Never use real PII in demos**:
- ❌ Real email addresses
- ❌ Real phone numbers
- ❌ Real names (use faker)
- ❌ Real addresses

### Demo Mode Flag

```php
// config/app.php
'demo_mode' => env('DEMO_MODE', false),

// Usage
if (config('app.demo_mode')) {
    // Disable email sending
    // Use fake payment processing
    // Show demo banner
}
```

## User Rights (GDPR/CCPA)

### Right to Access

**Endpoint**: `GET /api/user/data-export`

```php
public function exportData(Request $request)
{
    $user = $request->user();
    
    return response()->json([
        'user' => $user->only(['name', 'email', 'created_at']),
        'properties' => $user->properties,
        'invoices' => $user->invoices,
        'meter_readings' => $user->meterReadings,
    ]);
}
```

### Right to Deletion

**Endpoint**: `DELETE /api/user/account`

```php
public function deleteAccount(Request $request)
{
    $user = $request->user();
    
    // Anonymize instead of hard delete (for audit trail)
    $user->update([
        'email' => 'deleted_' . $user->id . '@example.com',
        'name' => 'Deleted User',
        'deleted_at' => now(),
    ]);
    
    // Soft delete
    $user->delete();
    
    return response()->json(['message' => 'Account deleted']);
}
```

### Right to Rectification

**Endpoint**: `PATCH /api/user/profile`

```php
public function updateProfile(UpdateProfileRequest $request)
{
    $user = $request->user();
    
    $user->update($request->validated());
    
    // Log the change for audit
    AuditLog::create([
        'user_id' => $user->id,
        'action' => 'profile_updated',
        'changes' => $request->validated(),
    ]);
    
    return response()->json($user);
}
```

## Compliance Checklist

### GDPR Compliance

- [x] PII redaction in logs
- [x] Data encryption at rest
- [x] HTTPS enforcement
- [x] User data export endpoint
- [x] Account deletion endpoint
- [x] Privacy policy published
- [x] Cookie consent (if using tracking cookies)
- [x] Data retention policies
- [ ] DPO appointed (if required)
- [ ] Data processing agreements with vendors

### CCPA Compliance

- [x] User data access
- [x] User data deletion
- [x] Opt-out of data sale (N/A - we don't sell data)
- [x] Privacy notice
- [ ] Designated contact for privacy requests

## Incident Response

### Data Breach Procedure

1. **Detection**: Security monitoring alerts on suspicious activity
2. **Containment**: Isolate affected systems
3. **Assessment**: Determine scope of breach
4. **Notification**: 
   - Users (within 72 hours for GDPR)
   - Authorities (if required)
   - Insurance provider
5. **Remediation**: Fix vulnerability
6. **Review**: Post-incident analysis

### Contact Information

- **Security Team**: security@example.com
- **DPO**: dpo@example.com (if applicable)
- **Emergency**: +1-XXX-XXX-XXXX

## References

- [GDPR Official Text](https://gdpr.eu/)
- [CCPA Official Text](https://oag.ca.gov/privacy/ccpa)
- [OWASP Privacy Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Privacy_Cheat_Sheet.html)
- [Laravel Security Documentation](https://laravel.com/docs/12.x/security)
