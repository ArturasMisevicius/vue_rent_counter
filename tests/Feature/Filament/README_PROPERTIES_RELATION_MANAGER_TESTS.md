# PropertiesRelationManager Test Suite

**Component**: `app/Filament/Resources/BuildingResource/RelationManagers/PropertiesRelationManager.php`  
**Test Coverage**: Validation, Security, Behavior  
**Framework**: Pest PHP with Livewire Testing  
**Date**: 2025-11-23

---

## 📋 Overview

Comprehensive test suite covering the validation rule integration changes made to PropertiesRelationManager. The diff removed the tenant select field and integrated validation rules from `StorePropertyRequest` and `UpdatePropertyRequest` into the Filament form.

### Changes Tested

1. **Validation Integration**: Address, type, and area_sqm fields now use FormRequest validation rules
2. **Tenant Field Removal**: Tenant assignment moved to separate "Manage Tenant" action
3. **Security Enhancements**: XSS prevention, mass assignment protection, audit logging
4. **Enum Validation**: PropertyType enum validation with Rule::enum()

---

## 🧪 Test Files

### 1. PropertiesRelationManagerValidationTest.php

**Purpose**: Validates that Filament form validation matches FormRequest validation rules

**Test Categories**:
- **Address Field Validation** (6 tests)
  - Required validation
  - Max length (255 characters)
  - XSS prevention (script tags, javascript:, event handlers)
  - Invalid character rejection
  - Valid address acceptance

- **Type Field Validation** (3 tests)
  - Required validation
  - Enum validation (only APARTMENT|HOUSE)
  - Valid enum value acceptance

- **Area Field Validation** (8 tests)
  - Required validation
  - Numeric validation
  - Minimum value (0)
  - Maximum value (10000)
  - Decimal precision (max 2 places)
  - Scientific notation rejection
  - Valid decimal acceptance

- **Tenant Field Removal** (1 test)
  - Verifies tenant field is not in form schema

**Total Tests**: 18

**Run Command**:
```bash
php artisan test tests/Feature/Filament/PropertiesRelationManagerValidationTest.php
```

---

### 2. PropertiesRelationManagerSecurityTest.php

**Purpose**: Validates security features including mass assignment protection and audit logging

**Test Categories**:
- **Mass Assignment Protection** (4 tests)
  - Whitelisted fields only
  - tenant_id override prevention
  - building_id override prevention
  - Unauthorized field logging

- **Tenant Scope Isolation** (2 tests)
  - Automatic tenant scoping
  - Cross-tenant edit prevention

- **Audit Logging** (5 tests)
  - Tenant assignment logging
  - Tenant removal logging
  - Email masking (GDPR compliance)
  - IP address masking
  - Unauthorized access logging

**Total Tests**: 11

**Run Command**:
```bash
php artisan test tests/Feature/Filament/PropertiesRelationManagerSecurityTest.php
```

---

### 3. PropertiesRelationManagerBehaviorTest.php

**Purpose**: Validates behavioral features and user experience

**Test Categories**:
- **Default Area Behavior** (4 tests)
  - Apartment default area (from config)
  - House default area (from config)
  - Type change updates area
  - User can override defaults

- **Update Operations** (3 tests)
  - Update validation consistency
  - Valid update success
  - Preserves tenant_id and building_id

- **Localization** (2 tests)
  - Validation messages use translations
  - Form labels use translations

- **Notifications** (3 tests)
  - Create success notification
  - Update success notification
  - Delete success notification

**Total Tests**: 12

**Run Command**:
```bash
php artisan test tests/Feature/Filament/PropertiesRelationManagerBehaviorTest.php
```

---

## 📊 Coverage Summary

| Category | Tests | Focus |
|----------|-------|-------|
| **Validation** | 18 | FormRequest rule integration |
| **Security** | 11 | Mass assignment, audit logging, PII masking |
| **Behavior** | 12 | Defaults, updates, localization, notifications |
| **Total** | **41** | **Comprehensive coverage** |

---

## 🎯 Test Scenarios

### Happy Path
- ✅ Create property with valid data
- ✅ Update property with valid data
- ✅ Delete property
- ✅ Assign tenant to property
- ✅ Remove tenant from property
- ✅ Type change updates default area

### Edge Cases
- ✅ Empty/null values
- ✅ Boundary values (0, 10000)
- ✅ Decimal precision (2 places max)
- ✅ Scientific notation
- ✅ Special characters in address

### Error Cases
- ✅ XSS attempts (script tags, javascript:, event handlers)
- ✅ Invalid enum values
- ✅ Negative area values
- ✅ Excessive decimal precision
- ✅ Address too long (>255 chars)
- ✅ Mass assignment attempts
- ✅ Cross-tenant access attempts
- ✅ Unauthorized operations

---

## 🔒 Security Testing

### XSS Prevention
Tests validate that the following are rejected:
```php
'<script>alert("XSS")</script>'
'javascript:alert("XSS")'
'<img src=x onerror=alert("XSS")>'
'<div onclick=alert("XSS")>Test</div>'
```

### Mass Assignment Protection
Tests verify that unauthorized fields are:
1. Not saved to database
2. Logged with warning
3. Include user context (ID, email, IP)

Protected fields:
- `tenant_id` (auto-injected from auth user)
- `building_id` (auto-injected from owner record)

### Audit Logging
All tenant management operations log:
- Action type (assigned/removed)
- Property ID and address
- Previous/new tenant IDs
- User ID, masked email, role
- Masked IP address
- ISO 8601 timestamp

### PII Masking
- **Email**: `john.doe@example.com` → `jo***@example.com`
- **IP**: `192.168.1.100` → `192.168.1.xxx`

---

## 🏃 Running Tests

### All PropertiesRelationManager Tests
```bash
php artisan test tests/Feature/Filament/PropertiesRelationManager*
```

### Individual Test Files
```bash
# Validation tests
php artisan test tests/Feature/Filament/PropertiesRelationManagerValidationTest.php

# Security tests
php artisan test tests/Feature/Filament/PropertiesRelationManagerSecurityTest.php

# Behavior tests
php artisan test tests/Feature/Filament/PropertiesRelationManagerBehaviorTest.php
```

### Specific Test
```bash
php artisan test --filter="address field is required when creating property"
```

### With Coverage
```bash
php artisan test tests/Feature/Filament/PropertiesRelationManager* --coverage
```

### Parallel Execution
```bash
php artisan test tests/Feature/Filament/PropertiesRelationManager* --parallel
```

---

## 🔧 Test Setup

### Prerequisites
```bash
# Install dependencies
composer install

# Setup test database
php artisan test:setup --fresh

# Run migrations
php artisan migrate --env=testing
```

### Configuration
Tests use:
- `RefreshDatabase` trait for isolation
- Factory-generated test data
- Randomized tenant IDs to prevent collisions
- Session-based tenant context

### Test Helpers (TestCase.php)
```php
$this->actingAsAdmin()      // Authenticate as admin
$this->actingAsManager($id) // Authenticate as manager
$this->actingAsTenant($id)  // Authenticate as tenant
$this->createTestProperty() // Create test property
```

---

## 📝 Assertions Used

### Filament-Specific
- `assertHasTableActionErrors()` - Validation errors present
- `assertHasNoTableActionErrors()` - No validation errors
- `assertNotified()` - Success notification shown
- `callTableAction()` - Trigger table action
- `mountTableAction()` - Open action modal
- `setTableActionData()` - Set form data

### Standard Laravel
- `expect()->toBe()` - Exact value match
- `expect()->toBeNull()` - Null check
- `expect()->not->toBeNull()` - Not null check
- `expect()->toContain()` - Array contains value
- `expect()->toHaveKey()` - Array has key

### Log Assertions
- `Log::spy()` - Mock log facade
- `Log::shouldHaveReceived()` - Verify log call
- `\Mockery::on()` - Custom matcher

---

## 🐛 Debugging Failed Tests

### View Test Output
```bash
php artisan test --filter="test_name" --verbose
```

### Check Logs
```bash
tail -f storage/logs/laravel.log
```

### Database State
```bash
php artisan tinker
>>> Property::all()
>>> User::all()
```

### Livewire Component State
```php
$component = Livewire::test(...);
dd($component->instance()->mountedTableActionData);
```

---

## 📚 Related Documentation

- [Security Audit Report](../../../docs/security/PROPERTIES_RELATION_MANAGER_SECURITY_AUDIT.md)
- [Security Fixes Summary](../../../docs/security/SECURITY_FIXES_SUMMARY.md)
- [Testing Guide](../../../docs/guides/TESTING_GUIDE.md)
- [Filament Testing Docs](https://filamentphp.com/docs/3.x/panels/testing)
- [Pest PHP Docs](https://pestphp.com/docs)

---

## ✅ Quality Gates

All tests must pass before merging:
- ✅ No validation regressions
- ✅ Security controls enforced
- ✅ Audit logging functional
- ✅ PII masking working
- ✅ Localization complete
- ✅ No breaking changes

---

## 🔄 Maintenance

### Adding New Tests
1. Follow AAA pattern (Arrange, Act, Assert)
2. Use descriptive test names
3. Include test category comment
4. Use factories for test data
5. Clean up with RefreshDatabase

### Updating Tests
When modifying PropertiesRelationManager:
1. Update affected tests
2. Add new tests for new features
3. Run full test suite
4. Update this README

### Test Data
- Use `fake()` for random data
- Use `withoutGlobalScopes()` for cross-tenant setup
- Use `session(['tenant_id' => $id])` for tenant context
- Clean up with `RefreshDatabase` trait

---

**Last Updated**: 2025-11-23  
**Maintained By**: Development Team  
**Status**: ✅ Complete
