# FAQ Delete Test - Quick Reference

## Test Case: TC-7 - Delete FAQ

### Objective
Verify that deleting an FAQ works correctly after namespace consolidation.

### Prerequisites
- Application running (`php artisan serve`)
- Authenticated as ADMIN or SUPERADMIN
- At least one FAQ exists in the database

### Test Steps

1. **Navigate to FAQ List**
   - URL: `http://127.0.0.1:8000/admin/faqs`
   - Verify page loads successfully

2. **Locate Delete Button**
   - Find any FAQ row in the table
   - Locate the delete icon button (trash icon)
   - Verify button is visible and enabled

3. **Initiate Delete**
   - Click the delete icon button
   - Observe confirmation modal appears

4. **Confirm Deletion**
   - Read the warning message in the modal
   - Click "Confirm" or "Delete" button
   - Observe the modal closes

5. **Verify Results**
   - Check that success notification displays
   - Verify the FAQ is removed from the list
   - Confirm the table updates correctly

### Expected Results

✅ **Delete Button**
- Delete icon button visible on each FAQ row
- Button uses `Tables\Actions\DeleteAction` with namespace prefix
- Button is properly styled and accessible

✅ **Confirmation Modal**
- Modal displays when delete button clicked
- Warning message is clear and translated
- Modal has "Confirm" and "Cancel" buttons
- Modal styling is consistent with Filament 4

✅ **Deletion Process**
- Clicking "Confirm" removes the FAQ
- Success notification displays
- FAQ disappears from the list immediately
- No errors in browser console
- No errors in application logs

✅ **Cancellation**
- Clicking "Cancel" closes modal
- FAQ remains in the list
- No changes made to database

### Namespace Consolidation Verification

Verify that the delete action uses the consolidated namespace:

```php
// Should use this pattern:
Tables\Actions\DeleteAction::make()

// NOT this pattern:
use Filament\Tables\Actions\DeleteAction;
DeleteAction::make()
```

### Authorization Check

- SUPERADMIN: Can delete any FAQ
- ADMIN: Can delete any FAQ
- MANAGER: Cannot access delete button
- TENANT: Cannot access delete button

### Performance Check

- Delete operation completes in < 500ms
- No N+1 query issues
- Cache invalidation works correctly (FaqObserver)

### Troubleshooting

**Issue**: Delete button not visible
- Check user role and permissions
- Verify FaqPolicy allows deletion
- Check browser console for errors

**Issue**: Confirmation modal doesn't appear
- Check JavaScript console for errors
- Verify Livewire is loaded correctly
- Clear browser cache and retry

**Issue**: FAQ not removed after confirmation
- Check application logs for errors
- Verify database connection
- Check FaqObserver for issues

**Issue**: Success notification doesn't display
- Check Filament notification configuration
- Verify Livewire is working correctly
- Check browser console for errors

### Related Test Cases

- **TC-8**: Bulk Delete - Tests deleting multiple FAQs at once
- **TC-12**: Authorization - Tests permission enforcement
- **TC-15**: Cache Invalidation - Tests cache clearing after deletion

### Documentation References

- Full test guide: [docs/testing/FAQ_ADMIN_MANUAL_TEST.md](FAQ_ADMIN_MANUAL_TEST.md)
- FaqResource implementation: `app/Filament/Resources/FaqResource.php`
- FaqPolicy: `app/Policies/FaqPolicy.php`
- FaqObserver: `app/Observers/FaqObserver.php`

### Test Result Template

```
Test Date: _______________
Tester: _______________
Environment: _______________

Delete FAQ Test: ⬜ Pass ⬜ Fail

Notes:
_________________________________
_________________________________
_________________________________

Issues Found:
_________________________________
_________________________________
_________________________________
```

---

**Status**: ✅ DOCUMENTED - Ready for manual execution
**Last Updated**: 2025-11-28
**Related Spec**: `.kiro/specs/6-filament-namespace-consolidation/`
