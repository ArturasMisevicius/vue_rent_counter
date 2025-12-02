# Advanced Relationships - Quick Reference

## 🚀 Quick Start

### 1. Add Traits to Model

```php
use App\Traits\{HasComments, HasAttachments, HasTags, HasActivities};

class Invoice extends Model
{
    use HasComments, HasAttachments, HasTags, HasActivities;
}
```

### 2. Use the Features

```php
// Comments
$invoice->addComment('Payment received', auth()->id());

// Attachments
$invoice->attachFile($file, auth()->id(), 'Receipt');

// Tags
$invoice->attachTags(['urgent', 'overdue']);

// Activities (automatic)
$invoice->update(['status' => InvoiceStatus::PAID]);
```

---

## 📝 Comments Cheat Sheet

```php
// Add comment
$model->addComment($body, $userId, $isInternal = false);

// Get comments
$model->comments;                    // All comments
$model->topLevelComments;            // No replies
$model->internalComments;            // Internal only
$model->publicComments;              // Public only
$model->pinnedComments;              // Pinned only

// Add reply
Comment::create([
    'parent_id' => $parentComment->id,
    'commentable_id' => $model->id,
    'commentable_type' => get_class($model),
    'user_id' => auth()->id(),
    'body' => 'Reply text',
]);

// Query
Model::whereHas('comments', function($q) {
    $q->where('user_id', $userId);
})->get();

// Count
$model->comment_count;               // Attribute
Model::withCount('comments')->get(); // Query
```

---

## 📎 Attachments Cheat Sheet

```php
// Attach file
$model->attachFile($file, $userId, $description = null, $disk = 'local');

// Get attachments
$model->attachments;                 // All files
$model->images;                      // Images only
$model->documents;                   // Documents only

// File info
$attachment->url;                    // Full URL
$attachment->human_size;             // "1.5 MB"
$attachment->isImage();              // Boolean
$attachment->isPdf();                // Boolean

// Query
Model::whereHas('attachments', function($q) {
    $q->where('mime_type', 'application/pdf');
})->get();

// Count
$model->attachment_count;            // Attribute
$model->total_attachment_size;       // Total bytes
Model::withCount('attachments')->get(); // Query
```

---

## 🏷️ Tags Cheat Sheet

```php
// Attach tags
$model->attachTags(['tag1', 'tag2'], $taggedBy);
$model->attachTags([$tag1, $tag2]);  // Tag objects

// Detach tags
$model->detachTags(['tag1']);        // Specific
$model->detachTags();                // All

// Sync tags (replace all)
$model->syncTags(['tag1', 'tag2']);

// Check tags
$model->hasTag('urgent');            // Boolean
$model->hasAnyTag(['urgent', 'high']); // Boolean
$model->hasAllTags(['urgent', 'high']); // Boolean
$model->tag_names;                   // Array of names

// Query
Model::withTag('urgent')->get();
Model::withAnyTag(['urgent', 'high'])->get();
Model::withAllTags(['urgent', 'high'])->get();

// Tag management
Tag::popular(10)->get();             // Most used
Tag::unused()->get();                // Unused tags
$tag->updateUsageCount();            // Refresh count
```

---

## 📊 Activities Cheat Sheet

```php
// Log activity (manual)
$model->logActivity($description, $event = null, $properties = null, $logName = null);

// Automatic logging (via trait)
$model->create([...]);               // Logs 'created'
$model->update([...]);               // Logs 'updated'
$model->delete();                    // Logs 'deleted'

// Get activities
$model->activities;                  // All activities
$model->activitiesForEvent('updated'); // Specific event
$model->activitiesCausedBy($userId); // By user

// Activity data
$activity->getChanges();             // New values
$activity->getOldValues();           // Old values
$activity->subject;                  // The model
$activity->causer;                   // Who did it

// Query
Activity::inLog('invoice_notifications')->get();
Activity::forEvent('updated')->get();
Activity::causedBy($user)->get();
Activity::forSubject($model)->get();

// Customize logging
protected function shouldLogActivity(string $event): bool
{
    return in_array($event, ['created', 'updated']);
}
```

---

## 🔗 Custom Pivot Cheat Sheet

```php
// Define relationship with custom pivot
public function tenants(): BelongsToMany
{
    return $this->belongsToMany(Tenant::class, 'property_tenant')
        ->using(PropertyTenantPivot::class)
        ->withPivot(['assigned_at', 'monthly_rent', 'deposit_amount'])
        ->withTimestamps();
}

// Attach with pivot data
$property->tenants()->attach($tenant->id, [
    'assigned_at' => now(),
    'monthly_rent' => 500.00,
    'deposit_amount' => 1000.00,
    'lease_type' => 'standard',
]);

// Access pivot
$tenant = $property->tenants->first();
$pivot = $tenant->pivot;             // PropertyTenantPivot instance
$pivot->monthly_rent;                // 500.00
$pivot->getDurationInMonths();       // Custom method
$pivot->isActive();                  // Custom method

// Query pivot
PropertyTenantPivot::active()->get();
PropertyTenantPivot::ofLeaseType('standard')->get();
```

---

## 🔍 Complex Queries

### Eager Loading (Prevent N+1)

```php
// Good
$invoices = Invoice::with([
    'tenant',
    'comments.user',
    'attachments',
    'tags',
    'activities.causer',
])->get();

// Bad (N+1 queries)
$invoices = Invoice::all();
foreach ($invoices as $invoice) {
    $invoice->comments; // Separate query each time
}
```

### Counting Relationships

```php
// Efficient counting
$invoices = Invoice::withCount([
    'comments',
    'attachments',
    'tags',
])->get();

foreach ($invoices as $invoice) {
    echo $invoice->comments_count;   // No query
}
```

### Filtering by Relationships

```php
// Has relationship
Invoice::has('comments')->get();
Invoice::has('comments', '>', 5)->get();

// Doesn't have relationship
Invoice::doesntHave('comments')->get();

// Where has (with conditions)
Invoice::whereHas('comments', function($q) {
    $q->where('is_internal', false);
})->get();

// Where doesn't have (with conditions)
Invoice::whereDoesntHave('comments', function($q) {
    $q->where('is_internal', true);
})->get();
```

### Multiple Relationship Filters

```php
Property::withTag('urgent')
    ->whereHas('comments', function($q) {
        $q->where('created_at', '>=', now()->subDays(7));
    })
    ->whereHas('attachments')
    ->with(['comments', 'attachments', 'tags'])
    ->get();
```

---

## ⚡ Performance Tips

```php
// 1. Always eager load
Model::with(['relation1', 'relation2'])->get();

// 2. Use withCount for counting
Model::withCount('comments')->get();

// 3. Lazy eager loading (if needed later)
$models->load('comments');
$models->loadMissing('comments');

// 4. Chunk large datasets
Model::with('comments')->chunk(100, function($models) {
    // Process models
});

// 5. Use lazy collections
Model::with('comments')->lazy()->each(function($model) {
    // Process model
});

// 6. Cache expensive queries
Cache::remember('key', 3600, function() {
    return Model::with('relations')->get();
});
```

---

## 🧪 Testing Quick Reference

```php
// Factory usage
Comment::factory()->for($invoice)->create();
Comment::factory()->internal()->create();
Comment::factory()->reply($parent)->create();

Attachment::factory()->for($property)->create();
Attachment::factory()->image()->create();

Tag::factory()->create();

// Assertions
$this->assertDatabaseHas('comments', ['body' => 'Test']);
$this->assertCount(3, $model->comments);
$this->assertTrue($model->hasTag('urgent'));
$this->assertInstanceOf(Comment::class, $model->comments->first());

// Relationship tests
$this->assertInstanceOf(Invoice::class, $comment->commentable);
$this->assertEquals($invoice->id, $comment->commentable->id);
```

---

## 🔐 Security Checklist

```php
// 1. Always scope by tenant
Comment::where('tenant_id', auth()->user()->tenant_id)->get();

// 2. Use policies
Gate::authorize('update', $comment);

// 3. Validate file uploads
$request->validate([
    'file' => 'required|file|max:10240|mimes:pdf,jpg,png',
]);

// 4. Check ownership
if ($comment->user_id !== auth()->id()) {
    abort(403);
}

// 5. Sanitize input
$body = strip_tags($request->input('body'));
```

---

## 📚 Common Patterns

### Pattern 1: Add Comment with Notification

```php
$comment = $invoice->addComment($body, auth()->id());
$invoice->tenant->notify(new CommentAddedNotification($comment));
```

### Pattern 2: Bulk Tag Assignment

```php
$tag = Tag::firstOrCreate(['slug' => 'urgent'], ['name' => 'Urgent']);
$models->each(fn($model) => $model->attachTags([$tag]));
```

### Pattern 3: Activity Log with Context

```php
$model->logActivity(
    description: 'Invoice sent to tenant',
    event: 'sent',
    properties: [
        'email' => $tenant->email,
        'sent_at' => now()->toIso8601String(),
    ]
);
```

### Pattern 4: File Upload with Validation

```php
$validated = $request->validate([
    'file' => 'required|file|max:10240|mimes:pdf',
]);

$attachment = $model->attachFile(
    $validated['file'],
    auth()->id(),
    $request->input('description')
);
```

### Pattern 5: Nested Comments Thread

```php
$thread = $model->topLevelComments()
    ->with(['replies.user', 'replies.replies.user'])
    ->get();
```

---

## 🆘 Troubleshooting

| Problem | Solution |
|---------|----------|
| N+1 queries | Use `with()` to eager load |
| Polymorphic type not found | Check morph map or use full class names |
| File not found | Verify storage disk configuration |
| Tenant isolation not working | Ensure `BelongsToTenant` trait is used |
| Tags not updating count | Call `$tag->updateUsageCount()` |
| Activities not logging | Check `shouldLogActivity()` method |
| Pivot data not accessible | Use `withPivot()` in relationship |

---

## 📖 Full Documentation

- Usage Examples: `docs/examples/ADVANCED_RELATIONSHIPS_USAGE.md`
- Testing Guide: `docs/examples/ADVANCED_RELATIONSHIPS_TESTING.md`
- Complete Example: `docs/examples/COMPLETE_INTEGRATION_EXAMPLE.php`
- Implementation Checklist: `docs/IMPLEMENTATION_CHECKLIST.md`
- Summary: `docs/ADVANCED_RELATIONSHIPS_SUMMARY.md`

