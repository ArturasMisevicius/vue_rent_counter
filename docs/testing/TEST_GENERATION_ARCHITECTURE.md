# Test Generation Architecture

## Overview

This document outlines the architecture and implementation strategy for automated test generation in the Vilnius Utilities Billing Platform using the `gsferro/generate-tests-easy` package.

## Architecture Assessment

### High-Level Impact

The enhanced test generation configuration impacts multiple architectural layers:

1. **Configuration Layer**: Comprehensive test generation settings with multi-tenancy awareness
2. **Testing Infrastructure**: Template-based test generation with project-specific patterns
3. **Quality Assurance**: Automated coverage for controllers, models, services, Filament resources
4. **Security Layer**: Built-in tenant isolation and authorization testing
5. **Documentation Layer**: Auto-generated test documentation

### Boundaries & Coupling

**Clear Boundaries:**
- Test types separated (Feature/Unit/Performance/Security)
- Multi-tenancy boundary enforcement in all generated tests
- Filament resource testing isolated from business logic tests
- Policy testing separated from implementation tests

**Coupling Considerations:**
- Templates reference project-specific traits (`BelongsToTenant`, `TenantContext`)
- Hard dependency on Filament v4 API
- Tight coupling to Pest 3.x test framework
- Authentication helpers tied to project role structure

**Mitigation Strategies:**
- Use interfaces for service dependencies
- Abstract Filament-specific logic into dedicated test helpers
- Version-lock Pest and Filament dependencies
- Document breaking changes in upgrade guides

## Recommended Patterns

### 1. Test Generation Service Pattern

```php
namespace App\Services\Testing;

class TestGenerationService
{
    public function __construct(
        private ClassAnalyzer $analyzer,
        private TemplateRenderer $renderer,
        private TenantContextProvider $tenantContext
    ) {}

    public function generateTests(string $className, string $type): array
    {
        // Analyze target class
        $analysis = $this->analyzer->analyze($className);
        
        // Determine test requirements
        $requirements = $this->determineRequirements($analysis, $type);
        
        // Apply multi-tenancy rules
        $requirements = $this->applyTenancyRules($requirements);
        
        // Generate test file
        return $this->renderer->render($requirements);
    }

    private function applyTenancyRules(array $requirements): array
    {
        if ($this->usesBelongsToTenant($requirements['class'])) {
            $requirements['tests'][] = 'tenant_isolation';
            $requirements['tests'][] = 'hierarchical_scope';
        }
        
        return $requirements;
    }
}
```

### 2. Test Helper Trait Pattern

```php
namespace Tests\Concerns;

trait InteractsWithTenancy
{
    protected function setupTenantContext(): void
    {
        $this->tenant = Tenant::factory()->create();
        TenantContext::set($this->tenant);
    }

    protected function createTenantRecord(string $model, array $attributes = []): Model
    {
        return $model::factory()->create(array_merge(
            ['tenant_id' => $this->tenant->id],
            $attributes
        ));
    }

    protected function assertTenantIsolation(Model $model): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherRecord = $model::factory()->for($otherTenant)->create();

        $results = $model::all();

        $this->assertFalse($results->contains($otherRecord));
    }
}
```

### 3. Policy Test Generator Pattern

```php
namespace App\Services\Testing\Generators;

class PolicyTestGenerator
{
    public function generate(string $policyClass): string
    {
        $methods = $this->extractPolicyMethods($policyClass);
        
        $tests = [];
        foreach ($methods as $method) {
            $tests[] = $this->generateRoleTests($method);
            $tests[] = $this->generateTenantIsolationTests($method);
            $tests[] = $this->generateHierarchicalTests($method);
        }
        
        return $this->renderTemplate('policy', compact('tests'));
    }

    private function generateRoleTests(string $method): array
    {
        return [
            "allows superadmin to {$method}",
            "allows admin to {$method}",
            "denies tenant to {$method}",
        ];
    }
}
```

### 4. Filament Resource Test Pattern

```php
namespace Tests\Concerns;

trait InteractsWithFilamentResources
{
    protected function assertCanListRecords(string $resource, array $records): void
    {
        Livewire::test($resource::getPages()['index'])
            ->assertCanSeeTableRecords($records);
    }

    protected function assertTenantIsolationInTable(string $resource): void
    {
        $ownRecord = $this->createTenantRecord($resource::getModel());
        $otherRecord = $this->createOtherTenantRecord($resource::getModel());

        Livewire::test($resource::getPages()['index'])
            ->assertCanSeeTableRecords([$ownRecord])
            ->assertCanNotSeeTableRecords([$otherRecord]);
    }
}
```

## Scalability & Performance

### Test Generation Performance

**Optimization Strategies:**

1. **Caching Layer**
```php
class CachedClassAnalyzer implements ClassAnalyzer
{
    public function analyze(string $className): array
    {
        return Cache::remember(
            "test_analysis:{$className}",
            now()->addHour(),
            fn() => $this->analyzer->analyze($className)
        );
    }
}
```

2. **Batch Processing**
```php
class BatchTestGenerator
{
    public function generateBatch(array $classes): void
    {
        $chunks = array_chunk($classes, 10);
        
        foreach ($chunks as $chunk) {
            dispatch(new GenerateTestsJob($chunk));
        }
    }
}
```

3. **Parallel Execution**
```bash
# Run generated tests in parallel
php artisan test --parallel --processes=4
```

### N+1 Query Prevention

**Generated Test Assertions:**

```php
it('avoids N+1 queries when listing records', function () {
    {{ modelName }}::factory()->count(10)->create();

    $queryCount = 0;
    DB::listen(function () use (&$queryCount) {
        $queryCount++;
    });

    Livewire::test({{ resourceName }}\Pages\List{{ resourceNamePlural }}::class);

    expect($queryCount)->toBeLessThan(5); // Adjust threshold
});
```

### Test Execution Performance

**Configuration:**

```php
// phpunit.xml
<phpunit>
    <php>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
        <env name="CACHE_DRIVER" value="array"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
    </php>
</phpunit>
```

## Security, Accessibility & Localization

### Security Testing

**Generated Security Tests:**

1. **Authorization Tests**
```php
it('requires authorization for {{ action }}', function () {
    $this->actingAsTenant();
    
    $response = $this->{{ httpMethod }}(route('{{ route }}'));
    
    $response->assertForbidden();
});
```

2. **CSRF Protection**
```php
it('validates CSRF token on {{ action }}', function () {
    $response = $this->post(route('{{ route }}'), [], [
        'X-CSRF-TOKEN' => 'invalid-token'
    ]);
    
    $response->assertStatus(419);
});
```

3. **Input Sanitization**
```php
it('sanitizes XSS attempts in {{ field }}', function () {
    $xssPayload = '<script>alert("XSS")</script>';
    
    $response = $this->post(route('{{ route }}'), [
        '{{ field }}' => $xssPayload
    ]);
    
    $this->assertDatabaseMissing('{{ table }}', [
        '{{ field }}' => $xssPayload
    ]);
});
```

### Accessibility Testing

**Generated A11y Tests:**

```php
it('has accessible form labels', function () {
    Livewire::test({{ resourceName }}\Pages\Create{{ modelName }}::class)
        ->assertSee('aria-label')
        ->assertSee('for=');
});

it('supports keyboard navigation', function () {
    $this->get({{ resourceName }}::getUrl('index'))
        ->assertSee('tabindex');
});
```

### Localization Testing

**Generated i18n Tests:**

```php
it('displays content in all supported locales', function () {
    foreach (['en', 'lt', 'ru'] as $locale) {
        app()->setLocale($locale);
        
        $response = $this->get(route('{{ route }}'));
        
        $response->assertSee(__('{{ translationKey }}'));
    }
});

it('validates error messages in current locale', function () {
    app()->setLocale('lt');
    
    $response = $this->post(route('{{ route }}'), []);
    
    $response->assertSessionHasErrors();
    // Verify error message is in Lithuanian
});
```

## Data Model Implications

### Factory Requirements

**Generated Factory Tests:**

```php
it('factory creates valid {{ modelName }}', function () {
    ${{ resourceName }} = {{ modelName }}::factory()->create();

    expect(${{ resourceName }})->toBeInstanceOf({{ modelName }}::class);
    expect(${{ resourceName }}->tenant_id)->not->toBeNull();
    
    // Verify all required relationships
    expect(${{ resourceName }}->tenant)->toBeInstanceOf(Tenant::class);
});
```

### Index Verification

**Performance Tests:**

```php
it('uses index for tenant_id filtering', function () {
    {{ modelName }}::factory()->count(1000)->create();

    $query = {{ modelName }}::query()->toSql();
    
    // Verify query plan uses index
    $explain = DB::select("EXPLAIN {$query}");
    
    expect($explain)->toContain('using index');
});
```

### Migration Testing

**Generated Migration Tests:**

```php
it('can rollback {{ tableName }} migration', function () {
    Artisan::call('migrate:rollback', ['--step' => 1]);
    
    expect(Schema::hasTable('{{ tableName }}'))->toBeFalse();
    
    Artisan::call('migrate');
    
    expect(Schema::hasTable('{{ tableName }}'))->toBeTrue();
});
```

## Testing Plan

### Coverage Matrix

| Component Type | Unit Tests | Feature Tests | Integration Tests | Property Tests |
|---------------|------------|---------------|-------------------|----------------|
| Models | ✓ | ✓ | - | ✓ |
| Controllers | - | ✓ | ✓ | - |
| Services | ✓ | ✓ | - | ✓ |
| Filament Resources | - | ✓ | ✓ | - |
| Policies | ✓ | - | - | ✓ |
| Middleware | ✓ | ✓ | - | - |
| Observers | ✓ | ✓ | - | - |
| Value Objects | ✓ | - | - | ✓ |

### Test Execution Strategy

```bash
# 1. Run fast unit tests first
php artisan test --testsuite=Unit

# 2. Run feature tests
php artisan test --testsuite=Feature

# 3. Run performance tests
php artisan test --testsuite=Performance

# 4. Run security tests
php artisan test --testsuite=Security

# 5. Generate coverage report
php artisan test --coverage --min=80
```

### Observability

**Test Metrics Collection:**

```php
// tests/Pest.php
uses()->beforeEach(function () {
    $this->startTime = microtime(true);
})->afterEach(function () {
    $duration = microtime(true) - $this->startTime;
    
    if ($duration > 1.0) {
        Log::warning("Slow test detected", [
            'test' => $this->name(),
            'duration' => $duration
        ]);
    }
})->in('Feature', 'Unit');
```

## Risks & Technical Debt

### Identified Risks

1. **Template Maintenance**
   - Risk: Templates become outdated with framework upgrades
   - Mitigation: Version templates alongside framework versions
   - Priority: Medium

2. **Test Brittleness**
   - Risk: Generated tests break with minor code changes
   - Mitigation: Use flexible assertions, avoid implementation details
   - Priority: High

3. **Coverage Gaps**
   - Risk: Generated tests miss edge cases
   - Mitigation: Manual review and enhancement of generated tests
   - Priority: High

4. **Performance Degradation**
   - Risk: Large test suites slow down CI/CD
   - Mitigation: Parallel execution, selective test runs
   - Priority: Medium

5. **Dependency Coupling**
   - Risk: Tight coupling to Filament/Pest versions
   - Mitigation: Abstract framework-specific code
   - Priority: Low

### Technical Debt

1. **Missing Property Tests**
   - Debt: Not all invariants covered by property tests
   - Plan: Generate property test stubs for critical business logic
   - Timeline: Q1 2025

2. **Incomplete Accessibility Coverage**
   - Debt: A11y tests not comprehensive
   - Plan: Integrate automated accessibility testing tools
   - Timeline: Q2 2025

3. **Limited Performance Benchmarks**
   - Debt: Performance tests lack baseline comparisons
   - Plan: Establish performance baselines and regression detection
   - Timeline: Q1 2025

## Prioritized Next Steps

### Immediate (Week 1-2)

1. ✅ Create test stub templates
2. ✅ Configure test generation paths and namespaces
3. ⏳ Generate tests for core models (Building, Property, Meter)
4. ⏳ Generate tests for critical services (BillingService, TariffResolver)
5. ⏳ Verify tenant isolation in generated tests

### Short-term (Week 3-4)

6. Generate Filament resource tests
7. Generate policy tests for all resources
8. Add performance test generation
9. Implement test helper traits
10. Document test generation workflow

### Medium-term (Month 2-3)

11. Generate property tests for business invariants
12. Add accessibility test generation
13. Implement test metrics collection
14. Create CI/CD integration
15. Establish coverage baselines

### Long-term (Quarter 1-2)

16. Automated test maintenance
17. Test quality analysis
18. Performance regression detection
19. Security test automation
20. Comprehensive documentation

## Implementation Checklist

- [x] Enhanced configuration file
- [x] Created test stub templates
- [x] Documented architecture
- [ ] Generate initial test suite
- [ ] Verify test execution
- [ ] Measure coverage improvement
- [ ] Document usage patterns
- [ ] Train team on test generation
- [ ] Integrate with CI/CD
- [ ] Establish quality gates

## Conclusion

The enhanced test generation configuration provides a solid foundation for automated, comprehensive testing across the application. By following the patterns and strategies outlined in this document, the team can maintain high code quality while scaling the application efficiently.

Key success factors:
- Consistent use of test generation patterns
- Regular review and enhancement of generated tests
- Continuous monitoring of test quality and performance
- Team training on test generation best practices
- Integration with existing quality assurance processes
