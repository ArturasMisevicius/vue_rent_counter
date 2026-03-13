# Batch 4 Verification Guide - Content & Localization Resources

## Quick Reference

**Resources**: FaqResource, LanguageResource, TranslationResource  
**Status**: ✅ Migrated to Filament 4  
**Verification Script**: `verify-batch4-resources.php`

## Automated Verification

### Run Verification Script

```bash
php verify-batch4-resources.php
```

### Expected Output

```
Verifying Batch 4 Filament Resources (Content & Localization)...

Testing FaqResource...
  ✓ Class structure: OK
  ✓ Model: App\Models\Faq
  ✓ Icon: heroicon-o-question-mark-circle
  ✓ Pages: 3 registered
  ✓ Using Filament 4 Schema API
  ✓ Using proper Tables\Actions\ namespace
  ✓ Not using individual action imports (correct)
  ✓ FaqResource is properly configured

Testing LanguageResource...
  ✓ Class structure: OK
  ✓ Model: App\Models\Language
  ✓ Icon: heroicon-o-language
  ✓ Pages: 3 registered
  ✓ Using Filament 4 Schema API
  ✓ Using proper Tables\Actions\ namespace
  ✓ Not using individual action imports (correct)
  ✓ LanguageResource is properly configured

Testing TranslationResource...
  ✓ Class structure: OK
  ✓ Model: App\Models\Translation
  ✓ Icon: heroicon-o-rectangle-stack
  ✓ Pages: 3 registered
  ✓ Using Filament 4 Schema API
  ✓ Using proper Tables\Actions\ namespace
  ✓ Not using individual action imports (correct)
  ✓ TranslationResource is properly configured

========================================
Results: 3 passed, 0 failed
========================================

✓ All Batch 4 resources are properly configured for Filament 4!
```

## Manual Testing

### 1. FaqResource Testing

#### Access the Resource
```
URL: /admin/faqs
Required Role: Admin or Superadmin
```

#### Test Cases

**List Page**:
- [ ] Page loads without errors
- [ ] FAQ entries display correctly
- [ ] Question column is searchable
- [ ] Category badges display
- [ ] Publication status icons show correctly
- [ ] Display order badges visible
- [ ] Updated timestamp shows relative time

**Filters**:
- [ ] Publication status filter works (Published/Draft)
- [ ] Category filter works (searchable dropdown)
- [ ] Filters persist in session

**Actions**:
- [ ] Edit icon button works
- [ ] Delete icon button works
- [ ] Bulk delete requires confirmation

**Create FAQ**:
- [ ] Create button visible
- [ ] Form loads correctly
- [ ] Question field required
- [ ] Category field optional
- [ ] Rich text editor loads with toolbar
- [ ] Display order defaults to 0
- [ ] Published toggle defaults to true
- [ ] Save creates new FAQ

**Edit FAQ**:
- [ ] Edit form loads with existing data
- [ ] All fields editable
- [ ] Rich text content preserved
- [ ] Save updates FAQ
- [ ] Delete action available in header

**Empty State**:
- [ ] Shows when no FAQs exist
- [ ] "Add first FAQ" button works

### 2. LanguageResource Testing

#### Access the Resource
```
URL: /admin/languages
Required Role: Superadmin only
```

#### Test Cases

**List Page**:
- [ ] Page loads without errors
- [ ] Language codes display as badges
- [ ] Language names searchable
- [ ] Native names show (or dash if empty)
- [ ] Default language icon shows correctly
- [ ] Active status icon shows correctly
- [ ] Display order badges visible

**Filters**:
- [ ] Active status filter (Active/Inactive/All)
- [ ] Default language filter (Default/Non-default/All)
- [ ] Filters persist in session

**Actions**:
- [ ] Edit icon button works
- [ ] Delete icon button works
- [ ] Bulk delete requires confirmation

**Create Language**:
- [ ] Create button visible
- [ ] Form loads correctly
- [ ] Code field required (max 5 chars, lowercase, alphadash)
- [ ] Name field required
- [ ] Native name optional
- [ ] Active toggle defaults to true
- [ ] Default toggle available
- [ ] Display order defaults to 0
- [ ] Save creates new language

**Edit Language**:
- [ ] Edit form loads with existing data
- [ ] Code field unique validation works
- [ ] All fields editable
- [ ] Save updates language
- [ ] Delete action available in header

**Validation**:
- [ ] Duplicate code rejected
- [ ] Code automatically lowercased
- [ ] Only alphadash characters allowed in code

### 3. TranslationResource Testing

#### Access the Resource
```
URL: /admin/translations
Required Role: Superadmin only
```

#### Test Cases

**List Page**:
- [ ] Page loads without errors
- [ ] Group badges display
- [ ] Keys searchable and copyable
- [ ] Default locale value shows (truncated at 50 chars)
- [ ] Tooltip shows full value on hover
- [ ] Updated timestamp shows relative time

**Filters**:
- [ ] Group filter works (searchable dropdown)
- [ ] Shows all unique groups
- [ ] Filter persists in session

**Actions**:
- [ ] Edit icon button works
- [ ] Delete icon button works
- [ ] Bulk delete requires confirmation
- [ ] Copy key to clipboard works

**Create Translation**:
- [ ] Create button visible
- [ ] Form loads correctly
- [ ] Group field required (alphadash)
- [ ] Key field required
- [ ] Dynamic language fields generated
- [ ] One textarea per active language
- [ ] Language labels show name and code
- [ ] Default language has helper text
- [ ] Section is collapsible
- [ ] Save creates new translation

**Edit Translation**:
- [ ] Edit form loads with existing data
- [ ] Group and key editable
- [ ] All language values editable
- [ ] Values section collapsible
- [ ] Collapse state persists
- [ ] Save updates translation
- [ ] Delete action available in header

**Dynamic Language Fields**:
- [ ] Only active languages shown
- [ ] Languages in display order
- [ ] Adding language updates form (after refresh)
- [ ] Deactivating language hides field (after refresh)

## Authorization Verification

### FaqResource
```bash
# Test as Admin
php artisan tinker
>>> $admin = User::where('role', 'admin')->first();
>>> auth()->login($admin);
>>> App\Filament\Resources\FaqResource::canViewAny(); // Should be true
>>> App\Filament\Resources\FaqResource::shouldRegisterNavigation(); // Should be true

# Test as Manager
>>> $manager = User::where('role', 'manager')->first();
>>> auth()->login($manager);
>>> App\Filament\Resources\FaqResource::canViewAny(); // Should be false
>>> App\Filament\Resources\FaqResource::shouldRegisterNavigation(); // Should be false
```

### LanguageResource
```bash
# Test as Superadmin
php artisan tinker
>>> $superadmin = User::where('role', 'superadmin')->first();
>>> auth()->login($superadmin);
>>> App\Filament\Resources\LanguageResource::canViewAny(); // Should be true
>>> App\Filament\Resources\LanguageResource::shouldRegisterNavigation(); // Should be true

# Test as Admin
>>> $admin = User::where('role', 'admin')->first();
>>> auth()->login($admin);
>>> App\Filament\Resources\LanguageResource::canViewAny(); // Should be false
>>> App\Filament\Resources\LanguageResource::shouldRegisterNavigation(); // Should be false
```

### TranslationResource
```bash
# Test as Superadmin
php artisan tinker
>>> $superadmin = User::where('role', 'superadmin')->first();
>>> auth()->login($superadmin);
>>> App\Filament\Resources\TranslationResource::canViewAny(); // Should be true
>>> App\Filament\Resources\TranslationResource::shouldRegisterNavigation(); // Should be true

# Test as Admin
>>> $admin = User::where('role', 'admin')->first();
>>> auth()->login($admin);
>>> App\Filament\Resources\TranslationResource::canViewAny(); // Should be false
>>> App\Filament\Resources\TranslationResource::shouldRegisterNavigation(); // Should be false
```

## Code Quality Checks

### Syntax Validation
```bash
php -l app/Filament/Resources/FaqResource.php
php -l app/Filament/Resources/LanguageResource.php
php -l app/Filament/Resources/TranslationResource.php
```

### Static Analysis
```bash
./vendor/bin/phpstan analyse app/Filament/Resources/FaqResource.php
./vendor/bin/phpstan analyse app/Filament/Resources/LanguageResource.php
./vendor/bin/phpstan analyse app/Filament/Resources/TranslationResource.php
```

### Code Style
```bash
./vendor/bin/pint app/Filament/Resources/FaqResource.php --test
./vendor/bin/pint app/Filament/Resources/LanguageResource.php --test
./vendor/bin/pint app/Filament/Resources/TranslationResource.php --test
```

## Common Issues & Solutions

### Issue: Actions not working
**Symptom**: Edit/Delete buttons don't respond  
**Solution**: Verify `Tables\Actions\` namespace is used, not individual imports

### Issue: Columns not displaying
**Symptom**: Table columns show errors  
**Solution**: Verify `Tables\Columns\` namespace is used

### Issue: Filters not working
**Symptom**: Filters don't apply  
**Solution**: Verify `Tables\Filters\` namespace is used

### Issue: Translation fields not showing
**Symptom**: Language fields missing in TranslationResource  
**Solution**: Ensure languages are marked as active in LanguageResource

### Issue: Authorization errors
**Symptom**: 403 errors when accessing resources  
**Solution**: Verify user role matches resource requirements

## Performance Checks

### Query Optimization
```bash
# Enable query logging
php artisan tinker
>>> DB::enableQueryLog();
>>> // Navigate to resource list page
>>> DB::getQueryLog();
```

Expected:
- FAQ list: ~3-5 queries
- Language list: ~2-3 queries
- Translation list: ~3-4 queries

### Memory Usage
```bash
# Check memory usage during resource operations
php artisan tinker
>>> memory_get_usage(true);
>>> // Perform resource operations
>>> memory_get_usage(true);
```

Expected: < 50MB for typical operations

## Integration Testing

### Test with Seeders
```bash
# Seed test data
php artisan db:seed --class=FaqSeeder
php artisan db:seed --class=LanguageSeeder

# Verify data appears in resources
# Navigate to /admin/faqs
# Navigate to /admin/languages
# Navigate to /admin/translations
```

### Test CRUD Operations
```bash
# Create test records via Filament UI
# Edit test records
# Delete test records
# Verify database changes
php artisan tinker
>>> App\Models\Faq::count();
>>> App\Models\Language::count();
>>> App\Models\Translation::count();
```

## Rollback Verification

If rollback is needed:

```bash
# Revert changes
git checkout HEAD~1 -- app/Filament/Resources/FaqResource.php
git checkout HEAD~1 -- app/Filament/Resources/LanguageResource.php
git checkout HEAD~1 -- app/Filament/Resources/TranslationResource.php

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Verify resources still work
php artisan route:list | grep -E "(faq|language|translation)"
```

## Success Criteria

✅ All automated checks pass  
✅ All manual test cases pass  
✅ Authorization works correctly  
✅ No syntax or static analysis errors  
✅ Code style compliant  
✅ Performance within acceptable limits  
✅ CRUD operations work correctly  
✅ Localization displays properly  

## Related Documentation

- [Batch 4 Resources Migration](../upgrades/BATCH_4_RESOURCES_MIGRATION.md)
- [Laravel 12 + Filament 4 Upgrade Guide](../upgrades/LARAVEL_12_FILAMENT_4_UPGRADE.md)
- [Batch 3 Verification Guide](BATCH_3_VERIFICATION_GUIDE.md)
- [Testing Guide](../guides/TESTING_GUIDE.md)

## Support

For issues or questions:
1. Check [BATCH_4_RESOURCES_MIGRATION.md](../upgrades/BATCH_4_RESOURCES_MIGRATION.md)
2. Review [LARAVEL_12_FILAMENT_4_UPGRADE.md](../upgrades/LARAVEL_12_FILAMENT_4_UPGRADE.md)
3. Consult Filament 4 documentation
4. Check project issue tracker
