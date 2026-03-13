# PropertiesRelationManager Test Plan

**Component**: `app/Filament/Resources/BuildingResource/RelationManagers/PropertiesRelationManager.php`  
**Date**: 2025-11-23  
**Status**: Test Plan & Recommendations

---

## 📋 Changes Summary

### Diff Analysis

The recent changes to PropertiesRelationManager include:

1. **Removed tenant select field** from the form (moved to separate "Manage Tenant" action)
2. **Integrated validation rules** from `StorePropertyRequest` and `UpdatePropertyRequest`
3. **Added `validationAttribute`** to all form fields for consistent error messages
4. **Added `Rule::enum(PropertyType::class)`** for type field validation
5. **Enhanced validation messages** with localized strings from `lang/en/properties.php`

---

## 🎯 Recommended Test Coverage

### 1. Validation Integration Tests

**Purpose**: Ensure Filament form validation matches FormRequest validation

**Test Cases**:

#### Address Field
- ✅ Required validation (empty string should fail)
- ✅ Max length validation (256 chars should fail, 255 should pass)
- ✅ XSS prevention (script tags, javascript:, event handlers should fail)
- ✅ Invalid characters (special chars like @, $, %, & should fail)
- ✅ Valid addresses (alphanumeric + common punctuation should pass)
- ✅ HTML stripping (tags should be stripped via dehydrateStateUsing)

#### Type Field
- ✅ Required validation (null should fail)
- ✅ Enum validation (invalid values should fail)
- ✅ Valid enum values (APARTMENT, HOUSE should pass)
- ✅ Case sensitivity (lowercase values should be handled)

#### Area Field
- ✅ Required validation (null should fail)
- ✅ Numeric validation (non-numeric should fail)
- ✅ Minimum value (negative should fail, 0 should pass)
- ✅ Maximum value (10001 should fail, 10000 should pass)
- ✅ Decimal precision (3+ decimals should fail, 2 should pass)
- ✅ Scientific notation (5e2 should fail)
- ✅ Valid decimals (50, 50.5, 50.12 should pass)

---

### 2. Security Tests

**Purpose**: Validate security features from the security audit

**Test Cases**:

#### Mass Assignment Protection
- ✅ Only whitelisted fields saved (address, type, area_sqm)
- ✅ tenant_id auto-injected (cannot be overridden)
- ✅ building_id auto-injected (cannot be overridden)
- ✅ Unauthorized fields logged (is_premium, discount_rate, etc.)
- ✅ Log includes user context (ID, email, IP)

#### Tenant Scope Isolation
- ✅ Properties scoped to authenticated user's tenant
- ✅ Cross-tenant access prevented
- ✅ Manager cannot edit other tenant's properties
- ✅ Table records filtered by tenant_id

#### Audit Logging
- ✅ Tenant assignment logged with full context
- ✅ Tenant removal logged with previous tenant info
- ✅ Email addresses masked (jo***@example.com)
- ✅ IP addresses masked (192.168.1.xxx)
- ✅ Unauthorized access attempts logged
- ✅ ISO 8601 timestamps included

---

### 3. Behavioral Tests

**Purpose**: Validate user experience and feature behavior

**Test Cases**:

#### Default Area Behavior
- ✅ Apartment type sets default area from config
- ✅ House type sets default area from config
- ✅ Changing type updates area value
- ✅ User can override default area

#### Update Operations
- ✅ Update applies same validation as create
- ✅ Valid updates succeed
- ✅ tenant_id and building_id preserved on update
- ✅ Validation errors prevent update

#### Localization
- ✅ Validation messages use translation keys
- ✅ Form labels use translation keys
- ✅ Helper text uses translation keys
- ✅ Notifications use translation keys

#### Notifications
- ✅ Create success shows notification
- ✅ Update success shows notification
- ✅ Delete success shows notification
- ✅ Tenant assignment shows notification
- ✅ Tenant removal shows notification

---

### 4. Authorization Tests

**Purpose**: Validate policy integration

**Test Cases**:

- ✅ Manager can create properties in their building
- ✅ Manager can edit properties in their building
- ✅ Manager can delete properties in their building
- ✅ Manager can manage tenants for their properties
- ✅ Tenant cannot create properties
- ✅ Tenant cannot edit properties
- ✅ Tenant cannot delete properties
- ✅ Tenant cannot manage tenants
- ✅ Admin can perform all operations
- ✅ Superadmin can perform all operations

---

## 🧪 Testing Approach

### Unit Tests

**Focus**: Individual methods and validation rules

```php
// Example: Test validation rule extraction
test('address field has correct validation rules', function () {
    $relationManager = new PropertiesRelationManager();
    $field = $relationManager->getAddressField();
    
    $rules = $field->getRules();
    
    expect($rules)->toContain('required');
    expect($rules)->toContain('max:255');
    // ... more assertions
});
```

### Feature Tests

**Focus**: End-to-end workflows through Livewire

```php
// Example: Test property creation workflow
test('manager can create property with valid data', function () {
    $manager = User::factory()->manager()->create();
    $building = Building::factory()->create(['tenant_id' => $manager->tenant_id]);
    
    $this->actingAs($manager);
    
    Livewire::test(PropertiesRelationManager::class, [
        'ownerRecord' => $building,
    ])
    ->callTableAction('create', data: [
        'address' => '123 Main St',
        'type' => PropertyType::APARTMENT->value,
        'area_sqm' => 50.0,
    ])
    ->assertHasNoTableActionErrors()
    ->assertNotified();
    
    expect(Property::where('address', '123 Main St')->exists())->toBeTrue();
});
```

### Property-Based Tests

**Focus**: Invariants that must hold across random inputs

```php
// Example: Property-based test for tenant isolation
test('properties are always scoped to authenticated user tenant', function () {
    $tenantId1 = fake()->numberBetween(1, 1000);
    $tenantId2 = fake()->numberBetween(1001, 2000);
    
    // Create properties for both tenants
    $property1 = Property::factory()->create(['tenant_id' => $tenantId1]);
    $property2 = Property::factory()->create(['tenant_id' => $tenantId2]);
    
    $manager1 = User::factory()->create(['tenant_id' => $tenantId1]);
    
    $this->actingAs($manager1);
    session(['tenant_id' => $tenantId1]);
    
    // Property: Manager can only see their tenant's properties
    $visibleProperties = Property::all();
    
    expect($visibleProperties->pluck('id'))->toContain($property1->id);
    expect($visibleProperties->pluck('id'))->not->toContain($property2->id);
})->repeat(100);
```

---

## 🔧 Test Setup Requirements

### Database Setup

```bash
# Run migrations
php artisan migrate --env=testing

# Seed test data
php artisan test:setup --fresh
```

### Configuration

```php
// config/billing.php
'property' => [
    'default_apartment_area' => 50,
    'default_house_area' => 120,
    'min_area' => 0,
    'max_area' => 10000,
],
```

### Factories

```php
// PropertyFactory
Property::factory()->create([
    'tenant_id' => $tenantId,
    'building_id' => $buildingId,
    'address' => fake()->address(),
    'type' => PropertyType::APARTMENT,
    'area_sqm' => 50.0,
]);

// BuildingFactory
Building::factory()->create([
    'tenant_id' => $tenantId,
    'address' => fake()->address(),
    'total_apartments' => fake()->numberBetween(10, 100),
]);
```

---

## 📊 Coverage Goals

| Category | Target | Priority |
|----------|--------|----------|
| **Validation** | 100% | High |
| **Security** | 100% | Critical |
| **Authorization** | 100% | High |
| **Behavior** | 90% | Medium |
| **Edge Cases** | 80% | Medium |

---

## 🚨 Known Testing Challenges

### 1. Filament Livewire Context

**Issue**: Validation closures require proper Livewire context

**Solution**: Use `Livewire::test()` with proper component setup:

```php
Livewire::test(PropertiesRelationManager::class, [
    'ownerRecord' => $building,
    'pageClass' => BuildingResource\Pages\EditBuilding::class,
])
```

### 2. Validation Closure Parameters

**Issue**: Custom validation closures expect `$attribute`, `$value`, `$fail` parameters

**Solution**: Test validation through actual form submission, not direct closure invocation

### 3. Tenant Context

**Issue**: Tests need proper tenant context for scoping

**Solution**: Always set session tenant_id:

```php
$this->actingAs($manager);
session(['tenant_id' => $manager->tenant_id]);
```

### 4. Global Scopes

**Issue**: Global scopes interfere with cross-tenant test setup

**Solution**: Use `withoutGlobalScopes()` when creating test data:

```php
Property::withoutGlobalScopes()->create([...]);
```

---

## 🔍 Manual Testing Checklist

### Validation Testing

- [ ] Create property with empty address → Should show "required" error
- [ ] Create property with 256-char address → Should show "max" error
- [ ] Create property with `<script>alert('XSS')</script>` → Should show error
- [ ] Create property with invalid type → Should show "enum" error
- [ ] Create property with negative area → Should show "min" error
- [ ] Create property with 10001 area → Should show "max" error
- [ ] Create property with 50.123 area → Should show "precision" error
- [ ] Create property with valid data → Should succeed

### Security Testing

- [ ] Inspect database after create → tenant_id should match auth user
- [ ] Inspect database after create → building_id should match owner record
- [ ] Check logs after unauthorized field attempt → Warning should be logged
- [ ] Check logs after tenant assignment → Info log with masked PII
- [ ] Try to edit other tenant's property → Should fail

### Behavior Testing

- [ ] Select apartment type → Area should default to 50
- [ ] Select house type → Area should default to 120
- [ ] Change from apartment to house → Area should update to 120
- [ ] Override default area → Custom value should be saved
- [ ] Update property → Changes should be saved
- [ ] Delete property → Property should be removed

---

## 📚 Testing Resources

### Documentation
- [Filament Testing Docs](https://filamentphp.com/docs/3.x/panels/testing)
- [Livewire Testing Docs](https://livewire.laravel.com/docs/testing)
- [Pest PHP Docs](https://pestphp.com/docs)
- [Laravel Testing Docs](https://laravel.com/docs/testing)

### Related Files
- `app/Http/Requests/StorePropertyRequest.php` - Validation rules source
- `app/Http/Requests/UpdatePropertyRequest.php` - Update validation rules
- `app/Policies/PropertyPolicy.php` - Authorization logic
- `lang/en/properties.php` - Localized strings
- `config/billing.php` - Default area configuration

### Existing Tests
- `tests/Feature/FilamentPropertyResourceTenantScopeTest.php` - Tenant scope examples
- `tests/Feature/FilamentPropertyValidationConsistencyPropertyTest.php` - Validation examples
- `tests/Security/PropertiesRelationManagerSecurityTest.php` - Security test examples

---

## ✅ Test Implementation Priority

### Phase 1: Critical (Week 1)
1. ✅ Validation integration tests (address, type, area)
2. ✅ Mass assignment protection tests
3. ✅ Tenant scope isolation tests
4. ✅ Authorization tests

### Phase 2: Important (Week 2)
1. ✅ Audit logging tests
2. ✅ PII masking tests
3. ✅ Default area behavior tests
4. ✅ Update operation tests

### Phase 3: Nice-to-Have (Week 3)
1. ✅ Localization tests
2. ✅ Notification tests
3. ✅ Edge case tests
4. ✅ Performance tests

---

## 🎓 Best Practices

### Test Naming
```php
// Good: Descriptive, behavior-focused
test('address field rejects XSS attempts with script tags')

// Bad: Implementation-focused
test('address validation rule works')
```

### Test Structure (AAA Pattern)
```php
test('example test', function () {
    // Arrange: Set up test data
    $manager = User::factory()->manager()->create();
    $building = Building::factory()->create();
    
    // Act: Perform the action
    $result = $manager->createProperty($building, [...]);
    
    // Assert: Verify the outcome
    expect($result)->toBeTrue();
    expect(Property::count())->toBe(1);
});
```

### Test Isolation
```php
// Always use RefreshDatabase
uses(RefreshDatabase::class);

// Clean up after each test
afterEach(function () {
    // Cleanup code if needed
});
```

### Test Data
```php
// Use factories for consistency
$property = Property::factory()->create();

// Use fake() for random data
$address = fake()->address();

// Use specific values for assertions
$area = 50.0; // Not fake()->randomFloat()
```

---

## 🔄 Maintenance

### When to Update Tests

1. **Validation rules change** → Update validation tests
2. **New fields added** → Add field-specific tests
3. **Security features added** → Add security tests
4. **Behavior changes** → Update behavior tests
5. **Bugs fixed** → Add regression tests

### Test Review Checklist

- [ ] All tests pass
- [ ] No skipped tests
- [ ] Coverage meets goals
- [ ] Tests are isolated
- [ ] Tests are deterministic
- [ ] Tests are fast (<5s per test)
- [ ] Tests are readable
- [ ] Tests follow AAA pattern

---

**Last Updated**: 2025-11-23  
**Maintained By**: Development Team  
**Status**: ✅ Test Plan Complete
