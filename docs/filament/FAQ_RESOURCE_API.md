# FaqResource API Reference

## Overview

**Resource**: `App\Filament\Resources\FaqResource`  
**Model**: `App\Models\Faq`  
**Access**: Admin and Superadmin only  
**Navigation Group**: System Management  
**Icon**: `heroicon-o-question-mark-circle`  
**Filament Version**: 4.x  
**Laravel Version**: 12.x

## Purpose

Provides CRUD operations for FAQ (Frequently Asked Questions) entries with rich text editing, publication control, category organization, and display order management.

---

## Authorization

### Access Control Matrix

| Role | View | Create | Edit | Delete | Navigation |
|------|------|--------|------|--------|------------|
| Superadmin | ✅ | ✅ | ✅ | ✅ | ✅ |
| Admin | ✅ | ✅ | ✅ | ✅ | ✅ |
| Manager | ❌ | ❌ | ❌ | ❌ | ❌ |
| Tenant | ❌ | ❌ | ❌ | ❌ | ❌ |

### Authorization Methods

```php
public static function canViewAny(): bool
public static function canCreate(): bool
public static function canEdit(Model $record): bool
public static function canDelete(Model $record): bool
public static function shouldRegisterNavigation(): bool
```

**Implementation**:
```php
private static function canAccessFaqManagement(): bool
{
    $user = auth()->user();
    return $user instanceof User && 
           in_array($user->role, [UserRole::ADMIN, UserRole::SUPERADMIN], true);
}
```

---

## Form Schema

### Form Structure

```php
public static function form(Schema $schema): Schema
```

**Returns**: Filament 4 Schema with FAQ entry fields

### Form Fields

#### Section: FAQ Entry

**Fields**:

1. **Question** (`question`)
   - Type: `TextInput`
   - Required: Yes
   - Max Length: 255
   - Validation: Required, max:255
   - Localization: `faq.labels.question`, `faq.placeholders.question`
   - Column Span: Full

2. **Category** (`category`)
   - Type: `TextInput`
   - Required: No
   - Max Length: 120
   - Validation: max:120
   - Localization: `faq.labels.category`, `faq.placeholders.category`
   - Helper Text: `faq.helper_text.category`

3. **Answer** (`answer`)
   - Type: `RichEditor`
   - Required: Yes
   - Toolbar: bold, italic, underline, bulletList, orderedList, link
   - Validation: Required
   - Localization: `faq.labels.answer`
   - Helper Text: `faq.helper_text.answer`
   - Column Span: Full

4. **Display Order** (`display_order`)
   - Type: `TextInput` (numeric)
   - Required: No
   - Default: 0
   - Min Value: 0
   - Validation: numeric, min:0
   - Localization: `faq.labels.display_order`
   - Helper Text: `faq.helper_text.order`

5. **Published** (`is_published`)
   - Type: `Toggle`
   - Required: No
   - Default: true
   - Inline: false
   - Localization: `faq.labels.published`
   - Helper Text: `faq.helper_text.published`

### Form Layout

```
┌─────────────────────────────────────────────┐
│ FAQ Entry Section                           │
├─────────────────────────────────────────────┤
│ Question (full width)                       │
├─────────────────────────────────────────────┤
│ Category          │ (empty)                 │
├─────────────────────────────────────────────┤
│ Answer (full width, rich editor)            │
├─────────────────────────────────────────────┤
│ Display Order     │ Published Toggle        │
└─────────────────────────────────────────────┘
```

---

## Table Configuration

### Table Structure

```php
public static function table(Table $table): Table
```

**Returns**: Configured Filament 4 Table with columns, filters, and actions

### Table Columns

#### 1. Question Column
```php
Tables\Columns\TextColumn::make('question')
    ->label(__('faq.labels.question'))
    ->wrap()
    ->searchable()
    ->sortable()
    ->weight('medium')
```

**Features**:
- Text wrapping enabled
- Searchable
- Sortable
- Medium font weight

#### 2. Category Column
```php
Tables\Columns\TextColumn::make('category')
    ->label(__('faq.labels.category'))
    ->badge()
    ->color('gray')
    ->sortable()
    ->toggleable()
    ->placeholder(__('app.common.dash'))
```

**Features**:
- Badge display
- Gray color
- Sortable
- Toggleable (can be hidden)
- Placeholder for null values

#### 3. Published Column
```php
Tables\Columns\IconColumn::make('is_published')
    ->label(__('faq.labels.published'))
    ->boolean()
    ->sortable()
    ->tooltip(fn (bool $state): string => 
        $state ? __('faq.helper_text.visible') : __('faq.helper_text.hidden')
    )
```

**Features**:
- Boolean icon display (✓/✗)
- Sortable
- Dynamic tooltip based on state

#### 4. Display Order Column
```php
Tables\Columns\TextColumn::make('display_order')
    ->label(__('faq.labels.order'))
    ->sortable()
    ->alignCenter()
    ->badge()
    ->color('primary')
```

**Features**:
- Badge display
- Primary color
- Center aligned
- Sortable

#### 5. Updated At Column
```php
Tables\Columns\TextColumn::make('updated_at')
    ->label(__('faq.labels.last_updated'))
    ->dateTime()
    ->since()
    ->sortable()
    ->toggleable(isToggledHiddenByDefault: true)
```

**Features**:
- Relative time display ("2 hours ago")
- Sortable
- Hidden by default (toggleable)

### Table Filters

#### 1. Publication Status Filter
```php
Tables\Filters\SelectFilter::make('is_published')
    ->label(__('faq.filters.status'))
    ->options([
        1 => __('faq.filters.options.published'),
        0 => __('faq.filters.options.draft'),
    ])
    ->native(false)
```

**Options**:
- Published (1)
- Draft (0)

#### 2. Category Filter
```php
Tables\Filters\SelectFilter::make('category')
    ->label(__('faq.filters.category'))
    ->options(fn (): array => self::getCategoryOptions())
    ->searchable()
    ->native(false)
```

**Features**:
- Dynamic options from database
- Searchable dropdown
- Cached for 1 hour

**Category Options Method**:
```php
private static function getCategoryOptions(): array
{
    return cache()->remember(
        'faq_categories',
        now()->addHours(1),
        fn (): array => Faq::query()
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category', 'category')
            ->toArray()
    );
}
```

### Table Actions

#### Row Actions
```php
->actions([
    Tables\Actions\EditAction::make()->iconButton(),
    Tables\Actions\DeleteAction::make()->iconButton(),
])
```

**Actions**:
- **Edit**: Icon button, opens edit page
- **Delete**: Icon button, requires confirmation

#### Bulk Actions
```php
->bulkActions([
    Tables\Actions\BulkActionGroup::make([
        Tables\Actions\DeleteBulkAction::make()
            ->requiresConfirmation()
            ->modalHeading(__('faq.modals.delete.heading'))
            ->modalDescription(__('faq.modals.delete.description')),
    ]),
])
```

**Actions**:
- **Bulk Delete**: Requires confirmation, shows modal

### Empty State

```php
->emptyStateHeading(__('faq.empty.heading'))
->emptyStateDescription(__('faq.empty.description'))
->emptyStateActions([
    Tables\Actions\CreateAction::make()
        ->label(__('faq.actions.add_first')),
])
```

**Features**:
- Custom heading and description
- "Add first FAQ" action button

### Table Settings

```php
->defaultSort('display_order', 'asc')
->persistSortInSession()
->persistSearchInSession()
->persistFiltersInSession()
```

**Persistence**:
- Default sort: Display order (ascending)
- Sort state persisted in session
- Search query persisted in session
- Filter state persisted in session

---

## Pages

### Page Registration

```php
public static function getPages(): array
{
    return [
        'index' => Pages\ListFaqs::route('/'),
        'create' => Pages\CreateFaq::route('/create'),
        'edit' => Pages\EditFaq::route('/{record}/edit'),
    ];
}
```

### Page Classes

#### 1. ListFaqs
**Path**: `app/Filament/Resources/FaqResource/Pages/ListFaqs.php`  
**Route**: `/admin/faqs`  
**Actions**: Create

```php
protected function getHeaderActions(): array
{
    return [
        Actions\CreateAction::make(),
    ];
}
```

#### 2. CreateFaq
**Path**: `app/Filament/Resources/FaqResource/Pages/CreateFaq.php`  
**Route**: `/admin/faqs/create`  
**Actions**: None (form submission)

#### 3. EditFaq
**Path**: `app/Filament/Resources/FaqResource/Pages/EditFaq.php`  
**Route**: `/admin/faqs/{record}/edit`  
**Actions**: Delete

```php
protected function getHeaderActions(): array
{
    return [
        Actions\DeleteAction::make(),
    ];
}
```

---

## Localization

### Translation Keys

#### Labels
- `faq.labels.resource` - Resource name
- `faq.labels.question` - Question field
- `faq.labels.category` - Category field
- `faq.labels.answer` - Answer field
- `faq.labels.display_order` - Display order field
- `faq.labels.published` - Published toggle
- `faq.labels.order` - Order column
- `faq.labels.last_updated` - Updated at column

#### Placeholders
- `faq.placeholders.question` - Question input placeholder
- `faq.placeholders.category` - Category input placeholder

#### Helper Text
- `faq.helper_text.entry` - Section description
- `faq.helper_text.category` - Category field help
- `faq.helper_text.answer` - Answer field help
- `faq.helper_text.order` - Display order help
- `faq.helper_text.published` - Published toggle help
- `faq.helper_text.visible` - Published tooltip
- `faq.helper_text.hidden` - Unpublished tooltip

#### Filters
- `faq.filters.status` - Status filter label
- `faq.filters.options.published` - Published option
- `faq.filters.options.draft` - Draft option
- `faq.filters.category` - Category filter label

#### Modals
- `faq.modals.delete.heading` - Delete modal heading
- `faq.modals.delete.description` - Delete modal description

#### Empty State
- `faq.empty.heading` - Empty state heading
- `faq.empty.description` - Empty state description
- `faq.actions.add_first` - Add first FAQ button

#### Sections
- `faq.sections.faq_entry` - Form section title

---

## Data Flow

### Create Flow

```
User clicks "Create" 
    ↓
CreateFaq page loads
    ↓
Form displays with defaults:
  - display_order: 0
  - is_published: true
    ↓
User fills form
    ↓
Validation runs:
  - question: required, max:255
  - category: max:120
  - answer: required
  - display_order: numeric, min:0
    ↓
Record created
    ↓
Redirect to list page
    ↓
Cache cleared (faq_categories)
```

### Edit Flow

```
User clicks edit icon
    ↓
EditFaq page loads with record
    ↓
Form displays with current values
    ↓
User modifies fields
    ↓
Validation runs
    ↓
Record updated
    ↓
Redirect to list page
    ↓
Cache cleared (faq_categories)
```

### Delete Flow

```
User clicks delete icon
    ↓
Confirmation modal displays
    ↓
User confirms
    ↓
Authorization check (canDelete)
    ↓
Record deleted
    ↓
Notification shown
    ↓
Table refreshes
    ↓
Cache cleared (faq_categories)
```

---

## Performance Considerations

### Caching

**Category Options Cache**:
- Key: `faq_categories`
- TTL: 1 hour
- Invalidation: Manual (on create/update/delete)

**Query Optimization**:
```php
Faq::query()
    ->whereNotNull('category')
    ->distinct()
    ->orderBy('category')
    ->pluck('category', 'category')
```

### Session Persistence

**Persisted State**:
- Sort column and direction
- Search query
- Filter selections

**Benefits**:
- Improved UX (state preserved across page loads)
- Reduced server load (fewer default queries)

---

## Filament 4 Migration

### Namespace Consolidation

**Before (Filament 3.x)**:
```php
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
```

**After (Filament 4.x)**:
```php
use Filament\Tables;
```

**Usage Pattern**:
```php
// Actions
Tables\Actions\EditAction::make()
Tables\Actions\DeleteAction::make()
Tables\Actions\CreateAction::make()
Tables\Actions\BulkActionGroup::make()
Tables\Actions\DeleteBulkAction::make()

// Columns
Tables\Columns\TextColumn::make()
Tables\Columns\IconColumn::make()

// Filters
Tables\Filters\SelectFilter::make()
```

**Benefits**:
- 87.5% reduction in import statements (8 → 1)
- Clearer component hierarchy
- Consistent with Filament 4 best practices
- Easier to identify component types

---

## Testing

### Manual Testing Checklist

#### List Page
- [ ] Page loads without errors
- [ ] FAQ entries display correctly
- [ ] Question column is searchable
- [ ] Category badges display
- [ ] Publication status icons show correctly
- [ ] Display order badges visible
- [ ] Updated timestamp shows relative time
- [ ] Filters work (publication status, category)
- [ ] Sort works on all sortable columns
- [ ] Edit/delete actions visible and functional

#### Create Page
- [ ] Create button visible on list page
- [ ] Form loads correctly
- [ ] All fields render properly
- [ ] Rich text editor loads with toolbar
- [ ] Validation works (required fields)
- [ ] Default values applied (display_order: 0, is_published: true)
- [ ] Save creates new FAQ
- [ ] Redirect to list page after save

#### Edit Page
- [ ] Edit form loads with existing data
- [ ] All fields editable
- [ ] Rich text content preserved
- [ ] Save updates FAQ
- [ ] Delete action available in header
- [ ] Redirect to list page after save/delete

#### Authorization
- [ ] Admin can access all operations
- [ ] Superadmin can access all operations
- [ ] Manager cannot access (404/403)
- [ ] Tenant cannot access (404/403)
- [ ] Navigation hidden for manager/tenant

### Automated Testing

**Test File**: `tests/Feature/Filament/FaqResourceTest.php` (to be created)

**Test Cases**:
```php
test('admin can view faq list')
test('admin can create faq')
test('admin can edit faq')
test('admin can delete faq')
test('manager cannot access faq resource')
test('tenant cannot access faq resource')
test('faq validation works correctly')
test('category filter works')
test('publication status filter works')
test('display order sorting works')
```

---

## Related Documentation

- [Batch 4 Resources Migration](../upgrades/BATCH_4_RESOURCES_MIGRATION.md)
- [Batch 4 Verification Guide](../testing/BATCH_4_VERIFICATION_GUIDE.md)
- [Laravel 12 + Filament 4 Upgrade](../upgrades/LARAVEL_12_FILAMENT_4_UPGRADE.md)
- [Filament V4 Compatibility Guide](./FILAMENT_V4_COMPATIBILITY_GUIDE.md)

---

## Changelog

### Version 1.1.0 (2025-11-24)
- **Changed**: Migrated to Filament 4 consolidated namespace pattern
- **Removed**: 8 individual action/column/filter imports
- **Added**: Consolidated `use Filament\Tables;` namespace
- **Impact**: 87.5% reduction in import statements
- **Status**: ✅ Verified with `verify-batch4-resources.php`

### Version 1.0.0 (2025-11-24)
- Initial implementation with Filament 4 API
- Admin and Superadmin access control
- Rich text editor for answers
- Category organization
- Display order management
- Publication status control

---

**Document Version**: 1.1.0  
**Last Updated**: 2025-11-24  
**Maintained By**: Development Team  
**Status**: ✅ Production Ready
