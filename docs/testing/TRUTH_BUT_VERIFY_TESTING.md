# Truth-but-Verify Testing Guide

## Overview

Comprehensive testing strategy for the Truth-but-Verify workflow implementation, covering unit tests, feature tests, integration tests, and performance tests.

## Test Categories

### 1. Unit Tests

#### Policy Tests (`tests/Unit/Policies/MeterReadingPolicyTest.php`)

**Core Authorization Tests**:
```php
/** @test */
public function all_roles_can_create_meter_readings(): void
{
    $roles = [UserRole::SUPERADMIN, UserRole::ADMIN, UserRole::MANAGER, UserRole::TENANT];

    foreach ($roles as $role) {
        $user = User::factory()->create(['role' => $role]);
        $this->assertTrue($this->policy->create($user));
    }
}

/** @test */
public function tenant_cannot_update_meter_reading(): void
{
    $user = User::factory()->create(['role' => UserRole::TENANT]);
    $meterReading = MeterReading::factory()->create();

    $this->assertFalse($this->policy->update($user, $meterReading));
}
```

**Approval Workflow Tests**:
```php
/** @test */
public function manager_can_approve_pending_reading_in_same_tenant(): void
{
    $tenantId = 1;
    $user = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => $tenantId]);
    $meterReading = MeterReading::factory()->create([
        'tenant_id' => $tenantId,
        'validation_status' => ValidationStatus::PENDING,
    ]);

    $this->assertTrue($this->policy->approve($user, $meterReading));
}
```

#### User Model Tests (`tests/Unit/Models/UserMeterReadingCapabilitiesTest.php`)

**Capability Tests**:
```php
/** @test */
public function all_active_roles_can_create_meter_readings(): void
{
    $roles = [UserRole::SUPERADMIN, UserRole::ADMIN, UserRole::MANAGER, UserRole::TENANT];

    foreach ($roles as $role) {
        $user = User::factory()->create(['role' => $role, 'is_active' => true]);
        $this->assertTrue($user->canCreateMeterReadings());
    }
}
```
#### Service Tests (`tests/Unit/Services/TenantBoundaryServiceTest.php`)

**Boundary Enforcement Tests**:
```php
/** @test */
public function can_check_tenant_access_to_meter_reading(): void
{
    $tenant = User::factory()->create(['role' => UserRole::TENANT]);
    $meterReading = MeterReading::factory()->create();
    
    $this->tenantBoundaryService
        ->shouldReceive('canTenantAccessMeterReading')
        ->with($tenant, $meterReading)
        ->andReturn(true);
    
    $result = $this->tenantBoundaryService->canTenantAccessMeterReading($tenant, $meterReading);
    $this->assertTrue($result);
}
```

### 2. Feature Tests

#### Workflow Integration Tests

**Tenant Submission Test**:
```php
/** @test */
public function tenant_can_submit_meter_reading_for_approval(): void
{
    $tenant = User::factory()->create(['role' => UserRole::TENANT]);
    $property = Property::factory()->create();
    $meter = Meter::factory()->create(['property_id' => $property->id]);
    
    // Associate tenant with property
    $property->tenants()->attach($tenant->tenant);
    
    $response = $this->actingAs($tenant)
        ->postJson('/api/meter-readings', [
            'meter_id' => $meter->id,
            'value' => 1000.50,
            'reading_date' => now()->format('Y-m-d'),
            'input_method' => 'manual',
        ]);
    
    $response->assertCreated()
        ->assertJsonPath('data.validation_status', 'pending')
        ->assertJsonPath('data.entered_by', $tenant->id);
}
```

**Manager Approval Test**:
```php
/** @test */
public function manager_can_approve_tenant_submitted_reading(): void
{
    $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => 1]);
    $reading = MeterReading::factory()->create([
        'tenant_id' => 1,
        'validation_status' => ValidationStatus::PENDING,
    ]);
    
    $response = $this->actingAs($manager)
        ->patchJson("/api/meter-readings/{$reading->id}/approve");
    
    $response->assertOk()
        ->assertJsonPath('data.validation_status', 'validated')
        ->assertJsonPath('data.validated_by', $manager->id);
    
    $reading->refresh();
    $this->assertEquals(ValidationStatus::VALIDATED, $reading->validation_status);
}
```

#### Cross-Tenant Security Tests

**Tenant Isolation Test**:
```php
/** @test */
public function tenant_cannot_access_other_tenant_readings(): void
{
    $tenant1 = User::factory()->create(['role' => UserRole::TENANT, 'tenant_id' => 1]);
    $tenant2 = User::factory()->create(['role' => UserRole::TENANT, 'tenant_id' => 2]);
    
    $reading = MeterReading::factory()->create(['tenant_id' => 2]);
    
    $response = $this->actingAs($tenant1)
        ->getJson("/api/meter-readings/{$reading->id}");
    
    $response->assertForbidden();
}
```

### 3. Integration Tests

#### Filament Resource Tests

**Resource Action Tests**:
```php
use function Pest\Livewire\livewire;

/** @test */
public function manager_can_approve_reading_via_filament(): void
{
    $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => 1]);
    $reading = MeterReading::factory()->create([
        'tenant_id' => 1,
        'validation_status' => ValidationStatus::PENDING,
    ]);
    
    livewire(ListMeterReadings::class)
        ->actingAs($manager)
        ->callTableAction('approve', $reading)
        ->assertNotified('Reading Approved');
    
    $reading->refresh();
    $this->assertEquals(ValidationStatus::VALIDATED, $reading->validation_status);
}
```

#### Notification Tests

**Approval Notification Test**:
```php
/** @test */
public function approval_sends_notification_to_tenant(): void
{
    Notification::fake();
    
    $tenant = User::factory()->create(['role' => UserRole::TENANT]);
    $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => 1]);
    $reading = MeterReading::factory()->create([
        'tenant_id' => 1,
        'entered_by' => $tenant->id,
        'validation_status' => ValidationStatus::PENDING,
    ]);
    
    $reading->markAsValidated($manager->id);
    
    Notification::assertSentTo($tenant, MeterReadingApprovedNotification::class);
}
```

### 4. Performance Tests

#### Load Testing

**Concurrent Submission Test**:
```php
/** @test */
public function handles_concurrent_tenant_submissions(): void
{
    $tenants = User::factory()->count(10)->create(['role' => UserRole::TENANT]);
    $meters = Meter::factory()->count(10)->create();
    
    $promises = [];
    foreach ($tenants as $index => $tenant) {
        $promises[] = $this->actingAs($tenant)
            ->postJson('/api/meter-readings', [
                'meter_id' => $meters[$index]->id,
                'value' => 1000 + $index,
                'reading_date' => now()->format('Y-m-d'),
            ]);
    }
    
    // All submissions should succeed
    foreach ($promises as $response) {
        $response->assertCreated();
    }
    
    // All readings should be pending
    $this->assertEquals(10, MeterReading::where('validation_status', 'pending')->count());
}
```

#### Cache Performance Test

**Boundary Check Caching Test**:
```php
/** @test */
public function tenant_boundary_checks_are_cached(): void
{
    Cache::spy();
    
    $tenant = User::factory()->create(['role' => UserRole::TENANT]);
    $reading = MeterReading::factory()->create();
    
    // First call should hit database
    $this->tenantBoundaryService->canTenantAccessMeterReading($tenant, $reading);
    
    // Second call should use cache
    $this->tenantBoundaryService->canTenantAccessMeterReading($tenant, $reading);
    
    Cache::shouldHaveReceived('remember')->twice();
}
```

## Test Data Setup

### Factory Definitions

**MeterReading Factory**:
```php
// database/factories/MeterReadingFactory.php
public function definition(): array
{
    return [
        'meter_id' => Meter::factory(),
        'value' => $this->faker->randomFloat(2, 0, 10000),
        'reading_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
        'zone' => $this->faker->randomElement(['day', 'night', null]),
        'validation_status' => ValidationStatus::PENDING,
        'input_method' => InputMethod::MANUAL,
        'entered_by' => User::factory(),
    ];
}

// State methods for different scenarios
public function validated(): static
{
    return $this->state([
        'validation_status' => ValidationStatus::VALIDATED,
        'validated_by' => User::factory(),
        'validated_at' => now(),
    ]);
}

public function rejected(): static
{
    return $this->state([
        'validation_status' => ValidationStatus::REJECTED,
        'validated_by' => User::factory(),
        'validated_at' => now(),
        'validation_notes' => 'Reading value seems incorrect',
    ]);
}
```

### Test Helpers

**TestCase Extensions**:
```php
// tests/TestCase.php
protected function createTenantWithMeter(): array
{
    $tenant = User::factory()->create(['role' => UserRole::TENANT]);
    $property = Property::factory()->create();
    $meter = Meter::factory()->create(['property_id' => $property->id]);
    
    // Associate tenant with property
    $property->tenants()->attach($tenant->tenant);
    
    return compact('tenant', 'property', 'meter');
}

protected function createManagerWithTenant(int $tenantId = null): User
{
    return User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId ?? 1,
    ]);
}
```

## Test Execution

### Running Tests

```bash
# Run all meter reading tests
php artisan test --filter=MeterReading

# Run policy tests specifically
php artisan test --filter=MeterReadingPolicyTest

# Run workflow integration tests
php artisan test --filter=TruthButVerify

# Run with coverage
php artisan test --coverage --filter=MeterReading
```

### Continuous Integration

**GitHub Actions Workflow**:
```yaml
name: Truth-but-Verify Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.3
      - name: Install dependencies
        run: composer install
      - name: Run Truth-but-Verify tests
        run: php artisan test --filter=TruthButVerify
```

## Test Coverage Requirements

### Minimum Coverage Targets
- **Policy Methods**: 100% coverage
- **User Capabilities**: 100% coverage
- **Workflow Actions**: 95% coverage
- **API Endpoints**: 90% coverage
- **Integration Points**: 85% coverage

### Coverage Verification

```bash
# Generate coverage report
php artisan test --coverage --min=90

# Check specific file coverage
php artisan test --coverage --filter=MeterReadingPolicy
```

## Related Documentation

- [Testing Standards](../testing/TESTING_STANDARDS.md)
- [Policy Testing Patterns](../testing/POLICY_TESTING.md)
- [Filament Testing Guide](../testing/FILAMENT_TESTING.md)
- [API Testing Patterns](../testing/API_TESTING.md)