# Security Headers MCP Integration Testing Guide

## Overview

This guide provides comprehensive documentation for testing the SecurityHeaders middleware with MCP (Model Context Protocol) integration, including type safety enhancements, security analytics, and performance monitoring.

## Test Structure

### 1. Feature Tests (`tests/Feature/Security/`)

#### SecurityHeadersMiddlewareEnhancedTest.php
**Purpose**: Test enhanced middleware functionality with MCP integration

**Key Test Cases**:
- `test_middleware_works_with_base_response_type()` - Verifies BaseResponse type compatibility
- `test_fallback_headers_applied_with_base_response()` - Tests error handling with Symfony responses
- `test_mcp_security_analytics_integration()` - Validates MCP service integration
- `test_csp_violation_processing_with_mcp()` - Tests CSP violation workflow
- `test_mcp_rate_limiting_enforcement()` - Verifies rate limiting works correctly
- `test_tenant_isolation_in_mcp_analytics()` - Ensures multi-tenant data isolation
- `test_malicious_csp_violation_detection()` - Tests threat detection

**Usage**:
```bash
php artisan test tests/Feature/Security/SecurityHeadersMiddlewareEnhancedTest.php
```

### 2. Unit Tests (`tests/Unit/Services/Security/`)

#### SecurityAnalyticsMcpServiceEnhancedTest.php
**Purpose**: Test MCP service methods in isolation

**Key Test Cases**:
- `test_tracks_csp_violation_via_mcp()` - Tests MCP violation tracking
- `test_processes_csp_violation_from_request()` - Tests request processing
- `test_validates_csp_request_rate_limiting()` - Tests rate limiting logic
- `test_sanitizes_csp_report_data()` - Tests data sanitization
- `test_detects_malicious_patterns()` - Tests threat detection
- `test_encrypts_sensitive_metadata()` - Tests encryption

**Usage**:
```bash
php artisan test tests/Unit/Services/Security/SecurityAnalyticsMcpServiceEnhancedTest.php
```

### 3. Property-Based Tests (`tests/Property/`)

#### SecurityHeadersMcpIntegrationPropertyTest.php
**Purpose**: Test security invariants that must hold across all scenarios

**Key Properties Tested**:
- CSP violation sanitization and classification consistency
- Rate limiting enforcement across all IPs
- MCP service resilience to failures
- Tenant isolation maintenance
- Metadata encryption consistency
- Performance bounds under load
- Audit trail completeness

**Usage**:
```bash
php artisan test tests/Property/SecurityHeadersMcpIntegrationPropertyTest.php
```

### 4. Performance Tests (`tests/Performance/`)

#### SecurityHeadersMcpPerformanceTest.php
**Purpose**: Validate performance targets are met with MCP integration

**Key Metrics**:
- CSP violation processing: < 50ms per violation
- MCP analytics operations: < 200ms
- Concurrent operations: < 1000ms for 20 requests
- Memory usage: < 10MB for 100 operations

**Usage**:
```bash
php artisan test tests/Performance/SecurityHeadersMcpPerformanceTest.php
```

### 5. Browser Tests (`tests/Browser/`)

#### SecurityHeadersMcpIntegrationTest.php
**Purpose**: Test UI interactions and accessibility

**Key Test Cases**:
- Security analytics dashboard accessibility (WCAG 2.1 AA)
- CSP violation real-time updates
- Performance monitoring UI
- MCP service status indicators
- CSP policy builder interface
- Tenant security isolation in UI

**Usage**:
```bash
php artisan dusk tests/Browser/SecurityHeadersMcpIntegrationTest.php
```

## Test Helpers

### SecurityTestHelpers Trait

Located in `tests/Support/SecurityTestHelpers.php`

**Available Methods**:
- `createCspViolationRequest()` - Create test CSP violation requests
- `createMaliciousCspViolationRequest()` - Create malicious violation requests
- `mockMcpService()` - Mock MCP service with expected behavior
- `createTenantWithViolations()` - Create test tenant with violations
- `assertCspViolationProcessed()` - Assert violation was processed correctly
- `assertSecurityHeadersApplied()` - Assert security headers are present
- `assertCspNonceValid()` - Assert CSP nonce is valid
- `assertTenantIsolation()` - Assert tenant data isolation

**Usage Example**:
```php
use Tests\Support\SecurityTestHelpers;

class MySecurityTest extends TestCase
{
    use SecurityTestHelpers;

    public function test_example(): void
    {
        $request = $this->createCspViolationRequest([
            'blocked-uri' => 'https://malicious.com/script.js',
        ]);

        $this->mockMcpService([
            'trackCspViolation' => true,
        ]);

        // Your test logic...
    }
}
```

### SecurityTestConfig Class

Located in `tests/Support/SecurityTestConfig.php`

**Configuration Constants**:
- `PERFORMANCE_THRESHOLDS` - Performance limits for operations
- `RATE_LIMITS` - Rate limiting configuration
- `CSP_VIOLATION_PATTERNS` - Test data patterns
- `THREAT_CLASSIFICATIONS` - Expected threat levels
- `REQUIRED_SECURITY_HEADERS` - Headers that must be present

**Usage Example**:
```php
use Tests\Support\SecurityTestConfig;

$threshold = SecurityTestConfig::getPerformanceThreshold('csp_violation_processing');
$this->assertLessThan($threshold, $actualTime);
```

## Factories

### SecurityViolationFactory

Located in `database/factories/SecurityViolationFactory.php`

**Available States**:
- `malicious()` - Create malicious violation
- `suspicious()` - Create suspicious violation
- `resolved()` - Create resolved violation
- `scriptSrc()` - Create script-src violation
- `styleSrc()` - Create style-src violation
- `withTenant($tenant)` - Associate with specific tenant

**Usage Example**:
```php
$violation = SecurityViolation::factory()
    ->malicious()
    ->withTenant($tenant)
    ->create();
```

## Running Tests

### Run All Security Tests
```bash
php artisan test --testsuite=Feature --filter=Security
php artisan test --testsuite=Unit --filter=Security
php artisan test --testsuite=Property
php artisan test --testsuite=Performance
```

### Run Specific Test Categories
```bash
# Feature tests only
php artisan test tests/Feature/Security/

# Unit tests only
php artisan test tests/Unit/Services/Security/

# Property tests only
php artisan test tests/Property/

# Performance tests only
php artisan test tests/Performance/

# Browser tests only
php artisan dusk tests/Browser/SecurityHeadersMcpIntegrationTest.php
```

### Run with Coverage
```bash
php artisan test --coverage --min=80
```

### Run with Parallel Execution
```bash
php artisan test --parallel
```

## Test Data Setup

### Database Seeding for Tests
```bash
php artisan db:seed --class=SecurityViolationSeeder
```

### Clear Test Caches
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

## Continuous Integration

### GitHub Actions Workflow
```yaml
name: Security Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
      - name: Install Dependencies
        run: composer install
      - name: Run Security Tests
        run: |
          php artisan test tests/Feature/Security/
          php artisan test tests/Unit/Services/Security/
          php artisan test tests/Property/
          php artisan test tests/Performance/
```

## Coverage Goals

### Minimum Coverage Targets
- **Overall**: 80%
- **SecurityHeaders Middleware**: 95%
- **SecurityAnalyticsMcpService**: 90%
- **Security Value Objects**: 100%
- **Critical Security Paths**: 100%

### Coverage Report
```bash
php artisan test --coverage-html coverage-report
```

## Regression Risks

### High-Risk Areas
1. **Type Safety Changes**: BaseResponse import could affect response handling
2. **MCP Integration**: New dependency on external MCP servers
3. **Rate Limiting**: Changes could affect legitimate traffic
4. **Data Sanitization**: Over-sanitization could lose important data
5. **Performance**: MCP calls could introduce latency

### Mitigation Strategies
1. Comprehensive type compatibility tests
2. MCP service mocking and fallback testing
3. Rate limiting threshold validation
4. Sanitization reversibility tests
5. Performance benchmarking and monitoring

## Debugging Tests

### Enable Verbose Output
```bash
php artisan test --verbose
```

### Debug Specific Test
```bash
php artisan test --filter=test_mcp_security_analytics_integration --debug
```

### View Test Logs
```bash
tail -f storage/logs/laravel.log
```

## Best Practices

### 1. Test Isolation
- Use `RefreshDatabase` trait for database tests
- Clear caches between tests
- Mock external dependencies (MCP servers)

### 2. Descriptive Test Names
- Use descriptive method names that explain what is being tested
- Follow AAA pattern: Arrange, Act, Assert

### 3. Performance Testing
- Always include performance assertions
- Use realistic data volumes
- Test under concurrent load

### 4. Accessibility Testing
- Test keyboard navigation
- Verify ARIA attributes
- Test screen reader compatibility

### 5. Security Testing
- Test with malicious inputs
- Verify data sanitization
- Test rate limiting
- Verify tenant isolation

## Troubleshooting

### Common Issues

#### 1. MCP Service Connection Failures
**Solution**: Ensure MCP servers are mocked in tests
```php
$this->mockMcpService(['trackCspViolation' => true]);
```

#### 2. Rate Limiting Interference
**Solution**: Clear rate limit caches before tests
```php
$this->clearSecurityCaches();
```

#### 3. Tenant Context Missing
**Solution**: Set tenant context explicitly
```php
app()->instance('tenant', $tenant);
```

#### 4. Performance Test Failures
**Solution**: Run performance tests in isolation
```bash
php artisan test tests/Performance/ --stop-on-failure
```

## Additional Resources

- [Laravel Testing Documentation](https://laravel.com/docs/12.x/testing)
- [Pest PHP Documentation](https://pestphp.com/)
- [Laravel Dusk Documentation](https://laravel.com/docs/12.x/dusk)
- [WCAG 2.1 Guidelines](https://www.w3.org/WAI/WCAG21/quickref/)
- [OWASP Security Headers](https://owasp.org/www-project-secure-headers/)

## Contact

For questions or issues with security tests, contact the security team or create an issue in the project repository.
