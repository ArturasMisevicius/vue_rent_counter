# Admin Panel Testing Guide

## Overview

This document describes the testing strategy and available tests for the Filament admin panel.

## Test Suites

### AdminDashboardTest

**Location**: `tests/Feature/Filament/AdminDashboardTest.php`

Tests the admin dashboard functionality including:

- **Access Control**
  - Admins can access dashboard
  - Managers can access dashboard
  - Tenants cannot access dashboard
  - Guests are redirected to login

- **Dashboard Stats**
  - Displays correct property counts
  - Displays correct building counts
  - Displays active tenant counts
  - Shows draft invoice counts
  - Shows pending meter reading counts
  - Calculates monthly revenue correctly

- **Tenant Scoping**
  - Stats are properly scoped to user's tenant
  - Cross-tenant data is not visible
  - Empty tenants show zero counts

- **UI Elements**
  - Quick action links render correctly
  - Welcome message displays
  - Role-specific content shows appropriately

### AdminResourceAccessTest

**Location**: `tests/Feature/Filament/AdminResourceAccessTest.php`

Tests resource-level access control:

- **Admin Access**
  - Can access all resources (Properties, Buildings, Meters, etc.)
  - Can create, edit, and delete within tenant scope
  - Cannot access other tenant's data

- **Manager Access**
  - Can access operational resources
  - Cannot access user management
  - Limited to tenant-scoped data

- **Tenant Access**
  - Cannot access admin panel
  - All admin routes return 403

- **Guest Access**
  - Redirected to login for all resources

## Running Tests

### Run All Admin Tests
```bash
php artisan test --filter=Admin
```

### Run Dashboard Tests Only
```bash
php artisan test --filter=AdminDashboardTest
```

### Run Resource Access Tests Only
```bash
php artisan test --filter=AdminResourceAccessTest
```

### Run Specific Test
```bash
php artisan test --filter=test_admin_can_access_dashboard
```

## Test Helpers

### Authentication Helpers

Located in `tests/TestCase.php`:

```php
// Act as admin user
$admin = $this->actingAsAdmin();

// Act as manager user
$manager = $this->actingAsManager();

// Act as tenant user
$tenant = $this->actingAsTenant();
```

### Data Creation Helpers

```php
// Create test property
$property = $this->createTestProperty(['tenant_id' => $admin->tenant_id]);

// Create test meter reading
$reading = $this->createTestMeterReading(['meter_id' => $meter->id]);
```

## Test Data Setup

Tests use the `TestDatabaseSeeder` for consistent data:

```bash
# Rebuild test database
php artisan test:setup --fresh
```

## Coverage Areas

### Functional Coverage

- ✅ Dashboard rendering
- ✅ Role-based access control
- ✅ Tenant data isolation
- ✅ Resource CRUD operations
- ✅ Navigation visibility
- ✅ Stats calculations
- ✅ Quick actions

### Security Coverage

- ✅ Cross-tenant data leakage prevention
- ✅ Unauthorized access blocking
- ✅ Policy enforcement
- ✅ Session management
- ✅ CSRF protection

### UI Coverage

- ✅ Dashboard layout
- ✅ Welcome messages
- ✅ Stat cards
- ✅ Quick action buttons
- ✅ Navigation menus
- ✅ Error pages (403, 404)

## Common Test Patterns

### Testing Access Control

```php
public function test_admin_can_access_resource(): void
{
    $this->actingAsAdmin();
    
    $response = $this->get('/admin/properties');
    
    $response->assertStatus(200);
}

public function test_tenant_cannot_access_resource(): void
{
    $this->actingAsTenant();
    
    $response = $this->get('/admin/properties');
    
    $response->assertStatus(403);
}
```

### Testing Tenant Scoping

```php
public function test_data_is_tenant_scoped(): void
{
    $admin1 = $this->actingAsAdmin();
    $property1 = $this->createTestProperty(['tenant_id' => $admin1->tenant_id]);
    
    $admin2 = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 'tenant_' . uniqid(),
    ]);
    $property2 = Property::factory()->create(['tenant_id' => $admin2->tenant_id]);
    
    $response = $this->get('/admin/properties');
    
    // Should only see property1, not property2
    $this->assertEquals(1, Property::where('tenant_id', $admin1->tenant_id)->count());
}
```

### Testing Stats

```php
public function test_dashboard_shows_correct_counts(): void
{
    $admin = $this->actingAsAdmin();
    
    // Create test data
    $this->createTestProperty(['tenant_id' => $admin->tenant_id]);
    $this->createTestProperty(['tenant_id' => $admin->tenant_id]);
    
    $response = $this->get('/admin');
    
    $response->assertStatus(200);
    $this->assertEquals(2, Property::where('tenant_id', $admin->tenant_id)->count());
}
```

## Continuous Integration

Tests run automatically on:
- Pull requests
- Commits to main branch
- Nightly builds

### CI Configuration

```yaml
# .github/workflows/tests.yml
- name: Run Admin Tests
  run: php artisan test --filter=Admin --parallel
```

## Test Maintenance

### Adding New Tests

1. Create test method following naming convention: `test_description_of_behavior`
2. Use appropriate test helpers for authentication and data creation
3. Assert expected behavior
4. Verify tenant scoping where applicable

### Updating Tests

When adding new features:
1. Add corresponding tests
2. Update existing tests if behavior changes
3. Verify all tests pass: `php artisan test`
4. Update this documentation

## Debugging Failed Tests

### Common Issues

**404 Errors**
```bash
# Clear and rebuild routes
php artisan route:clear
php artisan route:cache
```

**403 Errors**
- Check user role in test
- Verify middleware configuration
- Check policy methods

**Database Issues**
```bash
# Rebuild test database
php artisan test:setup --fresh
php artisan migrate:fresh --seed
```

**Cache Issues**
```bash
# Clear all caches
php artisan optimize:clear
```

### Verbose Output

```bash
# Run with verbose output
php artisan test --filter=AdminDashboardTest -vvv
```

### Debug Specific Test

```bash
# Run single test with debugging
php artisan test --filter=test_admin_can_access_dashboard --stop-on-failure
```

## Performance Considerations

- Tests use in-memory SQLite for speed
- Database transactions rollback after each test
- Parallel execution supported: `--parallel`
- Selective test running: `--filter`

## Best Practices

1. **Isolation**: Each test should be independent
2. **Clarity**: Test names should describe behavior
3. **Coverage**: Test both success and failure cases
4. **Speed**: Keep tests fast by minimizing database operations
5. **Maintenance**: Update tests when features change

## Related Documentation

- [Admin Panel Guide](./ADMIN_PANEL_GUIDE.md)
- [Testing Guide](../tests/TESTING_GUIDE.md)
- [Frontend Documentation](../frontend/FRONTEND.md)
- [Routes Documentation](../routes/ROUTES_IMPLEMENTATION_COMPLETE.md)
