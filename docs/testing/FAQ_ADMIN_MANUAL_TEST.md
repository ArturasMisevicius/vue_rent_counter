# FAQ Admin Panel Manual Testing Guide

## Overview
This document provides a comprehensive manual testing checklist for the FAQ admin panel at `/admin/faqs`.

## Prerequisites
- Application server running (e.g., `php artisan serve`)
- Authenticated as a user with ADMIN or SUPERADMIN role
- Database seeded with test data

## Test Environment Setup

### 1. Start the Application
```bash
php artisan serve
```

### 2. Access the Admin Panel
Navigate to: `http://127.0.0.1:8000/admin/faqs`

## Manual Test Cases

### TC-1: Navigation and Access
**Objective**: Verify that the FAQ resource is accessible and properly displayed in the admin panel.

**Steps**:
1. Log in as a SUPERADMIN user
2. Navigate to the admin panel
3. Locate "FAQs" in the System Management navigation group
4. Click on the FAQs menu item

**Expected Results**:
- [ ] FAQ menu item is visible in the navigation
- [ ] FAQ menu item is under "System Management" group
- [ ] Clicking the menu item navigates to `/admin/faqs`
- [ ] Page loads without errors
- [ ] Page displays the FAQ list table

**Verification Points**:
- Navigation icon: `heroicon-o-question-mark-circle`
- Navigation label: Translated "FAQs" text
- Navigation sort order: 10

---

### TC-2: List Page Display
**Objective**: Verify that the FAQ list page displays correctly with all columns and features.

**Steps**:
1. Navigate to `/admin/faqs`
2. Observe the table structure and columns

**Expected Results**:
- [ ] Table displays with the following columns:
  - Question (searchable, sortable)
  - Category (badge, sortable, toggleable)
  - Published (icon column, sortable)
  - Display Order (badge, sortable, centered)
  - Last Updated (date/time, sortable, hidden by default)
- [ ] Default sort is by Display Order (ascending)
- [ ] Empty state message displays if no FAQs exist
- [ ] Pagination controls display if more than one page

**Verification Points**:
- All columns render correctly
- Column labels are properly translated
- Tooltips display on Published icon column
- Category badges display with gray color
- Display Order badges display with primary color

---

### TC-3: Search Functionality
**Objective**: Verify that the search functionality works correctly.

**Steps**:
1. Navigate to `/admin/faqs`
2. Enter a search term in the search box
3. Observe the filtered results

**Expected Results**:
- [ ] Search box is visible and functional
- [ ] Entering text filters the FAQ list
- [ ] Search works on the Question field
- [ ] Search persists in session
- [ ] Clearing search restores full list

---

### TC-4: Filter Functionality
**Objective**: Verify that filters work correctly.

**Steps**:
1. Navigate to `/admin/faqs`
2. Click on the "Status" filter
3. Select "Published" or "Draft"
4. Observe the filtered results
5. Click on the "Category" filter
6. Select a category
7. Observe the filtered results

**Expected Results**:
- [ ] Status filter displays with options:
  - Published
  - Draft
- [ ] Category filter displays with available categories
- [ ] Category filter is searchable
- [ ] Selecting a filter updates the table
- [ ] Filters persist in session
- [ ] Multiple filters can be applied simultaneously
- [ ] Clearing filters restores full list

**Verification Points**:
- Filters use non-native select (custom styling)
- Category options are cached for performance
- Filter labels are properly translated

---

### TC-5: Create FAQ
**Objective**: Verify that creating a new FAQ works correctly.

**Steps**:
1. Navigate to `/admin/faqs`
2. Click the "Create" button (or "Add First FAQ" if empty)
3. Fill in the form:
   - Question: "How do I reset my password?"
   - Category: "Account"
   - Answer: "To reset your password, click on..."
   - Display Order: 1
   - Published: Toggle ON
4. Click "Create"

**Expected Results**:
- [ ] Create button is visible and clickable
- [ ] Create form displays with all fields
- [ ] Form validation works:
  - Question: Required, min 10 chars, max 255 chars, valid format
  - Category: Optional, max 120 chars, valid format
  - Answer: Required, min 10 chars, max 10000 chars
  - Display Order: Numeric, min 0, max 9999
  - Published: Boolean toggle
- [ ] Rich text editor displays with toolbar buttons:
  - Bold, Italic, Underline
  - Bullet List, Ordered List
  - Link
- [ ] Helper text displays for each field
- [ ] Successful creation redirects to list page
- [ ] Success notification displays
- [ ] New FAQ appears in the list

**Verification Points**:
- Form section title: "FAQ Entry"
- Form section description displays
- All labels are properly translated
- Validation messages are clear and translated
- Rich text editor hint about HTML sanitization displays

---

### TC-6: Edit FAQ
**Objective**: Verify that editing an existing FAQ works correctly.

**Steps**:
1. Navigate to `/admin/faqs`
2. Click the edit icon button on an FAQ row
3. Modify the Question field
4. Modify the Answer field
5. Change the Display Order
6. Toggle the Published status
7. Click "Save"

**Expected Results**:
- [ ] Edit icon button is visible on each row
- [ ] Clicking edit navigates to edit page
- [ ] Edit form displays with current values
- [ ] All fields are editable
- [ ] Form validation works (same as create)
- [ ] Successful update redirects to list page
- [ ] Success notification displays
- [ ] Updated FAQ reflects changes in the list

---

### TC-7: Delete FAQ
**Objective**: Verify that deleting an FAQ works correctly.

**Steps**:
1. Navigate to `/admin/faqs`
2. Click the delete icon button on an FAQ row
3. Confirm the deletion in the modal

**Expected Results**:
- [ ] Delete icon button is visible on each row
- [ ] Clicking delete shows confirmation modal
- [ ] Modal displays appropriate warning message
- [ ] Confirming deletion removes the FAQ
- [ ] Success notification displays
- [ ] FAQ is removed from the list
- [ ] Canceling deletion keeps the FAQ

---

### TC-8: Bulk Delete
**Objective**: Verify that bulk delete functionality works correctly.

**Steps**:
1. Navigate to `/admin/faqs`
2. Select multiple FAQs using checkboxes
3. Click the bulk actions dropdown
4. Select "Delete selected"
5. Confirm the deletion in the modal

**Expected Results**:
- [ ] Checkboxes appear on each row
- [ ] "Select all" checkbox works
- [ ] Bulk actions dropdown appears when items selected
- [ ] "Delete selected" option is available
- [ ] Confirmation modal displays with:
  - Heading: Translated delete heading
  - Description: Translated delete description
- [ ] Confirming deletion removes all selected FAQs
- [ ] Success notification displays
- [ ] Selected FAQs are removed from the list
- [ ] Bulk operation respects limit (max 50 items)

**Verification Points**:
- Authorization check: Only users with 'deleteAny' permission can bulk delete
- Rate limiting: Maximum 50 items per bulk operation
- Error message displays if limit exceeded

---

### TC-9: Sorting
**Objective**: Verify that sorting functionality works correctly.

**Steps**:
1. Navigate to `/admin/faqs`
2. Click on the "Question" column header
3. Observe the sort order
4. Click again to reverse sort
5. Repeat for other sortable columns

**Expected Results**:
- [ ] Clicking column header sorts the table
- [ ] Sort indicator (arrow) displays on active column
- [ ] Clicking again reverses the sort order
- [ ] Default sort is by Display Order (ascending)
- [ ] Sort persists in session
- [ ] Sortable columns:
  - Question
  - Category
  - Published
  - Display Order
  - Last Updated

---

### TC-10: Column Toggles
**Objective**: Verify that column visibility toggles work correctly.

**Steps**:
1. Navigate to `/admin/faqs`
2. Click the column toggle button (if available)
3. Toggle visibility of columns

**Expected Results**:
- [ ] Column toggle button is visible
- [ ] Clicking shows list of toggleable columns
- [ ] Toggling a column hides/shows it
- [ ] "Last Updated" column is hidden by default
- [ ] Column visibility persists in session

---

### TC-11: Performance Verification
**Objective**: Verify that performance optimizations are working.

**Steps**:
1. Navigate to `/admin/faqs`
2. Observe page load time
3. Apply filters and observe response time
4. Search and observe response time

**Expected Results**:
- [ ] Page loads in reasonable time (< 2 seconds)
- [ ] Filters respond quickly (< 500ms)
- [ ] Search responds quickly (< 500ms)
- [ ] No N+1 query issues
- [ ] Category options are cached

**Verification Points**:
- Query uses explicit column selection (no SELECT *)
- Translation cache reduces overhead
- Category options cached for 15 minutes
- Memoized authorization checks

---

### TC-12: Authorization
**Objective**: Verify that authorization is properly enforced.

**Steps**:
1. Log in as different user roles:
   - SUPERADMIN
   - ADMIN
   - MANAGER
   - TENANT
2. Attempt to access `/admin/faqs`
3. Attempt to create, edit, delete FAQs

**Expected Results**:
- [ ] SUPERADMIN: Full access to all operations
- [ ] ADMIN: Full access to all operations
- [ ] MANAGER: No access (navigation hidden, direct access blocked)
- [ ] TENANT: No access (navigation hidden, direct access blocked)
- [ ] Unauthorized access attempts show 403 error

**Verification Points**:
- Navigation only visible to ADMIN and SUPERADMIN
- FaqPolicy enforces authorization
- Bulk delete checks 'deleteAny' permission

---

### TC-13: Localization
**Objective**: Verify that all text is properly translated.

**Steps**:
1. Navigate to `/admin/faqs`
2. Switch language (if multi-language support enabled)
3. Observe all labels, messages, and text

**Expected Results**:
- [ ] All labels are translated
- [ ] All helper text is translated
- [ ] All validation messages are translated
- [ ] All modal messages are translated
- [ ] All empty state messages are translated
- [ ] All filter options are translated

---

### TC-14: Responsive Design
**Objective**: Verify that the page works on different screen sizes.

**Steps**:
1. Navigate to `/admin/faqs`
2. Resize browser window to different sizes:
   - Desktop (1920x1080)
   - Tablet (768x1024)
   - Mobile (375x667)
3. Observe layout and functionality

**Expected Results**:
- [ ] Layout adapts to screen size
- [ ] All functionality remains accessible
- [ ] Tables are scrollable on small screens
- [ ] Forms are usable on mobile devices
- [ ] Navigation works on all screen sizes

---

### TC-15: Cache Invalidation
**Objective**: Verify that cache is properly invalidated on changes.

**Steps**:
1. Navigate to `/admin/faqs`
2. Note the category filter options
3. Create a new FAQ with a new category
4. Return to the list page
5. Check the category filter options

**Expected Results**:
- [ ] New category appears in filter options
- [ ] Cache is automatically invalidated by FaqObserver
- [ ] No manual cache clearing required

---

## Test Results Summary

### Test Execution Date: _______________
### Tester: _______________
### Environment: _______________

| Test Case | Status | Notes |
|-----------|--------|-------|
| TC-1: Navigation and Access | ⬜ Pass ⬜ Fail | |
| TC-2: List Page Display | ⬜ Pass ⬜ Fail | |
| TC-3: Search Functionality | ⬜ Pass ⬜ Fail | |
| TC-4: Filter Functionality | ⬜ Pass ⬜ Fail | |
| TC-5: Create FAQ | ⬜ Pass ⬜ Fail | |
| TC-6: Edit FAQ | ⬜ Pass ⬜ Fail | |
| TC-7: Delete FAQ | ⬜ Pass ⬜ Fail | |
| TC-8: Bulk Delete | ⬜ Pass ⬜ Fail | |
| TC-9: Sorting | ⬜ Pass ⬜ Fail | |
| TC-10: Column Toggles | ⬜ Pass ⬜ Fail | |
| TC-11: Performance Verification | ⬜ Pass ⬜ Fail | |
| TC-12: Authorization | ⬜ Pass ⬜ Fail | |
| TC-13: Localization | ⬜ Pass ⬜ Fail | |
| TC-14: Responsive Design | ⬜ Pass ⬜ Fail | |
| TC-15: Cache Invalidation | ⬜ Pass ⬜ Fail | |

### Overall Result: ⬜ Pass ⬜ Fail

### Issues Found:
1. 
2. 
3. 

### Recommendations:
1. 
2. 
3. 

---

## Namespace Consolidation Verification

### Verification Points:
- [ ] FaqResource uses `use Filament\Tables;` import
- [ ] All table actions use `Tables\Actions\` prefix
- [ ] All table columns use `Tables\Columns\` prefix
- [ ] All table filters use `Tables\Filters\` prefix
- [ ] No individual component imports remain
- [ ] Code is PSR-12 compliant
- [ ] No IDE warnings or errors
- [ ] Verification script passes

### Code Review Checklist:
- [ ] Import section has only `use Filament\Tables;`
- [ ] `Tables\Actions\EditAction::make()` used
- [ ] `Tables\Actions\DeleteAction::make()` used
- [ ] `Tables\Actions\CreateAction::make()` used
- [ ] `Tables\Actions\BulkActionGroup::make()` used
- [ ] `Tables\Actions\DeleteBulkAction::make()` used
- [ ] `Tables\Columns\TextColumn::make()` used
- [ ] `Tables\Columns\IconColumn::make()` used
- [ ] `Tables\Filters\SelectFilter::make()` used

---

## Notes
- This manual test should be performed after any changes to the FaqResource
- All test cases should pass before marking the task as complete
- Document any issues or unexpected behavior
- Take screenshots of any visual issues
- Report performance issues if page load exceeds 2 seconds
