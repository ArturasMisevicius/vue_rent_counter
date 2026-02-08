# FAQ Admin Panel - Quick Test Reference

## 🚀 Quick Start

### Setup (2 minutes)
```bash
# 1. Start server
php artisan serve

# 2. Seed test data (if needed)
php artisan db:seed --class=FaqSeeder

# 3. Navigate to
http://127.0.0.1:8000/admin/faqs
```

### Login Credentials
- **SUPERADMIN**: Use your superadmin credentials
- **ADMIN**: Use your admin credentials

---

## ✅ Quick Smoke Test (10 minutes)

### 1. Navigation ✓
- [ ] FAQ menu visible in "System Management"
- [ ] Click navigates to `/admin/faqs`
- [ ] Page loads without errors

### 2. List View ✓
- [ ] Table displays with columns: Question, Category, Published, Order, Updated
- [ ] Default sort by Display Order
- [ ] Search box visible

### 3. Create ✓
- [ ] Click "Create" button
- [ ] Fill form: Question, Category, Answer, Order, Published
- [ ] Click "Create"
- [ ] New FAQ appears in list

### 4. Edit ✓
- [ ] Click edit icon on any FAQ
- [ ] Modify question
- [ ] Click "Save"
- [ ] Changes reflected in list

### 5. Delete ✓
- [ ] Click delete icon on any FAQ
- [ ] Confirm deletion
- [ ] FAQ removed from list

### 6. Filters ✓
- [ ] Status filter works (Published/Draft)
- [ ] Category filter works
- [ ] Filters update table correctly

### 7. Search ✓
- [ ] Enter search term
- [ ] Results filter correctly
- [ ] Clear search restores list

### 8. Bulk Delete ✓
- [ ] Select multiple FAQs
- [ ] Click bulk actions → Delete
- [ ] Confirm deletion
- [ ] Selected FAQs removed

---

## 🔍 Namespace Consolidation Check (2 minutes)

### Code Verification
Open `app/Filament/Resources/FaqResource.php` and verify:

- [ ] Import section has: `use Filament\Tables;`
- [ ] No individual imports like: `use Filament\Tables\Actions\EditAction;`
- [ ] All actions use: `Tables\Actions\EditAction::make()`
- [ ] All columns use: `Tables\Columns\TextColumn::make()`
- [ ] All filters use: `Tables\Filters\SelectFilter::make()`

---

## 🎯 Critical Test Points

### Must Pass ✓
1. **Navigation**: Menu visible to ADMIN/SUPERADMIN only
2. **CRUD**: Create, Read, Update, Delete all work
3. **Filters**: Status and Category filters functional
4. **Search**: Question search works
5. **Authorization**: MANAGER/TENANT cannot access

### Performance ✓
- [ ] Page loads < 2 seconds
- [ ] Filters respond < 500ms
- [ ] Search responds < 500ms

### Security ✓
- [ ] Only ADMIN/SUPERADMIN can access
- [ ] Bulk delete limited to 50 items
- [ ] Authorization enforced on all actions

---

## 📝 Report Issues

### Issue Template
```
**Issue**: [Brief description]
**Steps to Reproduce**:
1. 
2. 
3. 
**Expected**: [What should happen]
**Actual**: [What actually happened]
**Severity**: [Critical/High/Medium/Low]
```

### Common Issues to Watch For
- [ ] Broken links or navigation
- [ ] Missing translations
- [ ] Validation not working
- [ ] Filters not updating table
- [ ] Search not filtering correctly
- [ ] Authorization not enforced
- [ ] Performance issues (slow loading)
- [ ] Visual/styling issues

---

## 📊 Quick Results

| Test Area | Status | Notes |
|-----------|--------|-------|
| Navigation | ⬜ Pass ⬜ Fail | |
| List View | ⬜ Pass ⬜ Fail | |
| Create | ⬜ Pass ⬜ Fail | |
| Edit | ⬜ Pass ⬜ Fail | |
| Delete | ⬜ Pass ⬜ Fail | |
| Filters | ⬜ Pass ⬜ Fail | |
| Search | ⬜ Pass ⬜ Fail | |
| Bulk Delete | ⬜ Pass ⬜ Fail | |
| Authorization | ⬜ Pass ⬜ Fail | |
| Performance | ⬜ Pass ⬜ Fail | |

**Overall**: ⬜ Pass ⬜ Fail

---

## 📚 Full Documentation

For comprehensive testing, see:
- **Full Manual Test Guide**: [docs/testing/FAQ_ADMIN_MANUAL_TEST.md](FAQ_ADMIN_MANUAL_TEST.md)
- **Test Summary**: [docs/testing/FAQ_ADMIN_TEST_SUMMARY.md](FAQ_ADMIN_TEST_SUMMARY.md)

---

**Quick Test Version**: 1.0.0  
**Estimated Time**: 10-15 minutes  
**Last Updated**: 2025-11-28
