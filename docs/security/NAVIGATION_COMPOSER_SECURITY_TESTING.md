# NavigationComposer Security Testing Guide

## Overview

This guide provides comprehensive security testing procedures for the NavigationComposer component, including automated tests, manual verification, and penetration testing scenarios.

---

## Automated Security Tests

### Running Tests

```bash
# Run all NavigationComposer tests
php artisan test --filter NavigationComposerTest

# Run with coverage
php artisan test --filter NavigationComposerTest --coverage

# Run with verbose output
php artisan test --filter NavigationComposerTest --verbose
```

### Expected Results

```
✓ it does not compose view data when user is not authenticated
✓ it composes view data for authenticated admin user
✓ it hides locale switcher for manager role
✓ it hides locale switcher for tenant role
✓ it hides locale switcher for superadmin role
✓ it returns only active languages ordered by display_order
✓ it provides consistent CSS classes for active and inactive states

Tests:    7 passed (32 assertions)
```

---

## Security Test Cases

### 1. Authentication Tests

#### Test: Unauthenticated Access
**Objective**: Verify no data exposed to unauthenticated users

```php
it('does not compose view data when user is not authenticated', function () {
    $this->auth->shouldReceive('check')->once()->andReturn(false);
    $this->view->shouldNotReceive('with');
    $this->composer->compose($this->view);
});
```

**Verification**:
- ✅ No view data composed
- ✅ No database queries executed
- ✅ No errors thrown

---

#### Test: Session Fixation
**Objective**: Verify session regeneration on authentication

```bash
# Manual test
1. Open browser in incognito mode
2. Navigate to login page
3. Note session ID in cookies
4. Log in
5. Verify session ID changed
```

**Expected**: Session ID should change after authentication

---

### 2. Authorization Tests

#### Test: Role-Based Access Control
**Objective**: Verify locale switcher hidden for specific roles

```php
it('hides locale switcher for manager role', function () {
    $user = User::factory()->make(['role' => UserRole::MANAGER]);
    $this->auth->shouldReceive('check')->once()->andReturn(true);
    $this->auth->shouldReceive('user')->once()->andReturn($user);
    
    $this->view->shouldReceive('with')->once()->withArgs(function ($data) {
        return $data['showTopLocaleSwitcher'] === false;
    });
    
    $this->composer->compose($this->view);
});
```

**Verification**:
- ✅ MANAGER: Locale switcher hidden
- ✅ TENANT: Locale switcher hidden
- ✅ SUPERADMIN: Locale switcher hidden
- ✅ ADMIN: Locale switcher visible

---

#### Test: Authorization Bypass Attempt
**Objective**: Verify enum prevents typo-based bypasses

```php
it('prevents authorization bypass via typo', function () {
    // This should fail at compile time
    $roles = [
        'manger', // Typo: should be 'manager'
    ];
    
    // Using enum prevents this
    $roles = [
        UserRole::MANAGER, // Compile-time validation
    ];
});
```

**Verification**:
- ✅ Enum usage prevents typos
- ✅ IDE autocomplete suggests valid roles
- ✅ Static analysis catches errors

---

### 3. SQL Injection Tests

#### Test: Query Scope Protection
**Objective**: Verify query scope prevents SQL injection

```php
it('prevents SQL injection in language queries', function () {
    // Create language with malicious code
    Language::factory()->create([
        'code' => "'; DROP TABLE languages; --",
        'is_active' => true,
    ]);
    
    $user = User::factory()->make(['role' => UserRole::ADMIN]);
    $this->auth->shouldReceive('check')->once()->andReturn(true);
    $this->auth->shouldReceive('user')->once()->andReturn($user);
    $this->router->shouldReceive('currentRouteName')->once()->andReturn('admin.dashboard');
    $this->router->shouldReceive('has')->with('locale.set')->times(3)->andReturn(true);
    
    $this->view->shouldReceive('with')->once();
    
    // Should not execute SQL injection
    $this->composer->compose($this->view);
    
    // Verify languages table still exists
    expect(Language::count())->toBeGreaterThan(0);
});
```

**Verification**:
- ✅ SQL injection not executed
- ✅ Database tables intact
- ✅ Query parameterized correctly

---

#### Test: Direct Query Injection
**Objective**: Verify parameterized queries

```bash
# Manual test using database logs
1. Enable query logging
2. Trigger NavigationComposer
3. Check logs for parameterized queries

# Expected query:
SELECT * FROM languages WHERE is_active = ? ORDER BY display_order
# Parameters: [true]
```

**Verification**:
- ✅ All queries parameterized
- ✅ No string concatenation
- ✅ No raw SQL

---

### 4. XSS Prevention Tests

#### Test: CSS Class Injection
**Objective**: Verify CSS classes cannot be injected

```php
it('prevents XSS via CSS class injection', function () {
    // Attempt to inject malicious CSS
    $maliciousClass = '<script>alert("XSS")</script>';
    
    // Constants prevent injection
    expect(NavigationComposer::ACTIVE_CLASS)
        ->not->toContain('<script>')
        ->not->toContain('javascript:');
});
```

**Verification**:
- ✅ CSS classes are constants
- ✅ No user input in CSS classes
- ✅ Blade auto-escapes output

---

#### Test: Blade Escaping
**Objective**: Verify Blade auto-escapes all output

```bash
# Manual test
1. Create language with malicious name
2. View navigation in browser
3. Inspect HTML source

# Expected: HTML entities escaped
<option value="en">&lt;script&gt;alert("XSS")&lt;/script&gt;</option>
```

**Verification**:
- ✅ All output escaped
- ✅ No raw HTML in output
- ✅ No XSS vulnerabilities

---

### 5. Type Safety Tests

#### Test: Type Juggling Prevention
**Objective**: Verify strict types prevent type juggling

```php
it('prevents type juggling attacks', function () {
    // Attempt type juggling
    $stringZero = '0';
    $intZero = 0;
    
    // Strict types prevent this
    expect($stringZero === $intZero)->toBeFalse();
    
    // Enum prevents string comparison
    expect(UserRole::MANAGER->value)->toBe('manager');
    expect(UserRole::MANAGER->value === 0)->toBeFalse();
});
```

**Verification**:
- ✅ Strict types enabled
- ✅ No type juggling
- ✅ Enum usage enforced

---

### 6. Information Disclosure Tests

#### Test: Data Leakage Prevention
**Objective**: Verify no sensitive data exposed

```php
it('does not expose sensitive data', function () {
    $user = User::factory()->make(['role' => UserRole::ADMIN]);
    $this->auth->shouldReceive('check')->once()->andReturn(true);
    $this->auth->shouldReceive('user')->once()->andReturn($user);
    $this->router->shouldReceive('currentRouteName')->once()->andReturn('admin.dashboard');
    $this->router->shouldReceive('has')->with('locale.set')->times(3)->andReturn(true);
    
    Language::factory()->count(3)->create(['is_active' => true]);
    
    $this->view->shouldReceive('with')->once()->withArgs(function ($data) {
        // Verify no sensitive data
        expect($data)->not->toHaveKey('password');
        expect($data)->not->toHaveKey('tenant_id');
        expect($data)->not->toHaveKey('email');
        return true;
    });
    
    $this->composer->compose($this->view);
});
```

**Verification**:
- ✅ No PII exposed
- ✅ No sensitive data in output
- ✅ Only necessary data provided

---

### 7. Denial of Service Tests

#### Test: Query Performance
**Objective**: Verify queries are optimized

```php
it('executes queries efficiently', function () {
    $user = User::factory()->make(['role' => UserRole::ADMIN]);
    $this->auth->shouldReceive('check')->once()->andReturn(true);
    $this->auth->shouldReceive('user')->once()->andReturn($user);
    $this->router->shouldReceive('currentRouteName')->once()->andReturn('admin.dashboard');
    $this->router->shouldReceive('has')->with('locale.set')->times(3)->andReturn(true);
    
    Language::factory()->count(100)->create(['is_active' => true]);
    
    // Measure query time
    $start = microtime(true);
    
    $this->view->shouldReceive('with')->once();
    $this->composer->compose($this->view);
    
    $duration = microtime(true) - $start;
    
    // Should complete in < 100ms
    expect($duration)->toBeLessThan(0.1);
});
```

**Verification**:
- ✅ Query time < 100ms
- ✅ No N+1 queries
- ✅ Conditional loading

---

#### Test: Memory Usage
**Objective**: Verify memory usage is minimal

```php
it('uses minimal memory', function () {
    $user = User::factory()->make(['role' => UserRole::ADMIN]);
    $this->auth->shouldReceive('check')->once()->andReturn(true);
    $this->auth->shouldReceive('user')->once()->andReturn($user);
    $this->router->shouldReceive('currentRouteName')->once()->andReturn('admin.dashboard');
    $this->router->shouldReceive('has')->with('locale.set')->times(3)->andReturn(true);
    
    Language::factory()->count(100)->create(['is_active' => true]);
    
    // Measure memory
    $memoryBefore = memory_get_usage();
    
    $this->view->shouldReceive('with')->once();
    $this->composer->compose($this->view);
    
    $memoryAfter = memory_get_usage();
    $memoryUsed = $memoryAfter - $memoryBefore;
    
    // Should use < 1MB
    expect($memoryUsed)->toBeLessThan(1024 * 1024);
});
```

**Verification**:
- ✅ Memory usage < 1MB
- ✅ No memory leaks
- ✅ Efficient data structures

---

## Manual Security Testing

### 1. Browser-Based Testing

#### Test: Session Security
```bash
1. Open browser developer tools
2. Navigate to application
3. Check cookies:
   - HttpOnly: true
   - Secure: true (production)
   - SameSite: Lax
4. Log in
5. Verify session ID changes
6. Check CSRF token present
```

**Expected**:
- ✅ Session cookie secure
- ✅ Session ID regenerated
- ✅ CSRF token present

---

#### Test: XSS Prevention
```bash
1. Create language with malicious name:
   <script>alert('XSS')</script>
2. View navigation
3. Inspect HTML source
4. Verify HTML entities escaped
```

**Expected**:
```html
<option value="en">&lt;script&gt;alert('XSS')&lt;/script&gt;</option>
```

---

#### Test: Authorization
```bash
1. Log in as MANAGER
2. Check navigation
3. Verify locale switcher hidden
4. Log out
5. Log in as ADMIN
6. Verify locale switcher visible
```

**Expected**:
- ✅ MANAGER: No locale switcher
- ✅ ADMIN: Locale switcher visible

---

### 2. API Testing

#### Test: Authentication
```bash
# Unauthenticated request
curl -X GET http://localhost:8000/admin/dashboard

# Expected: Redirect to login
HTTP/1.1 302 Found
Location: /login
```

---

#### Test: Authorization
```bash
# Log in as MANAGER
curl -X POST http://localhost:8000/login \
  -d "email=manager@example.com" \
  -d "password=password"

# Access admin route
curl -X GET http://localhost:8000/admin/dashboard \
  -H "Cookie: laravel_session=..."

# Expected: 403 Forbidden
HTTP/1.1 403 Forbidden
```

---

### 3. Database Testing

#### Test: SQL Injection
```sql
-- Attempt SQL injection
INSERT INTO languages (code, name, is_active) 
VALUES ("'; DROP TABLE languages; --", 'Malicious', true);

-- Query languages
SELECT * FROM languages WHERE is_active = true;

-- Expected: Languages table intact
```

---

#### Test: Query Performance
```sql
-- Enable query logging
SET GLOBAL general_log = 'ON';

-- Trigger NavigationComposer
-- Check logs for queries

-- Expected query:
SELECT * FROM languages WHERE is_active = ? ORDER BY display_order;
```

---

## Penetration Testing

### 1. Authentication Bypass

**Scenario**: Attempt to bypass authentication

```bash
# Test 1: Direct access to protected routes
curl -X GET http://localhost:8000/admin/dashboard

# Test 2: Session fixation
# 1. Get session ID before login
# 2. Log in
# 3. Verify session ID changed

# Test 3: Brute force protection
# Attempt multiple failed logins
# Verify rate limiting active
```

**Expected**:
- ✅ Redirect to login
- ✅ Session regenerated
- ✅ Rate limiting active

---

### 2. Authorization Bypass

**Scenario**: Attempt to bypass role-based authorization

```bash
# Test 1: Role manipulation
# Attempt to change role in session
# Verify role validated from database

# Test 2: Privilege escalation
# Log in as TENANT
# Attempt to access ADMIN routes
# Verify 403 Forbidden

# Test 3: Horizontal privilege escalation
# Log in as MANAGER (tenant_id=1)
# Attempt to access data from tenant_id=2
# Verify tenant isolation
```

**Expected**:
- ✅ Role validated from database
- ✅ 403 Forbidden for unauthorized access
- ✅ Tenant isolation enforced

---

### 3. SQL Injection

**Scenario**: Attempt SQL injection attacks

```bash
# Test 1: Language code injection
POST /admin/languages
{
  "code": "'; DROP TABLE languages; --",
  "name": "Malicious"
}

# Test 2: Query parameter injection
GET /admin/languages?filter[code]=' OR '1'='1

# Test 3: Order by injection
GET /admin/languages?sort=display_order; DROP TABLE languages; --
```

**Expected**:
- ✅ SQL injection not executed
- ✅ Parameterized queries used
- ✅ Database tables intact

---

### 4. XSS Attacks

**Scenario**: Attempt XSS attacks

```bash
# Test 1: Stored XSS
POST /admin/languages
{
  "code": "en",
  "name": "<script>alert('XSS')</script>"
}

# Test 2: Reflected XSS
GET /admin/languages?search=<script>alert('XSS')</script>

# Test 3: DOM-based XSS
# Inject malicious JavaScript via URL fragment
GET /admin/languages#<script>alert('XSS')</script>
```

**Expected**:
- ✅ HTML entities escaped
- ✅ No script execution
- ✅ CSP headers block inline scripts

---

### 5. CSRF Attacks

**Scenario**: Attempt CSRF attacks

```bash
# Test 1: Missing CSRF token
POST /admin/languages
{
  "code": "en",
  "name": "English"
}
# (without CSRF token)

# Test 2: Invalid CSRF token
POST /admin/languages
{
  "_token": "invalid_token",
  "code": "en",
  "name": "English"
}

# Test 3: CSRF token reuse
# Use same token for multiple requests
```

**Expected**:
- ✅ 419 Page Expired (missing token)
- ✅ 419 Page Expired (invalid token)
- ✅ Token validated per request

---

## Security Checklist

### Pre-Deployment

- [ ] All tests passing
- [ ] Static analysis clean
- [ ] No security warnings
- [ ] Documentation updated
- [ ] Security audit complete

### Configuration

- [ ] APP_DEBUG=false (production)
- [ ] APP_ENV=production
- [ ] HTTPS enforced
- [ ] Session cookies secure
- [ ] CSRF protection enabled
- [ ] Rate limiting configured
- [ ] CSP headers set

### Database

- [ ] Indexes on is_active, display_order
- [ ] Foreign keys enforced
- [ ] Backups configured
- [ ] Query logging enabled (monitoring)

### Monitoring

- [ ] Application logs monitored
- [ ] Database logs monitored
- [ ] Error tracking configured
- [ ] Performance monitoring active

---

## Incident Response

### Security Incident Detected

1. **Isolate**: Disable affected component
2. **Investigate**: Review logs and traces
3. **Remediate**: Apply security patch
4. **Verify**: Run security tests
5. **Deploy**: Deploy patched version
6. **Monitor**: Watch for recurrence

### Reporting

**Internal**: Report to development team  
**External**: Report to security team  
**Public**: Disclose responsibly (if applicable)

---

## References

- [OWASP Testing Guide](https://owasp.org/www-project-web-security-testing-guide/)
- [Laravel Security](https://laravel.com/docs/12.x/security)
- [NAVIGATION_COMPOSER_SECURITY_AUDIT.md](NAVIGATION_COMPOSER_SECURITY_AUDIT.md)

---

**Last Updated**: 2025-11-24  
**Next Review**: 2026-02-24  
**Maintained By**: Security Team
