# Tariff Manual Mode - Requirements Specification

## Executive Summary

### Business Value
Enable administrators to create and manage utility tariffs without requiring provider integration, supporting historical data entry, custom configurations, and operational flexibility when provider APIs are unavailable.

### Success Metrics
- 100% of manual tariffs created without provider_id persist correctly
- Zero data integrity issues when switching between manual and provider modes
- <300ms form render time with conditional field visibility
- 100% backward compatibility with existing provider-linked tariffs
- All validation rules enforce data integrity for both modes

### Constraints
- SUPERADMIN and ADMIN roles only (enforced via TariffPolicy)
- Maintains existing multi-tenant architecture with tenant_id scoping
- No breaking changes to existing tariff functionality
- Must support Lithuanian localization (EN/LT/RU)
- Backward compatible with existing tariffs

## User Stories

### US-1: Create Manual Tariff
**As an** administrator  
**I want to** create a tariff without linking to a provider  
**So that** I can enter historical rates from paper records or create custom configurations

**Acceptance Criteria:**
- [ ] Manual mode toggle is visible at top of tariff creation form
- [ ] When manual mode is enabled, provider and remote_id fields are hidden
- [ ] Manual tariff can be created with only name, configuration, and active dates
- [ ] Tariff saves with provider_id = null
- [ ] Form validation does not require provider when manual mode is enabled
- [ ] Success message confirms tariff creation
- [ ] Created tariff appears in tariff list with appropriate indicator

**A11y Requirements:**
- Toggle has proper ARIA label from translation key
- Helper text explains manual mode purpose
- Keyboard navigation works for all visible fields
- Screen reader announces field visibility changes

**Localization:**
- All labels use translation keys (tariffs.forms.*)
- Helper text available in EN/LT/RU
- Validation messages localized

**Performance:**
- Form renders in <300ms
- Field visibility updates in <100ms (Livewire reactive)
- No N+1 queries on form load

### US-2: Create Provider-Linked Tariff
**As an** administrator  
**I want to** create a tariff linked to a provider with optional external system ID  
**So that** I can integrate with provider APIs and track external references

**Acceptance Criteria:**
- [ ] When manual mode is disabled (default), provider field is visible and required
- [ ] Remote ID field is visible and optional when provider mode is active
- [ ] Provider dropdown is searchable with cached options
- [ ] Remote ID accepts up to 255 characters
- [ ] Tariff saves with provider_id and optional remote_id
- [ ] Validation enforces provider selection in provider mode
- [ ] External system ID is indexed for fast lookups

**A11y Requirements:**
- Provider select has proper label and is keyboard accessible
- Remote ID field has descriptive helper text
- Required field indicators are clear

**Localization:**
- Provider and remote_id labels use translation keys
- Validation messages for both fields localized

**Performance:**
- Provider options cached (Provider::getCachedOptions())
- Remote_id field indexed in database
- Form submission <500ms

### US-3: Switch Between Modes
**As an** administrator  
**I want to** toggle between manual and provider modes while creating a tariff  
**So that** I can change my approach without losing entered data

**Acceptance Criteria:**
- [ ] Manual mode toggle updates field visibility in real-time
- [ ] Switching modes preserves entered data in visible fields
- [ ] Validation rules update based on current mode
- [ ] No form submission required to see mode changes
- [ ] Clear visual feedback when fields appear/disappear

**A11y Requirements:**
- Mode changes announced to screen readers
- Focus management when fields hide/show
- Toggle state clearly indicated

**Performance:**
- Mode switch updates UI in <100ms
- No server round-trip for visibility changes

### US-4: Edit Manual Tariff to Add Provider
**As an** administrator  
**I want to** edit an existing manual tariff to link it to a provider  
**So that** I can transition historical data to provider integration

**Acceptance Criteria:**
- [ ] Manual tariffs can be edited
- [ ] Provider can be selected on edit form
- [ ] Remote ID can be added when provider is selected
- [ ] Tariff updates from manual to provider-linked mode
- [ ] Audit log captures the mode change
- [ ] No data loss during transition

**A11y Requirements:**
- Edit form maintains same accessibility as create form
- Changes are clearly indicated

**Performance:**
- Edit form loads in <300ms
- Update operation completes in <500ms

### US-5: Validate Data Integrity
**As a** system  
**I want to** enforce validation rules based on tariff mode  
**So that** data integrity is maintained

**Acceptance Criteria:**
- [ ] Provider is required when manual mode is disabled
- [ ] Provider is optional when manual mode is enabled
- [ ] Remote ID max length is 255 characters
- [ ] Remote ID is optional even with provider selected
- [ ] Name field always required (max 255 chars)
- [ ] Configuration field always required
- [ ] Active dates validated (active_until > active_from)
- [ ] Validation messages are clear and localized

**A11y Requirements:**
- Validation errors announced to screen readers
- Error messages associated with fields via ARIA

**Performance:**
- Client-side validation provides immediate feedback
- Server-side validation completes in <200ms

## Data Model

### Migration: add_remote_id_to_tariffs_table

**File:** `database/migrations/2025_12_05_163137_add_remote_id_to_tariffs_table.php`

**Schema Changes:**
```php
// Up migration
Schema::table('tariffs', function (Blueprint $table) {
    $table->string('remote_id', 255)->nullable()->after('provider_id');
    $table->foreignId('provider_id')->nullable()->change();
    $table->index('remote_id');
});

// Down migration
Schema::table('tariffs', function (Blueprint $table) {
    $table->dropIndex(['remote_id']);
    $table->dropColumn('remote_id');
    $table->foreignId('provider_id')->nullable(false)->change();
});
```

**Fields:**
- `remote_id`: VARCHAR(255), nullable, indexed - External system identifier
- `provider_id`: Modified to nullable - Allows manual tariffs

**Indexes:**
- `remote_id` - Standard index for external system lookups
- Existing `provider_id` foreign key index maintained

**Relationships:**
- Tariff belongsTo Provider (nullable)
- Provider hasMany Tariffs (unchanged)

**Seeds/Backfill:**
- No data migration required
- Existing tariffs retain provider_id values
- New manual tariffs created with provider_id = null

**Rollback Considerations:**
- Cannot rollback if manual tariffs exist (provider_id = null)
- Must assign providers to manual tariffs before rollback
- Or accept data loss of manual tariffs

### Model Changes

**File:** `app/Models/Tariff.php`

**New Method:**
```php
public function isManual(): bool
{
    return is_null($this->provider_id);
}
```

**Fillable Fields:**
- Add `remote_id` to $fillable array

**Computed Attributes:**
- `is_manual` - Boolean accessor using isManual() method

## API/Controllers

### Filament Resource Changes

**File:** `app/Filament/Resources/TariffResource/Concerns/BuildsTariffFormFields.php`

**Form Fields Added:**
1. **manual_mode** (Toggle)
   - UI-only field (dehydrated: false)
   - Default: false (provider mode)
   - Live reactive updates
   - Column span: full width

2. **remote_id** (TextInput)
   - Visible when manual_mode = false
   - Max length: 255
   - Optional field
   - Helper text explains purpose

**Form Fields Modified:**
1. **provider_id** (Select)
   - Visibility: Conditional on !manual_mode
   - Required: Conditional on !manual_mode
   - Validation: Dynamic based on manual_mode
   - Searchable with cached options

**Validation Rules:**
```php
// Provider ID
->rules([
    fn (Get $get): string => !$get('manual_mode') ? 'required' : 'nullable',
    fn (Get $get): string => !$get('manual_mode') ? 'exists:providers,id' : 'nullable',
])

// Remote ID
->rules(['nullable', 'string', 'max:255'])

// Name (unchanged)
->rules(['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9\s\-\_\.\,\(\)]+$/u'])
```

### Authorization Matrix

| Role | View Tariffs | Create Manual | Create Provider | Edit | Delete |
|------|--------------|---------------|-----------------|------|--------|
| SUPERADMIN | ✓ | ✓ | ✓ | ✓ | ✓ |
| ADMIN | ✓ | ✓ | ✓ | ✓ | ✓ |
| MANAGER | ✓ | ✗ | ✗ | ✗ | ✗ |
| TENANT | ✗ | ✗ | ✗ | ✗ | ✗ |

**Policy:** `app/Policies/TariffPolicy.php` (no changes required)

### API Endpoints

**Existing endpoints support new fields:**

**POST /api/tariffs**
```json
// Manual tariff
{
  "provider_id": null,
  "name": "Manual Historical Rate",
  "configuration": {
    "type": "flat",
    "rate": 0.12,
    "currency": "EUR"
  },
  "active_from": "2024-01-01"
}

// Provider tariff
{
  "provider_id": 5,
  "remote_id": "EXT-12345",
  "name": "Provider Standard Rate",
  "configuration": {
    "type": "flat",
    "rate": 0.15,
    "currency": "EUR"
  },
  "active_from": "2025-01-01"
}
```

**Response includes:**
- `is_manual`: Boolean computed attribute
- `remote_id`: String or null

## UX Requirements

### Form States

**Loading State:**
- Skeleton loaders for form fields
- Disabled submit button
- Loading indicator

**Empty State:**
- Default form with manual_mode = false
- Provider dropdown shows "Select provider..."
- All fields empty and ready for input

**Error State:**
- Validation errors displayed inline
- Error summary at top of form
- Fields with errors highlighted
- Focus moves to first error

**Success State:**
- Success notification displayed
- Redirect to tariff list or edit page
- Toast message confirms creation

### Keyboard Navigation

- Tab order: manual_mode → provider_id (if visible) → remote_id (if visible) → name → configuration fields
- Enter key submits form
- Escape key cancels/closes modal
- Arrow keys navigate select dropdowns

### Focus Management

- Focus on first visible field on form load
- Focus preserved when toggling manual mode
- Focus moves to error field on validation failure
- Focus returns to trigger after modal close

### Optimistic UI

- Manual mode toggle updates immediately (no server round-trip)
- Field visibility changes instantly
- Validation feedback appears on blur

### URL State Persistence

- Not applicable (Filament handles routing)
- Form state preserved in Livewire component

## Non-Functional Requirements

### Performance Budgets

- Form initial render: <300ms
- Manual mode toggle response: <100ms
- Form submission: <500ms
- Provider dropdown load: <200ms (cached)
- Database query for tariff list: <100ms

### Accessibility (WCAG 2.1 AA)

- All form fields have proper labels
- Helper text associated with fields via aria-describedby
- Required fields indicated with aria-required
- Validation errors announced to screen readers
- Keyboard navigation fully functional
- Focus indicators visible
- Color contrast ratios meet AA standards
- Form can be completed using keyboard only

### Security

**Headers/CSP:**
- Existing security headers maintained
- No inline scripts (Livewire handles reactivity)
- XSS protection via input sanitization

**Input Validation:**
- Server-side validation enforced
- SQL injection prevented via Eloquent
- XSS prevented via regex validation on name field
- Max length enforced on all text fields

**Authorization:**
- TariffPolicy enforces SUPERADMIN/ADMIN access
- Multi-tenant scoping via BelongsToTenant trait
- Audit logging via TariffObserver

### Privacy

- No PII stored in tariff records
- Audit logs capture user actions
- Data retention follows existing policies

### Observability

**Logging:**
- Tariff creation logged via TariffObserver
- Mode changes captured in audit trail
- Validation failures logged

**Metrics:**
- Track manual vs provider tariff creation ratio
- Monitor form submission success rate
- Track validation error frequency

**Alerting:**
- Alert on high validation error rate (>10%)
- Alert on form submission failures
- Monitor database query performance

## Testing Plan

### Unit Tests

**File:** `tests/Unit/Models/TariffTest.php`

```php
it('identifies manual tariffs correctly', function () {
    $manualTariff = Tariff::factory()->create(['provider_id' => null]);
    expect($manualTariff->isManual())->toBeTrue();
    
    $providerTariff = Tariff::factory()->create(['provider_id' => 1]);
    expect($providerTariff->isManual())->toBeFalse();
});
```

### Feature Tests

**File:** `tests/Feature/Filament/TariffManualModeTest.php`

**Test Cases:**
1. ✓ Can create manual tariff without provider
2. ✓ Can create tariff with provider and remote_id
3. ✓ Validates provider required when remote_id provided
4. ✓ Validates remote_id max length (255 chars)
5. ✓ Can edit manual tariff to add provider later

**Additional Test Cases Needed:**
```php
it('validates manual mode toggle behavior', function () {
    // Test that manual_mode field is not saved to database
});

it('enforces authorization for tariff creation', function () {
    // Test that only SUPERADMIN/ADMIN can create tariffs
});

it('maintains multi-tenant isolation', function () {
    // Test that tariffs are scoped to tenant_id
});
```

### Integration Tests

**Filament Form Interaction:**
```php
it('updates field visibility when toggling manual mode', function () {
    Livewire::test(TariffResource\Pages\CreateTariff::class)
        ->set('data.manual_mode', true)
        ->assertDontSee('Provider')
        ->assertDontSee('External System ID')
        ->set('data.manual_mode', false)
        ->assertSee('Provider')
        ->assertSee('External System ID');
});
```

### Property Tests

**Invariants to Test:**
- Manual tariffs always have provider_id = null
- Provider tariffs always have provider_id set
- Remote_id never exceeds 255 characters
- Tariff name always required regardless of mode
- Active dates always valid (active_until > active_from)

### Playwright E2E Tests

**Critical User Flows:**
1. Create manual tariff end-to-end
2. Create provider tariff with remote_id
3. Edit manual tariff to add provider
4. Validate error handling for invalid inputs
5. Test keyboard navigation through form

## Migration/Deployment

### Pre-Deployment Checklist

- [ ] Run migration in staging environment
- [ ] Verify existing tariffs unaffected
- [ ] Test manual tariff creation
- [ ] Test provider tariff creation
- [ ] Verify validation rules
- [ ] Check translation keys exist
- [ ] Review audit logging
- [ ] Performance test form rendering

### Deployment Steps

1. **Database Migration:**
   ```bash
   php artisan migrate --force
   ```

2. **Cache Clear:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan view:clear
   ```

3. **Verify Deployment:**
   ```bash
   php artisan test --filter=TariffManualModeTest
   ```

### Rollback Plan

**If issues arise:**
1. Identify manual tariffs: `SELECT * FROM tariffs WHERE provider_id IS NULL`
2. Options:
   - Keep feature and fix issues
   - Assign providers to manual tariffs
   - Accept data loss and rollback migration
3. Rollback command: `php artisan migrate:rollback --step=1`

### Post-Deployment Monitoring

- Monitor tariff creation success rate
- Track validation error frequency
- Check form performance metrics
- Review audit logs for anomalies
- Monitor database query performance

## Documentation Updates

### Files to Update

1. **README.md**
   - Add manual mode feature to feature list
   - Update tariff management section

2. **docs/filament/TARIFF_RESOURCE.md**
   - Document manual mode toggle
   - Explain field visibility behavior
   - Add usage examples

3. **docs/api/TARIFF_API.md**
   - Update request/response schemas
   - Add manual tariff examples
   - Document new remote_id field

4. **.kiro/specs/vilnius-utilities-billing/**
   - Update tariff management spec
   - Add manual mode requirements
   - Document acceptance criteria

### Translation Keys Required

**File:** `lang/en/tariffs.php`

```php
'forms' => [
    'manual_mode' => 'Manual Entry Mode',
    'manual_mode_helper' => 'Enable to create tariff without provider integration',
    'remote_id' => 'External System ID',
    'remote_id_helper' => 'Optional identifier from external billing system',
],

'validation' => [
    'provider_id' => [
        'required' => 'Provider is required',
        'exists' => 'Selected provider does not exist',
        'required_with' => 'Provider is required when external ID is provided',
    ],
    'remote_id' => [
        'max' => 'External ID may not be greater than 255 characters',
    ],
],
```

## Monitoring/Alerting

### Metrics to Track

1. **Usage Metrics:**
   - Manual tariff creation count
   - Provider tariff creation count
   - Manual-to-provider conversion rate
   - Remote_id usage rate

2. **Performance Metrics:**
   - Form render time (p50, p95, p99)
   - Form submission time
   - Validation response time
   - Database query time

3. **Error Metrics:**
   - Validation error rate by field
   - Form submission failure rate
   - Database constraint violations

### Alerts to Configure

1. **Critical Alerts:**
   - Form submission failure rate >5%
   - Database migration failures
   - Authorization bypass attempts

2. **Warning Alerts:**
   - Validation error rate >10%
   - Form render time >500ms
   - High manual tariff creation rate (potential misuse)

3. **Info Alerts:**
   - New manual tariff created
   - Manual tariff converted to provider tariff
   - Remote_id field usage patterns

## Success Criteria

### Functional Success

- [ ] Manual tariffs can be created without provider
- [ ] Provider tariffs can be created with optional remote_id
- [ ] Field visibility updates based on manual mode toggle
- [ ] Validation rules enforce data integrity
- [ ] Manual tariffs can be edited to add provider
- [ ] All tests passing (unit, feature, integration)

### Non-Functional Success

- [ ] Form renders in <300ms
- [ ] Manual mode toggle responds in <100ms
- [ ] All accessibility requirements met (WCAG 2.1 AA)
- [ ] All translation keys implemented (EN/LT/RU)
- [ ] Zero security vulnerabilities introduced
- [ ] Backward compatibility maintained

### Business Success

- [ ] Administrators can enter historical tariff data
- [ ] Custom tariff configurations supported
- [ ] Provider integration remains optional
- [ ] Audit trail captures all changes
- [ ] Documentation complete and accurate

## Risk Register

| Risk | Impact | Likelihood | Mitigation |
|------|--------|------------|------------|
| Data integrity issues with nullable provider_id | High | Low | Comprehensive validation, database constraints, extensive testing |
| Performance degradation with conditional fields | Medium | Low | Cached provider options, indexed remote_id, Livewire optimization |
| User confusion about manual vs provider mode | Medium | Medium | Clear helper text, comprehensive documentation, training materials |
| Rollback complexity if manual tariffs exist | High | Low | Clear rollback documentation, data migration scripts, backup procedures |
| Translation key gaps | Low | Medium | Complete translation file, review process, fallback to English |

## Appendix

### Related Files

- Migration: `database/migrations/2025_12_05_163137_add_remote_id_to_tariffs_table.php`
- Form Builder: `app/Filament/Resources/TariffResource/Concerns/BuildsTariffFormFields.php`
- Model: `app/Models/Tariff.php`
- Tests: `tests/Feature/Filament/TariffManualModeTest.php`
- Documentation: `docs/filament/TARIFF_MANUAL_MODE.md`

### References

- Filament 4 Documentation: https://filamentphp.com/docs
- Laravel 12 Documentation: https://laravel.com/docs/12.x
- WCAG 2.1 Guidelines: https://www.w3.org/WAI/WCAG21/quickref/
