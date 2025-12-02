# Advanced Eloquent Relationships - Implementation Summary

## 📋 Overview

This document summarizes the advanced Eloquent relationship patterns implemented in the Vilnius Utilities Billing system.

## ✅ What Was Implemented

### 1. Polymorphic Comments System
- **Models**: `Comment`
- **Trait**: `HasComments`
- **Features**:
  - Comments can be attached to any model (Invoice, Property, Meter, Building, etc.)
  - Nested comments (replies) with unlimited depth
  - Internal vs public comments
  - Pinned comments
  - Edit tracking

### 2. Polymorphic File Attachments
- **Models**: `Attachment`
- **Trait**: `HasAttachments`
- **Features**:
  - Files can be attached to any model
  - Automatic file type detection (images, PDFs, documents)
  - File size tracking and human-readable formatting
  - Metadata storage
  - Automatic cleanup on model deletion

### 3. Tagging System (Morph-to-Many)
- **Models**: `Tag`, pivot table `taggables`
- **Trait**: `HasTags`
- **Features**:
  - Tags can be attached to multiple model types
  - Usage count tracking
  - Tag filtering and searching
  - Popular/unused tag queries
  - Bulk tag operations (attach, detach, sync)

### 4. Activity Logging
- **Models**: `Activity`
- **Trait**: `HasActivities`
- **Features**:
  - Automatic logging of model events (created, updated, deleted)
  - Custom activity logging
  - Batch activity grouping
  - Change tracking (old vs new values)
  - Polymorphic subject and causer relationships

### 5. Enhanced Pivot Models
- **Models**: `PropertyTenantPivot`
- **Features**:
  - Custom pivot model with additional data (rent, deposit, lease type)
  - Business logic methods (duration calculations, status checks)
  - Pivot-specific scopes and queries
  - Relationship to other models (assignedBy user)

## 📁 Files Created

### Migrations
```
database/migrations/
├── 2025_12_02_100000_create_comments_table.php
├── 2025_12_02_100001_create_attachments_table.php
├── 2025_12_02_100002_create_tags_and_taggables_table.php
├── 2025_12_02_100003_create_activities_table.php
├── 2025_12_02_100004_enhance_property_tenant_pivot.php
└── 2025_12_02_100005_create_user_permissions_table.php
```

### Models
```
app/Models/
├── Comment.php
├── Attachment.php
├── Tag.php
├── Activity.php
└── PropertyTenantPivot.php
```

### Traits
```
app/Traits/
├── HasComments.php
├── HasAttachments.php
├── HasTags.php
└── HasActivities.php
```

### Documentation
```
docs/examples/
├── ADVANCED_RELATIONSHIPS_USAGE.md
└── ADVANCED_RELATIONSHIPS_TESTING.md
```

## 🚀 Quick Start

### Step 1: Run Migrations

```bash
php artisan migrate
```

### Step 2: Add Traits to Models

```php
// app/Models/Invoice.php
use App\Traits\{HasComments, HasAttachments, HasTags, HasActivities};

class Invoice extends Model
{
    use HasFactory, BelongsToTenant;
    use HasComments, HasAttachments, HasTags, HasActivities;
    
    // ... rest of model
}
```

### Step 3: Use the Relationships

```php
// Add a comment
$invoice->addComment('Payment received', auth()->id());

// Attach a file
$invoice->attachFile($request->file('receipt'), auth()->id());

// Add tags
$invoice->attachTags(['urgent', 'overdue']);

// Activity is logged automatically
$invoice->update(['status' => InvoiceStatus::PAID]);
```

## 📊 Relationship Patterns Used

### 1. One-to-Many Polymorphic (morphMany)
- Comments → Commentable models
- Attachments → Attachable models
- Activities → Subject models

### 2. Many-to-Many Polymorphic (morphToMany)
- Tags ↔ Multiple model types via `taggables` pivot

### 3. Self-Referencing (hasMany/belongsTo)
- Comments → Parent comment
- Comments → Replies (children)

### 4. Custom Pivot Models
- PropertyTenantPivot with additional business logic

### 5. Has-Many-Through
- Already implemented: Tenant → MeterReadings through Property

### 6. Polymorphic Many-to-Many with Pivot Data
- Tags with `tagged_by` user tracking

## 🎯 Use Cases

### Comments
- Invoice payment notes
- Property maintenance issues
- Meter reading clarifications
- Internal team communication

### Attachments
- Invoice receipts
- Property inspection photos
- Meter installation certificates
- Lease agreements

### Tags
- Categorize properties (urgent, maintenance, renovation)
- Flag invoices (overdue, disputed, paid)
- Mark meters (replacement-needed, calibration-due)

### Activities
- Audit trail for all model changes
- User action tracking
- Compliance and reporting
- Debugging and troubleshooting

## 🔧 Configuration

### Morph Map (Optional)

For cleaner polymorphic type names in the database:

```php
// app/Providers/AppServiceProvider.php
use Illuminate\Database\Eloquent\Relations\Relation;

public function boot(): void
{
    Relation::enforceMorphMap([
        'invoice' => \App\Models\Invoice::class,
        'property' => \App\Models\Property::class,
        'meter' => \App\Models\Meter::class,
        'building' => \App\Models\Building::class,
        'tenant' => \App\Models\Tenant::class,
        'user' => \App\Models\User::class,
    ]);
}
```

### File Storage Configuration

```php
// config/filesystems.php
'disks' => [
    'attachments' => [
        'driver' => 'local',
        'root' => storage_path('app/attachments'),
        'visibility' => 'private',
    ],
],
```

## 📈 Performance Considerations

### Always Eager Load Relationships

```php
// Good
$invoices = Invoice::with(['comments.user', 'attachments', 'tags'])->get();

// Bad (N+1 queries)
$invoices = Invoice::all();
foreach ($invoices as $invoice) {
    $invoice->comments; // Separate query for each invoice
}
```

### Use withCount for Counting

```php
// Good
$invoices = Invoice::withCount(['comments', 'attachments'])->get();

// Bad
$invoices = Invoice::with(['comments', 'attachments'])->get();
$count = $invoice->comments->count(); // Loads all comments into memory
```

### Index Polymorphic Columns

All migrations include composite indexes on polymorphic columns:
```php
$table->index(['commentable_type', 'commentable_id', 'created_at']);
```

## 🧪 Testing

Comprehensive test examples are provided in:
- [docs/examples/ADVANCED_RELATIONSHIPS_TESTING.md](examples/ADVANCED_RELATIONSHIPS_TESTING.md)

Key testing areas:
- Unit tests for each model
- Feature tests for user workflows
- Relationship integrity tests
- Performance tests (N+1 prevention)
- Factory definitions for all models

## 🔐 Security Considerations

### Tenant Isolation

All models include `tenant_id` and use the `BelongsToTenant` trait:

```php
// Automatic tenant scoping
$comments = Comment::all(); // Only returns current tenant's comments
```

### Authorization

Use policies to control access:

```php
// app/Policies/CommentPolicy.php
public function update(User $user, Comment $comment): bool
{
    return $user->id === $comment->user_id 
        && $user->tenant_id === $comment->tenant_id;
}
```

### File Upload Validation

```php
$request->validate([
    'file' => 'required|file|max:10240|mimes:pdf,jpg,png,doc,docx',
]);
```

## 📚 Additional Resources

- [Laravel Eloquent Relationships Documentation](https://laravel.com/docs/eloquent-relationships)
- [Polymorphic Relationships Guide](https://laravel.com/docs/eloquent-relationships#polymorphic-relationships)
- Usage Examples: [docs/examples/ADVANCED_RELATIONSHIPS_USAGE.md](examples/ADVANCED_RELATIONSHIPS_USAGE.md)
- Testing Guide: [docs/examples/ADVANCED_RELATIONSHIPS_TESTING.md](examples/ADVANCED_RELATIONSHIPS_TESTING.md)

## 🎓 Next Steps

1. **Run migrations**: `php artisan migrate`
2. **Add traits to models**: Choose which models need which features
3. **Create factories**: For testing and seeding
4. **Write tests**: Ensure relationships work correctly
5. **Update Filament resources**: Add UI for comments, attachments, tags
6. **Configure file storage**: Set up proper disk for attachments
7. **Implement policies**: Control access to relationships

## 💡 Tips

- Start with one model (e.g., Invoice) and add all traits
- Test thoroughly before adding to other models
- Use eager loading to prevent N+1 queries
- Consider caching for frequently accessed data
- Monitor database query performance
- Keep polymorphic type names consistent

## 🐛 Troubleshooting

### Issue: N+1 Queries
**Solution**: Always use eager loading with `with()`

### Issue: Polymorphic Type Not Found
**Solution**: Check morph map configuration or use full class names

### Issue: File Not Found
**Solution**: Verify storage disk configuration and file paths

### Issue: Tenant Isolation Not Working
**Solution**: Ensure `BelongsToTenant` trait is used and `tenant_id` is set

---

**Implementation Date**: December 2, 2025  
**Laravel Version**: 12.x  
**PHP Version**: 8.3+

