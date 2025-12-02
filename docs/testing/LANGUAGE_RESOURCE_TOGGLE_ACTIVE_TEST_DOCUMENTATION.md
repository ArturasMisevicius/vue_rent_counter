# Language Resource Toggle Active Test Documentation

## Overview

This document provides comprehensive documentation for the `LanguageResourceToggleActiveTest` test suite, which validates the toggle active/inactive functionality in the LanguageResource.

**Test File**: `tests/Feature/Filament/LanguageResourceToggleActiveTest.php`  
**Resource Under Test**: `App\Filament\Resources\LanguageResource`  
**Total Tests**: 16  
**Test Groups**: `filament`, `language`, `toggle-active`, `namespace-consolidation`

## Table of Contents

1. [Test Suite Overview](#test-suite-overview)
2. [Test Coverage](#test-coverage)
3. [Business Rules Validated](#business-rules-validated)
4. [Test Execution](#test-execution)
5. [Test Details](#test-details)
6. [Related Documentation](#related-documentation)

## Test Suite Overview

The `LanguageResourceToggleActiveTest` suite provides comprehensive validation of the toggle active status functionality in the LanguageResource. This includes:

- **Namespace Consolidation**: Verifies proper use of Filament v4 consolidated namespaces
- **Functional Testing**: Validates core toggle functionality for individual and bulk operations
- **UI Element Testing**: Ensures dynamic labels, icons, and colors are correct
- **Security Testing**: Validates authorization and default language protection
- **Business Logic**: Confirms critical business rules are enforced

## Test Coverage

### Coverage Summary

| Category | Tests | Description |
|----------|-------|-------------|
| Namespace Consolidation | 3 | Verifies consolidated namespace usage |
| Functional Tests | 6 | Tests core toggle functionality |
| UI Element Tests | 6 | Validates dynamic UI elements |
| Authorization Tests | 1 | Verifies role-based access control |
| **Total** | **16** | **Complete test coverage** |

### Detailed Coverage

#### 1. Namespace Consolidation Tests (3 tests)

These tests verify that the LanguageResource uses the Filament v4 consolidated namespace pattern instead of individual imports.

**Tests**:
- `toggle_active_action_uses_consolidated_namespace()`
- `bulk_activate_action_uses_consolidated_namespace()`
- `bulk_deactivate_action_uses_consolidated_namespace()`

**Pattern Verified**:
```php
use Filament\Tables;

// Individual actions
Tables\Actions\Action::make('toggle_active')

// Bulk actions
Tables\Actions\BulkAction::make('activate')
Tables\Actions\BulkAction::make('deactivate')
```

#### 2. Functional Tests (6 tests)

These tests validate the core functionality of toggling language active status.

**Tests**:
- `can_toggle_active_language_to_inactive()` - Toggle active → inactive
- `can_toggle_inactive_language_to_active()` - Toggle inactive → active
- `cannot_deactivate_default_language_via_toggle()` - Default language protection (individual)
- `can_bulk_activate_multiple_languages()` - Bulk activate multiple languages
- `can_bulk_deactivate_multiple_languages()` - Bulk deactivate multiple languages
- `bulk_deactivate_prevents_deactivating_default_language()` - Default language protection (bulk)

#### 3. UI Element Tests (6 tests)

These tests ensure that dynamic UI elements (labels, icons, colors) are correctly configured based on language state.

**Tests**:
- `toggle_action_shows_deactivate_label_for_active_language()` - Label: "Deactivate"
- `toggle_action_shows_activate_label_for_inactive_language()` - Label: "Activate"
- `toggle_action_uses_correct_icon_for_active_language()` - Icon: heroicon-o-x-circle
- `toggle_action_uses_correct_icon_for_inactive_language()` - Icon: heroicon-o-check-circle
- `toggle_action_uses_correct_color_for_active_language()` - Color: danger (red)
- `toggle_action_uses_correct_color_for_inactive_language()` - Color: success (green)

#### 4. Authorization Tests (1 test)

This test validates role-based access control for language management.

**Test**:
- `only_superadmin_can_access_toggle_actions()` - Verifies SUPERADMIN-only access

## Business Rules Validated

### 1. Default Language Protection

**Rule**: The default language cannot be deactivated.

**Implementation**:
- **Individual Toggle**: Action is hidden for active default languages
- **Bulk Deactivate**: Exception thrown if default language is in selection

**Rationale**: The application must always have an active default language for fallback purposes. Deactivating the default language would break the system.

**Tests**:
- `cannot_deactivate_default_language_via_toggle()`
- `bulk_deactivate_prevents_deactivating_default_language()`

### 2. Confirmation Required

**Rule**: All toggle actions require user confirmation.

**Implementation**: All actions use `->requiresConfirmation()`

**Rationale**: Prevents accidental state changes and gives users an opportunity to review the action before execution.

**Verified In**: All functional tests

### 3. Authorization

**Rule**: Only superadmins can access language management.

**Implementation**: 
- Resource-level: `shouldRegisterNavigation()` checks for SUPERADMIN role
- Policy-level: `LanguagePolicy` provides additional authorization

**Rationale**: Language configuration is a system-level setting that should only be modified by superadmins.

**Test**: `only_superadmin_can_access_toggle_actions()`

### 4. Dynamic UI Feedback

**Rule**: UI elements should clearly indicate the current state and action.

**Implementation**:
- Active languages show "Deactivate" with red X-circle icon
- Inactive languages show "Activate" with green check-circle icon

**Rationale**: Clear visual feedback helps prevent user errors and improves UX.

**Tests**: All 6 UI element tests

## Test Execution

### Run All Tests

```bash
# Run the complete test suite
php artisan test --filter=LanguageResourceToggleActiveTest

# Expected output: 16 tests, 16 assertions
```

### Run Specific Test Groups

```bash
# Run only namespace consolidation tests
php artisan test --filter=LanguageResourceToggleActiveTest --group=namespace-consolidation

# Run only functional tests
php artisan test --filter=LanguageResourceToggleActiveTest --exclude-group=namespace-consolidation
```

### Run Individual Tests

```bash
# Run a specific test
php artisan test --filter=LanguageResourceToggleActiveTest::can_toggle_active_language_to_inactive

# Run with verbose output
php artisan test --filter=LanguageResourceToggleActiveTest::can_toggle_active_language_to_inactive -v
```

### Run with Coverage

```bash
# Generate coverage report
php artisan test --filter=LanguageResourceToggleActiveTest --coverage

# Generate HTML coverage report
php artisan test --filter=LanguageResourceToggleActiveTest --coverage-html coverage/
```

## Test Details

### Test 1: toggle_active_action_uses_consolidated_namespace

**Purpose**: Verify namespace consolidation for individual toggle action

**Setup**:
- Create a language with `is_active = true`
- Authenticate as superadmin

**Assertions**:
- Action exists with name `toggle_active`
- Action is instance of `\Filament\Tables\Actions\Action`

**Expected Result**: ✅ Pass

---

### Test 2: can_toggle_active_language_to_inactive

**Purpose**: Verify active language can be deactivated

**Setup**:
- Create language: `is_active = true`, `is_default = false`
- Authenticate as superadmin

**Action**: Call `toggle_active` action on the language

**Assertions**:
- Database has language with `is_active = false`

**Expected Result**: ✅ Pass

---

### Test 3: can_toggle_inactive_language_to_active

**Purpose**: Verify inactive language can be activated

**Setup**:
- Create language: `is_active = false`, `is_default = false`
- Authenticate as superadmin

**Action**: Call `toggle_active` action on the language

**Assertions**:
- Database has language with `is_active = true`

**Expected Result**: ✅ Pass

---

### Test 4: cannot_deactivate_default_language_via_toggle

**Purpose**: Verify default language protection (individual toggle)

**Setup**:
- Create language: `is_active = true`, `is_default = true`
- Authenticate as superadmin

**Assertions**:
- `toggle_active` action is hidden for the default language

**Expected Result**: ✅ Pass

---

### Test 5: bulk_activate_action_uses_consolidated_namespace

**Purpose**: Verify namespace consolidation for bulk activate action

**Setup**:
- Create 2 languages with `is_active = false`
- Authenticate as superadmin

**Assertions**:
- Bulk action exists with name `activate`
- Action is instance of `\Filament\Tables\Actions\BulkAction`

**Expected Result**: ✅ Pass

---

### Test 6: can_bulk_activate_multiple_languages

**Purpose**: Verify multiple languages can be activated simultaneously

**Setup**:
- Create 3 languages with `is_active = false`
- Authenticate as superadmin

**Action**: Call `activate` bulk action on all languages

**Assertions**:
- All 3 languages have `is_active = true` in database

**Expected Result**: ✅ Pass

---

### Test 7: bulk_deactivate_action_uses_consolidated_namespace

**Purpose**: Verify namespace consolidation for bulk deactivate action

**Setup**:
- Create 2 languages: `is_active = true`, `is_default = false`
- Authenticate as superadmin

**Assertions**:
- Bulk action exists with name `deactivate`
- Action is instance of `\Filament\Tables\Actions\BulkAction`

**Expected Result**: ✅ Pass

---

### Test 8: can_bulk_deactivate_multiple_languages

**Purpose**: Verify multiple non-default languages can be deactivated simultaneously

**Setup**:
- Create 3 languages: `is_active = true`, `is_default = false`
- Authenticate as superadmin

**Action**: Call `deactivate` bulk action on all languages

**Assertions**:
- All 3 languages have `is_active = false` in database

**Expected Result**: ✅ Pass

---

### Test 9: bulk_deactivate_prevents_deactivating_default_language

**Purpose**: Verify default language protection (bulk deactivate)

**Setup**:
- Create default language: `is_active = true`, `is_default = true`
- Create other language: `is_active = true`, `is_default = false`
- Authenticate as superadmin

**Action**: Attempt to bulk deactivate both languages

**Assertions**:
- Notification is shown (exception occurred)
- Default language remains `is_active = true`

**Expected Result**: ✅ Pass

---

### Test 10: toggle_action_shows_deactivate_label_for_active_language

**Purpose**: Verify correct label for active language

**Setup**:
- Create language: `is_active = true`, `is_default = false`
- Authenticate as superadmin

**Assertions**:
- Action label contains "Deactivate"

**Expected Result**: ✅ Pass

---

### Test 11: toggle_action_shows_activate_label_for_inactive_language

**Purpose**: Verify correct label for inactive language

**Setup**:
- Create language: `is_active = false`, `is_default = false`
- Authenticate as superadmin

**Assertions**:
- Action label contains "Activate"

**Expected Result**: ✅ Pass

---

### Test 12: toggle_action_uses_correct_icon_for_active_language

**Purpose**: Verify correct icon for active language

**Setup**:
- Create language: `is_active = true`, `is_default = false`
- Authenticate as superadmin

**Assertions**:
- Action icon equals `heroicon-o-x-circle`

**Expected Result**: ✅ Pass

---

### Test 13: toggle_action_uses_correct_icon_for_inactive_language

**Purpose**: Verify correct icon for inactive language

**Setup**:
- Create language: `is_active = false`, `is_default = false`
- Authenticate as superadmin

**Assertions**:
- Action icon equals `heroicon-o-check-circle`

**Expected Result**: ✅ Pass

---

### Test 14: toggle_action_uses_correct_color_for_active_language

**Purpose**: Verify correct color for active language

**Setup**:
- Create language: `is_active = true`, `is_default = false`
- Authenticate as superadmin

**Assertions**:
- Action color equals `danger` (red)

**Expected Result**: ✅ Pass

---

### Test 15: toggle_action_uses_correct_color_for_inactive_language

**Purpose**: Verify correct color for inactive language

**Setup**:
- Create language: `is_active = false`, `is_default = false`
- Authenticate as superadmin

**Assertions**:
- Action color equals `success` (green)

**Expected Result**: ✅ Pass

---

### Test 16: only_superadmin_can_access_toggle_actions

**Purpose**: Verify authorization (SUPERADMIN only)

**Setup**:
- Create language with `is_active = true`
- Create ADMIN user (not SUPERADMIN)
- Authenticate as ADMIN

**Action**: Attempt to access LanguageResource list page

**Assertions**:
- Response is 403 Forbidden

**Expected Result**: ✅ Pass

## Related Documentation

### Implementation Documentation
- **Resource**: `app/Filament/Resources/LanguageResource.php`
- **Model**: `app/Models/Language.php`
- **Policy**: `app/Policies/LanguagePolicy.php`

### API Documentation
- **API Reference**: `docs/filament/LANGUAGE_RESOURCE_TOGGLE_ACTIVE_API.md`
- **Resource API**: `docs/filament/LANGUAGE_RESOURCE_API.md`

### Testing Documentation
- **Verification Guide**: `docs/testing/LANGUAGE_RESOURCE_TOGGLE_ACTIVE_VERIFICATION.md`
- **Quick Reference**: `docs/testing/LANGUAGE_RESOURCE_TOGGLE_ACTIVE_QUICK_REFERENCE.md`
- **Summary**: `docs/testing/LANGUAGE_RESOURCE_TOGGLE_ACTIVE_SUMMARY.md`

### Specification Documentation
- **Tasks**: `.kiro/specs/6-filament-namespace-consolidation/tasks.md`
- **Requirements**: `.kiro/specs/6-filament-namespace-consolidation/requirements.md`
- **Design**: `.kiro/specs/6-filament-namespace-consolidation/design.md`

## Troubleshooting

### Common Issues

#### Issue: Tests fail with "Action not found"

**Cause**: The action name might be incorrect or the action is not registered.

**Solution**: Verify the action name in `LanguageResource.php` matches the test.

#### Issue: Tests fail with "Forbidden" error

**Cause**: User authentication or role assignment is incorrect.

**Solution**: Ensure the test creates a SUPERADMIN user and authenticates properly.

#### Issue: Tests fail with namespace errors

**Cause**: Filament version mismatch or incorrect namespace usage.

**Solution**: Verify Filament v4 is installed and namespaces are correct.

## Maintenance

### When to Update Tests

Update these tests when:
1. Toggle action behavior changes
2. Authorization rules change
3. UI elements (labels, icons, colors) change
4. Business rules for default language change
5. Namespace consolidation pattern changes

### Test Quality Checklist

- [ ] All tests have descriptive DocBlocks
- [ ] Test names clearly describe what is being tested
- [ ] Setup is minimal and focused
- [ ] Assertions are specific and meaningful
- [ ] Tests are independent and can run in any order
- [ ] Edge cases are covered
- [ ] Authorization is properly tested

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0.0 | 2025-11-28 | Initial test suite creation with 16 comprehensive tests |

---

**Last Updated**: 2025-11-28  
**Maintained By**: Development Team  
**Review Cycle**: Quarterly or when functionality changes
