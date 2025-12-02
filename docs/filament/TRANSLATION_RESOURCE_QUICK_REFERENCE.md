# TranslationResource Quick Reference

## TL;DR

TranslationResource manages translation strings for the platform. **Superadmin-only access**. Automatically publishes changes to PHP language files.

---

## Quick Access

**Routes**:
- List: `/admin/translations`
- Create: `/admin/translations/create`
- Edit: `/admin/translations/{id}/edit`

**Authorization**: Superadmin only (403 for all other roles)

---

## Key Features

### Empty Value Filtering

When you clear a language value in the form, it's automatically removed from the database:

```php
// User clears 'lt' field
// Before: ['en' => 'Hello', 'lt' => 'Labas', 'ru' => 'Привет']
// After:  ['en' => 'Hello', 'ru' => 'Привет']
```

### Automatic Publishing

All changes automatically publish to PHP language files:
- Create → Publishes
- Update → Republishes
- Delete → Republishes

---

## Common Tasks

### Create a Translation

1. Navigate to `/admin/translations`
2. Click "Create Translation"
3. Fill in:
   - **Group**: e.g., `app`, `auth`, `validation`
   - **Key**: e.g., `welcome`, `login.title`
   - **Values**: Fill in active languages (optional)
4. Click "Create"
5. PHP files automatically updated

### Edit a Translation

1. Navigate to `/admin/translations`
2. Click edit icon on translation
3. Modify values
4. Click "Save"
5. PHP files automatically republished

### Clear a Language Value

1. Edit translation
2. Clear the language field (set to empty)
3. Click "Save"
4. Empty value is removed from database

### Delete a Translation

1. Edit translation
2. Click "Delete" in header
3. Confirm deletion
4. PHP files automatically republished

---

## Form Fields

### Group (Required)
- Max 120 characters
- Alpha-dash only (letters, numbers, dashes, underscores)
- Example: `app`, `auth`, `validation`

### Key (Required)
- Max 255 characters
- Example: `welcome`, `login.title`, `errors.not_found`

### Language Values (Optional)
- One field per active language
- Can be left empty
- Empty values are automatically filtered out

---

## Authorization

| Role | Access |
|------|--------|
| SUPERADMIN | ✅ Full access |
| ADMIN | ❌ 403 Forbidden |
| MANAGER | ❌ 403 Forbidden |
| TENANT | ❌ 403 Forbidden |

---

## Data Flow

```
User Action → Form Submit → Filter Empty Values → Save to DB → Observer → TranslationPublisher → Update PHP Files
```

---

## Published Files

Translations are published to:
```
lang/
├── en/
│   ├── app.php
│   ├── auth.php
│   └── validation.php
├── lt/
│   ├── app.php
│   ├── auth.php
│   └── validation.php
└── ru/
    ├── app.php
    ├── auth.php
    └── validation.php
```

---

## Common Errors

### "The group field is required"
- Fill in the group field
- Use alpha-dash characters only

### "The key field is required"
- Fill in the key field

### "403 Forbidden"
- Only superadmins can access translations
- Check your user role

---

## Testing

```bash
# Run all translation tests
php artisan test --filter=TranslationResource

# Run create tests
php artisan test --filter=TranslationResourceCreateTest

# Run edit tests
php artisan test --filter=TranslationResourceEditTest
```

---

## Related Documentation

- **Full API**: [docs/filament/TRANSLATION_RESOURCE_PAGES_API.md](TRANSLATION_RESOURCE_PAGES_API.md)
- **Test Guide**: [docs/testing/TRANSLATION_RESOURCE_CREATE_TEST_GUIDE.md](../testing/TRANSLATION_RESOURCE_CREATE_TEST_GUIDE.md)
- **Edit Completion**: [docs/testing/TRANSLATION_RESOURCE_EDIT_COMPLETION.md](../testing/TRANSLATION_RESOURCE_EDIT_COMPLETION.md)

---

## Need Help?

1. Check the full API documentation
2. Review test files for examples
3. Check the changelog for recent changes
4. Contact the development team

---

**Last Updated**: 2025-11-29
