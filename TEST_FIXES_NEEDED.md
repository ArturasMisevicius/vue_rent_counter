# Test Fixes Needed

## Summary
Created 7 new model tests but they need fixes based on actual model structures.

## Tests Created
1. ✅ UserTest.php - PASSED (needs factory state methods)
2. ✅ BuildingTest.php - PASSED (needs factory and scope methods)
3. ✅ InvoiceItemTest.php - PASSED (needs factory)
4. ⚠️ LanguageTest.php - 2 FAILURES (cache invalidation tests)
5. ❌ SubscriptionTest.php - 12 FAILURES (wrong schema assumptions)
6. ❌ TariffZoneTest.php - 11 FAILURES (TariffZone is an ENUM, not a Model!)
7. ❌ TranslationTest.php - 11 FAILURES (wrong schema - uses `values` JSON, not `value` + `language_id`)
8. ❌ BillingServiceTest.php - 15 FAILURES (methods don't exist in BillingService)

## Issues to Fix

### 1. TariffZoneTest.php - DELETE THIS FILE
- TariffZone is an ENUM (`app/Enums/TariffZone.php`), not a Model
- No need for model tests
- **Action**: Delete `tests/Unit/Models/TariffZoneTest.php`

### 2. SubscriptionTest.php - FIX SCHEMA
**Actual Schema:**
```php
$fillable = [
    'user_id',
    'plan_type',      // NOT 'plan_name'
    'status',         // NEW
    'starts_at',
    'expires_at',     // NOT 'ends_at'
    'max_properties',
    'max_tenants',    // NOT 'max_buildings', 'max_meters'
];
```

**Fixes Needed:**
- Update fillable test
- Remove `max_buildings`, `max_meters` references
- Change `ends_at` to `expires_at`
- Change `plan_name` to `plan_type`
- Remove `is_active` (it's computed from status + expires_at)
- Update all scope tests to use correct fields
- Update factory tests

### 3. TranslationTest.php - FIX SCHEMA
**Actual Schema:**
```php
$fillable = [
    'group',
    'key',
    'values',  // JSON array, NOT 'value' + 'language_id'
];

$casts = [
    'values' => 'array',  // Contains translations for all languages
];
```

**Structure:**
```json
{
    "group": "auth",
    "key": "login.title",
    "values": {
        "en": "Login",
        "lt": "Prisijungti",
        "ru": "Войти"
    }
}
```

**Fixes Needed:**
- Remove `language_id` references
- Remove `value` references
- Update to use `values` JSON structure
- Remove `belongsTo(Language)` relationship test
- Remove `forLanguage()` scope test
- Remove `byKey()` and `byGroup()` scope tests (they don't exist)
- Update factory test to check `values` is array

### 4. BillingServiceTest.php - FIX METHOD CALLS
**Methods that DON'T exist:**
- `calculateConsumption()`
- `calculateAmount()`
- `getMeterReadingsForPeriod()`
- `createInvoiceItemsForMeters()`
- `snapshotTariffRate()`
- `calculateTotalInvoiceAmount()`
- `recalculateDraftInvoice()`
- `getApplicableTariff()`
- `validateBillingPeriod()`

**Methods that DO exist:**
- `generateInvoice()` - Creates invoice with items
- `finalizeInvoice()` - Finalizes draft invoice

**Fixes Needed:**
- Check actual BillingService methods
- Rewrite tests to use actual methods
- Or mark tests as skipped until methods are implemented

### 5. MeterType Enum - FIX CONSTANTS
**Actual values:**
```php
case ELECTRICITY = 'electricity';
case WATER_COLD = 'water_cold';   // NOT WATER
case WATER_HOT = 'water_hot';
case HEATING = 'heating';
```

**Fixes Needed:**
- Change `MeterType::WATER` to `MeterType::WATER_COLD` or `MeterType::WATER_HOT`

### 6. LanguageTest.php - MINOR FIXES
**Issues:**
- Cache invalidation tests expect `remember()` to be called but it's not
- Delete test expects error but observer prevents deleting last active language

**Fixes Needed:**
- Fix or skip cache invalidation tests
- Update delete test to create multiple languages first

## Current Test Status
- **Total Tests**: 3,088
- **Passed**: 1,688
- **Failed**: 1,400
- **New Tests Added**: 75 (7 files × ~11 tests each)
- **New Tests Passing**: ~15
- **New Tests Failing**: ~60

## Next Steps
1. Delete TariffZoneTest.php
2. Fix SubscriptionTest.php schema
3. Fix TranslationTest.php schema
4. Fix or remove BillingServiceTest.php
5. Fix MeterType references
6. Fix LanguageTest.php minor issues
7. Create missing factories if needed
8. Run tests again to verify fixes
