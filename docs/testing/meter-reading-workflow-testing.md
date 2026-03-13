# Meter Reading Workflow Testing Guide

## Overview

This guide covers testing strategies for the meter reading authorization system, focusing on the configurable workflow strategies (Permissive vs Truth-but-Verify) and tenant isolation requirements.

## Testing Categories

### 1. Unit Tests - Policy Authorization

#### Core Policy Methods

Test each authorization method with all role combinations:

```php
<?php

namespace Tests\Unit\Policies;

use App\Enums\UserRole;
use App\Enums\ValidationStatus;
use App\Models\MeterReading;
use App\Models\User;
use App\Policies\MeterReadingPolicy;
use App\Services\Workflows\PermissiveWorkflowStrategy;
use App\Services\Workflows\TruthButVerifyWorkflowStrategy;
use Tests\TestCase;

class MeterReadingPolicyWorkflowTest extends TestCase
{
    /** @test */
    public function permissive_workflow_allows_tenant_self_service(): void
    {
        $tenant = User::factory()->create(['role' => UserRole::TENANT, 'id' => 1]);
        $reading = MeterReading::factory()->create([
            'entered_by' => 1,
            'validation_status' => ValidationStatus::PENDING,
        ]);

        $policy = new MeterReadingPolicy(
            $this->app->make(TenantBoundaryService::class),
            new PermissiveWorkflowStrategy()
        );

        $this->assertTrue($policy->update($tenant, $reading));
        $this->assertTrue($policy->delete($tenant, $reading));
    }

    /** @test */
    public function permissive_workflow_prevents_modification_of_validated_readings(): void
    {
        $tenant = User::factory()->create(['role' => UserRole::TENANT, 'id' => 1]);
        $reading = MeterReading::factory()->create([
            'entered_by' => 1,
            'validation_status' => ValidationStatus::VALIDATED,
        ]);

        $policy = new MeterReadingPolicy(
            $this->app->make(TenantBoundaryService::class),
            new PermissiveWorkflowStrategy()
        );

        $this->assertFalse($policy->update($tenant, $reading));
        $this->assertFalse($policy->delete($tenant, $reading));
    }
}
```
#### Cross-Tenant Access Prevention

```php
/** @test */
public function prevents_cross_tenant_access_in_all_workflows(): void
{
    $tenant1 = User::factory()->create(['role' => UserRole::TENANT, 'tenant_id' => 1]);
    $tenant2 = User::factory()->create(['role' => UserRole::TENANT, 'tenant_id' => 2]);
    
    $reading = MeterReading::factory()->create([
        'entered_by' => $tenant1->id,
        'tenant_id' => 1,
        'validation_status' => ValidationStatus::PENDING,
    ]);

    $permissivePolicy = new MeterReadingPolicy(
        $this->app->make(TenantBoundaryService::class),
        new PermissiveWorkflowStrategy()
    );

    // Tenant 2 cannot access tenant 1's reading
    $this->assertFalse($permissivePolicy->view($tenant2, $reading));
    $this->assertFalse($permissivePolicy->update($tenant2, $reading));
    $this->assertFalse($permissivePolicy->delete($tenant2, $reading));
}
```

#### Workflow Strategy Comparison

```php
/** @test */
public function truth_but_verify_workflow_prevents_all_tenant_modifications(): void
{
    $tenant = User::factory()->create(['role' => UserRole::TENANT, 'id' => 1]);
    $reading = MeterReading::factory()->create([
        'entered_by' => 1,
        'validation_status' => ValidationStatus::PENDING,
    ]);

    $strictPolicy = new MeterReadingPolicy(
        $this->app->make(TenantBoundaryService::class),
        new TruthButVerifyWorkflowStrategy()
    );

    // Truth-but-Verify prevents all tenant modifications
    $this->assertFalse($strictPolicy->update($tenant, $reading));
    $this->assertFalse($strictPolicy->delete($tenant, $reading));
}
```

### 2. Integration Tests - Controller Endpoints

#### API Endpoint Testing

```php
<?php

namespace Tests\Feature\Api;

use App\Enums\ValidationStatus;
use App\Models\MeterReading;
use App\Models\User;
use Tests\TestCase;

class MeterReadingWorkflowIntegrationTest extends TestCase
{
    /** @test */
    public function tenant_can_update_own_pending_reading_via_api(): void
    {
        $tenant = User::factory()->tenant()->create();
        $reading = MeterReading::factory()->create([
            'entered_by' => $tenant->id,
            'validation_status' => ValidationStatus::PENDING,
            'tenant_id' => $tenant->tenant_id,
        ]);

        $this->actingAs($tenant)
            ->putJson("/api/meter-readings/{$reading->id}", [
                'value' => 1500.5,
                'notes' => 'Updated reading',
            ])
            ->assertOk()
            ->assertJson([
                'data' => [
                    'value' => 1500.5,
                    'notes' => 'Updated reading',
                ],
            ]);

        $this->assertDatabaseHas('meter_readings', [
            'id' => $reading->id,
            'value' => 1500.5,
            'notes' => 'Updated reading',
        ]);
    }

    /** @test */
    public function tenant_cannot_update_validated_reading_via_api(): void
    {
        $tenant = User::factory()->tenant()->create();
        $reading = MeterReading::factory()->create([
            'entered_by' => $tenant->id,
            'validation_status' => ValidationStatus::VALIDATED,
            'tenant_id' => $tenant->tenant_id,
        ]);

        $this->actingAs($tenant)
            ->putJson("/api/meter-readings/{$reading->id}", [
                'value' => 1500.5,
            ])
            ->assertForbidden()
            ->assertJson([
                'message' => 'This action is unauthorized.',
            ]);
    }
}
```
### 3. Filament Resource Testing

#### Resource Authorization

```php
<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\MeterReadingResource;
use App\Models\MeterReading;
use App\Models\User;
use Tests\TestCase;

class MeterReadingResourceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function tenant_can_edit_own_pending_reading_in_filament(): void
    {
        $tenant = User::factory()->tenant()->create();
        $reading = MeterReading::factory()->create([
            'entered_by' => $tenant->id,
            'validation_status' => ValidationStatus::PENDING,
        ]);

        $this->actingAs($tenant);

        livewire(MeterReadingResource\Pages\EditMeterReading::class, [
            'record' => $reading->id,
        ])
            ->fillForm([
                'value' => 2000.0,
                'notes' => 'Corrected reading',
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified('Saved successfully');

        $this->assertDatabaseHas('meter_readings', [
            'id' => $reading->id,
            'value' => 2000.0,
            'notes' => 'Corrected reading',
        ]);
    }

    /** @test */
    public function tenant_cannot_access_validated_reading_edit_page(): void
    {
        $tenant = User::factory()->tenant()->create();
        $reading = MeterReading::factory()->create([
            'entered_by' => $tenant->id,
            'validation_status' => ValidationStatus::VALIDATED,
        ]);

        $this->actingAs($tenant);

        $this->get(MeterReadingResource::getUrl('edit', ['record' => $reading]))
            ->assertForbidden();
    }
}
```

#### Table Actions Testing

```php
/** @test */
public function table_actions_respect_workflow_permissions(): void
{
    $tenant = User::factory()->tenant()->create();
    
    $pendingReading = MeterReading::factory()->create([
        'entered_by' => $tenant->id,
        'validation_status' => ValidationStatus::PENDING,
    ]);
    
    $validatedReading = MeterReading::factory()->create([
        'entered_by' => $tenant->id,
        'validation_status' => ValidationStatus::VALIDATED,
    ]);

    $this->actingAs($tenant);

    // Can edit pending reading
    livewire(MeterReadingResource\Pages\ListMeterReadings::class)
        ->assertTableActionVisible('edit', $pendingReading)
        ->assertTableActionVisible('delete', $pendingReading);

    // Cannot edit validated reading
    livewire(MeterReadingResource\Pages\ListMeterReadings::class)
        ->assertTableActionHidden('edit', $validatedReading)
        ->assertTableActionHidden('delete', $validatedReading);
}
```

### 4. Property-Based Testing

#### Tenant Isolation Properties

```php
<?php

namespace Tests\Property;

use App\Enums\UserRole;
use App\Enums\ValidationStatus;
use App\Models\MeterReading;
use App\Models\User;
use App\Policies\MeterReadingPolicy;
use Tests\TestCase;

class MeterReadingWorkflowPropertyTest extends TestCase
{
    /** @test */
    public function tenant_isolation_property(): void
    {
        $this->forAll(
            $this->tenantPairGenerator(),
            $this->meterReadingGenerator()
        )->then(function ($tenantPair, $reading) {
            [$tenant1, $tenant2] = $tenantPair;
            
            // Tenant 1 creates reading
            $reading->update(['entered_by' => $tenant1->id, 'tenant_id' => $tenant1->tenant_id]);
            
            $policy = app(MeterReadingPolicy::class);
            
            // Tenant 2 cannot access tenant 1's reading
            $this->assertFalse($policy->view($tenant2, $reading));
            $this->assertFalse($policy->update($tenant2, $reading));
            $this->assertFalse($policy->delete($tenant2, $reading));
        });
    }

    /** @test */
    public function workflow_consistency_property(): void
    {
        $this->forAll(
            $this->userGenerator(),
            $this->meterReadingGenerator(),
            $this->validationStatusGenerator()
        )->then(function ($user, $reading, $status) {
            $reading->update(['validation_status' => $status]);
            
            $policy = app(MeterReadingPolicy::class);
            
            // Workflow rules are consistent
            if ($user->role === UserRole::TENANT) {
                $canUpdate = $policy->update($user, $reading);
                $canDelete = $policy->delete($user, $reading);
                
                // In permissive workflow, tenant permissions are the same for update/delete
                $this->assertEquals($canUpdate, $canDelete);
                
                // Tenants can only modify their own pending readings
                if ($canUpdate) {
                    $this->assertEquals($user->id, $reading->entered_by);
                    $this->assertEquals(ValidationStatus::PENDING, $reading->validation_status);
                }
            }
        });
    }
}
```
### 5. Performance Testing

#### Authorization Performance

```php
/** @test */
public function authorization_performance_under_load(): void
{
    $tenant = User::factory()->tenant()->create();
    $readings = MeterReading::factory()->count(1000)->create([
        'entered_by' => $tenant->id,
        'validation_status' => ValidationStatus::PENDING,
    ]);

    $policy = app(MeterReadingPolicy::class);

    $startTime = microtime(true);
    
    foreach ($readings as $reading) {
        $policy->update($tenant, $reading);
    }
    
    $endTime = microtime(true);
    $executionTime = $endTime - $startTime;

    // Authorization should complete within reasonable time
    $this->assertLessThan(1.0, $executionTime, 'Authorization took too long');
}
```

## Test Data Generators

### Property-Based Test Generators

```php
<?php

namespace Tests\Support\Generators;

trait WorkflowTestGenerators
{
    protected function tenantPairGenerator(): \Generator
    {
        while (true) {
            yield [
                User::factory()->tenant()->create(['tenant_id' => 1]),
                User::factory()->tenant()->create(['tenant_id' => 2]),
            ];
        }
    }

    protected function meterReadingGenerator(): \Generator
    {
        while (true) {
            yield MeterReading::factory()->create();
        }
    }

    protected function validationStatusGenerator(): \Generator
    {
        $statuses = [
            ValidationStatus::PENDING,
            ValidationStatus::VALIDATED,
            ValidationStatus::REJECTED,
        ];

        while (true) {
            yield $statuses[array_rand($statuses)];
        }
    }

    protected function userGenerator(): \Generator
    {
        $roles = [UserRole::TENANT, UserRole::MANAGER, UserRole::ADMIN, UserRole::SUPERADMIN];

        while (true) {
            $role = $roles[array_rand($roles)];
            yield User::factory()->create(['role' => $role]);
        }
    }
}
```

## Test Configuration

### PHPUnit Configuration

```xml
<!-- phpunit.xml -->
<testsuites>
    <testsuite name="Workflow">
        <directory suffix="WorkflowTest.php">./tests/Unit/Policies</directory>
        <directory suffix="WorkflowTest.php">./tests/Feature</directory>
        <directory suffix="PropertyTest.php">./tests/Property</directory>
    </testsuite>
</testsuites>
```

### Pest Configuration

```php
// tests/Pest.php
uses(
    Tests\TestCase::class,
    Tests\Support\Generators\WorkflowTestGenerators::class,
    Illuminate\Foundation\Testing\RefreshDatabase::class
)->in('Feature', 'Unit', 'Property');
```

## Running Tests

### Command Examples

```bash
# Run all workflow tests
php artisan test --testsuite=Workflow

# Run specific workflow tests
php artisan test --filter=WorkflowTest

# Run property-based tests with coverage
php artisan test --filter=PropertyTest --coverage

# Run performance tests
php artisan test --filter=Performance --group=performance
```

### Continuous Integration

```yaml
# .github/workflows/workflow-tests.yml
name: Workflow Tests

on: [push, pull_request]

jobs:
  workflow-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.3
      - name: Install Dependencies
        run: composer install
      - name: Run Workflow Tests
        run: php artisan test --testsuite=Workflow --coverage
```

## Test Coverage Requirements

### Minimum Coverage Targets

- **Policy Methods**: 100% line coverage
- **Workflow Strategies**: 100% line coverage  
- **Integration Endpoints**: 95% line coverage
- **Filament Resources**: 90% line coverage

### Coverage Verification

```bash
# Generate coverage report
php artisan test --coverage --min=95

# Generate HTML coverage report
php artisan test --coverage-html=coverage-report
```

## Debugging Test Failures

### Common Issues

1. **Policy Not Applied**: Ensure policy is registered in `AuthServiceProvider`
2. **Workflow Strategy Not Injected**: Check service container bindings
3. **Tenant Context Missing**: Verify tenant setup in test data
4. **Cache Issues**: Clear policy cache between tests

### Debug Helpers

```php
// Add to test for debugging
protected function debugAuthorization(User $user, MeterReading $reading): void
{
    $policy = app(MeterReadingPolicy::class);
    
    dump([
        'user_role' => $user->role->value,
        'user_tenant' => $user->tenant_id,
        'reading_owner' => $reading->entered_by,
        'reading_status' => $reading->validation_status->value,
        'reading_tenant' => $reading->tenant_id,
        'can_update' => $policy->update($user, $reading),
        'workflow' => $policy->getWorkflowName(),
    ]);
}
```

## Related Documentation

- [MeterReadingPolicy API Reference](../api/policies/meter-reading-policy.md)
- [Workflow Strategies Guide](../usage/workflow-strategies.md)
- [Testing Standards](../../.kiro/steering/testing-standards.md)
- [Property-Based Testing Guide](property-based-testing.md)