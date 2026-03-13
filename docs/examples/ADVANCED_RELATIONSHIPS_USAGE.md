# Advanced Eloquent Relationships - Usage Examples

This document provides comprehensive examples of using the advanced relationship patterns implemented in the system.

## Table of Contents
1. [Adding Traits to Models](#adding-traits-to-models)
2. [Polymorphic Comments](#polymorphic-comments)
3. [Polymorphic Attachments](#polymorphic-attachments)
4. [Tagging System](#tagging-system)
5. [Activity Logging](#activity-logging)
6. [Custom Pivot Models](#custom-pivot-models)
7. [Complex Queries](#complex-queries)
8. [Performance Optimization](#performance-optimization)

---

## Adding Traits to Models

First, add the traits to your models:

```php
// app/Models/Invoice.php
use App\Traits\HasComments;
use App\Traits\HasAttachments;
use App\Traits\HasTags;
use App\Traits\HasActivities;

class Invoice extends Model
{
    use HasFactory, BelongsToTenant;
    use HasComments, HasAttachments, HasTags, HasActivities;
    
    // ... rest of model
}

// app/Models/Property.php
class Property extends Model
{
    use BelongsToTenant, HasFactory;
    use HasComments, HasAttachments, HasTags, HasActivities;
    
    // ... rest of model
}

// app/Models/Meter.php
class Meter extends Model
{
    use HasFactory, BelongsToTenant;
    use HasComments, HasAttachments, HasTags, HasActivities;
    
    // ... rest of model
}
```

---

## Polymorphic Comments

### Adding Comments

```php
// Add a comment to an invoice
$invoice = Invoice::find(1);
$invoice->addComment(
    body: 'Payment received late, applied late fee',
    userId: auth()->id(),
    isInternal: true
);

// Add a public comment to a property
$property = Property::find(1);
$property->addComment(
    body: 'Tenant reported heating issue',
    userId: auth()->id(),
    isInternal: false
);

// Add a reply to an existing comment
$comment = Comment::find(1);
$reply = Comment::create([
    'tenant_id' => auth()->user()->tenant_id,
    'commentable_id' => $comment->commentable_id,
    'commentable_type' => $comment->commentable_type,
    'parent_id' => $comment->id,
    'user_id' => auth()->id(),
    'body' => 'Issue has been resolved',
    'is_internal' => false,
]);
```

### Retrieving Comments

```php
// Get all comments for an invoice
$comments = $invoice->comments;

// Get only top-level comments (no replies)
$topComments = $invoice->topLevelComments;

// Get internal comments only
$internalComments = $invoice->internalComments;

// Get comments with replies (nested)
$commentsWithReplies = $invoice->topLevelComments()
    ->with('replies.user')
    ->get();

// Get all descendants of a comment (recursive)
$comment = Comment::find(1);
$allReplies = $comment->descendants;

// Get pinned comments
$pinnedComments = $invoice->pinnedComments;
```

### Querying Comments

```php
// Find all invoices with comments from a specific user
$invoices = Invoice::whereHas('comments', function ($query) use ($userId) {
    $query->where('user_id', $userId);
})->get();

// Find properties with internal comments
$properties = Property::whereHas('internalComments')->get();

// Count comments per invoice
$invoices = Invoice::withCount('comments')->get();
foreach ($invoices as $invoice) {
    echo "Invoice #{$invoice->id} has {$invoice->comments_count} comments";
}
```

---

## Polymorphic Attachments

### Uploading Files

```php
// Attach a file to an invoice
$invoice = Invoice::find(1);
$file = request()->file('document');

$attachment = $invoice->attachFile(
    file: $file,
    uploadedBy: auth()->id(),
    description: 'Payment receipt',
    disk: 'public'
);

// Attach multiple files
foreach (request()->file('documents') as $file) {
    $property->attachFile(
        file: $file,
        uploadedBy: auth()->id(),
        description: 'Property inspection photo'
    );
}
```

### Retrieving Attachments

```php
// Get all attachments
$attachments = $invoice->attachments;

// Get only images
$images = $property->images;

// Get only documents (PDFs, Word, Excel)
$documents = $invoice->documents;

// Get attachment with uploader info
$attachments = $invoice->attachments()
    ->with('uploader')
    ->get();

foreach ($attachments as $attachment) {
    echo "{$attachment->original_filename} uploaded by {$attachment->uploader->name}";
    echo "Size: {$attachment->human_size}";
    echo "URL: {$attachment->url}";
}
```

### Querying Attachments

```php
// Find all invoices with PDF attachments
$invoices = Invoice::whereHas('attachments', function ($query) {
    $query->where('mime_type', 'application/pdf');
})->get();

// Get total storage used by property attachments
$property = Property::find(1);
$totalSize = $property->total_attachment_size; // in bytes

// Find large attachments (> 5MB)
$largeAttachments = Attachment::where('size', '>', 5 * 1024 * 1024)
    ->with('attachable')
    ->get();
```

---

## Tagging System

### Adding Tags

```php
// Create tags
$urgent = Tag::create([
    'tenant_id' => auth()->user()->tenant_id,
    'name' => 'Urgent',
    'slug' => 'urgent',
    'color' => '#ff0000',
]);

$maintenance = Tag::create([
    'tenant_id' => auth()->user()->tenant_id,
    'name' => 'Maintenance',
    'slug' => 'maintenance',
    'color' => '#ffa500',
]);

// Attach tags to a property
$property = Property::find(1);
$property->attachTags([$urgent, $maintenance], taggedBy: auth()->id());

// Attach tags by slug
$property->attachTags(['urgent', 'maintenance']);

// Sync tags (replace all existing)
$property->syncTags(['urgent', 'high-priority']);

// Detach specific tags
$property->detachTags([$urgent]);

// Detach all tags
$property->detachTags();
```

### Checking Tags

```php
// Check if property has a tag
if ($property->hasTag('urgent')) {
    // Handle urgent property
}

// Check if has any of the tags
if ($property->hasAnyTag(['urgent', 'maintenance'])) {
    // Handle tagged property
}

// Check if has all tags
if ($property->hasAllTags(['urgent', 'maintenance'])) {
    // Handle property with both tags
}

// Get tag names as array
$tagNames = $property->tag_names; // ['Urgent', 'Maintenance']
```

### Querying by Tags

```php
// Find all properties with 'urgent' tag
$urgentProperties = Property::withTag('urgent')->get();

// Find properties with any of these tags
$properties = Property::withAnyTag(['urgent', 'maintenance'])->get();

// Find properties with all of these tags
$properties = Property::withAllTags(['urgent', 'maintenance'])->get();

// Get most popular tags
$popularTags = Tag::popular(10)->get();

// Get unused tags
$unusedTags = Tag::unused()->get();

// Get all invoices tagged with a specific tag
$tag = Tag::where('slug', 'overdue')->first();
$invoices = $tag->invoices;
```

---

## Activity Logging

### Automatic Logging

Activities are automatically logged when models are created, updated, or deleted:

```php
// This automatically logs an activity
$invoice = Invoice::create([
    'tenant_id' => auth()->user()->tenant_id,
    'billing_period_start' => now()->startOfMonth(),
    'billing_period_end' => now()->endOfMonth(),
    'total_amount' => 150.00,
    'status' => InvoiceStatus::DRAFT,
]);

// Activity is created with:
// - description: "Invoice created"
// - event: "created"
// - properties: ['attributes' => [...]]
// - causer: current user
```

### Manual Logging

```php
// Log a custom activity
$invoice->logActivity(
    description: 'Invoice sent to tenant via email',
    event: 'sent',
    properties: [
        'email' => $tenant->email,
        'sent_at' => now()->toIso8601String(),
    ],
    logName: 'invoice_notifications'
);

// Log with batch UUID for grouping related activities
$batchUuid = Str::uuid()->toString();
request()->headers->set('X-Batch-UUID', $batchUuid);

$invoice1->logActivity('Bulk invoice generation - Invoice 1');
$invoice2->logActivity('Bulk invoice generation - Invoice 2');
$invoice3->logActivity('Bulk invoice generation - Invoice 3');
```

### Retrieving Activities

```php
// Get all activities for an invoice
$activities = $invoice->activities;

// Get activities with causer (user) information
$activities = $invoice->activities()
    ->with('causer')
    ->get();

foreach ($activities as $activity) {
    echo "{$activity->description} by {$activity->causer->name} at {$activity->created_at}";
}

// Get activities for a specific event
$createdActivities = $invoice->activitiesForEvent('created');

// Get activities caused by a specific user
$userActivities = $invoice->activitiesCausedBy(auth()->id());

// Get changes from an activity
$activity = Activity::find(1);
$changes = $activity->getChanges(); // New values
$oldValues = $activity->getOldValues(); // Old values
```

### Querying Activities

```php
// Get all activities in a specific log
$activities = Activity::inLog('invoice_notifications')->get();

// Get activities for a specific event type
$activities = Activity::forEvent('updated')->get();

// Get activities in a batch
$activities = Activity::inBatch($batchUuid)->get();

// Get all activities caused by a user
$activities = Activity::causedBy(auth()->user())->get();

// Get all activities for a specific subject
$activities = Activity::forSubject($invoice)->get();

// Get recent activities across all models
$recentActivities = Activity::where('tenant_id', auth()->user()->tenant_id)
    ->with(['subject', 'causer'])
    ->latest()
    ->limit(50)
    ->get();
```

### Customizing Activity Logging

```php
// In your model, override shouldLogActivity to customize
class Invoice extends Model
{
    use HasActivities;
    
    protected function shouldLogActivity(string $event): bool
    {
        // Only log created and status changes
        if ($event === 'created') {
            return true;
        }
        
        if ($event === 'updated' && $this->isDirty('status')) {
            return true;
        }
        
        return false;
    }
}
```

---

## Custom Pivot Models

### Using the Enhanced Property-Tenant Pivot

```php
// Update the Property model to use custom pivot
class Property extends Model
{
    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'property_tenant')
            ->using(PropertyTenantPivot::class) // Use custom pivot
            ->withPivot([
                'assigned_at',
                'vacated_at',
                'monthly_rent',
                'deposit_amount',
                'lease_type',
                'notes',
                'assigned_by'
            ])
            ->withTimestamps()
            ->wherePivotNull('vacated_at')
            ->orderByPivot('assigned_at', 'desc');
    }
    
    public function tenantAssignments(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'property_tenant')
            ->using(PropertyTenantPivot::class)
            ->withPivot([
                'assigned_at',
                'vacated_at',
                'monthly_rent',
                'deposit_amount',
                'lease_type',
                'notes',
                'assigned_by'
            ])
            ->withTimestamps()
            ->orderByPivot('assigned_at', 'desc');
    }
}
```

### Working with Pivot Data

```php
// Assign tenant to property with additional data
$property->tenants()->attach($tenant->id, [
    'assigned_at' => now(),
    'monthly_rent' => 500.00,
    'deposit_amount' => 1000.00,
    'lease_type' => 'standard',
    'notes' => 'First-time tenant',
    'assigned_by' => auth()->id(),
]);

// Access pivot data
$property = Property::with('tenants')->find(1);
foreach ($property->tenants as $tenant) {
    $pivot = $tenant->pivot; // PropertyTenantPivot instance
    
    echo "Tenant: {$tenant->name}";
    echo "Monthly Rent: {$pivot->monthly_rent}";
    echo "Assigned: {$pivot->assigned_at->format('Y-m-d')}";
    echo "Duration: {$pivot->getDurationInMonths()} months";
    echo "Total Rent Paid: {$pivot->getTotalRentPaid()}";
    
    if ($pivot->isActive()) {
        echo "Status: Active";
    }
}

// Mark tenant as vacated
$pivot = $property->tenants()->first()->pivot;
$pivot->markAsVacated();

// Query using pivot methods
$activeAssignments = PropertyTenantPivot::active()->get();
$endedAssignments = PropertyTenantPivot::ended()->get();
$shortTermLeases = PropertyTenantPivot::ofLeaseType('short-term')->get();
```

---

## Complex Queries

### Multi-Level Eager Loading

```php
// Load invoice with all related data
$invoice = Invoice::with([
    'tenant.property.building',
    'items.meter.property',
    'comments.user',
    'comments.replies.user',
    'attachments.uploader',
    'tags',
    'activities.causer',
])->find(1);

// Load properties with active tenants and their meters
$properties = Property::with([
    'tenants' => function ($query) {
        $query->wherePivotNull('vacated_at');
    },
    'tenants.pivot.assignedBy',
    'meters.readings' => function ($query) {
        $query->latest()->limit(1);
    },
    'building',
    'comments.user',
    'tags',
])->get();
```

### Complex Filtering

```php
// Find properties with urgent tags and recent comments
$properties = Property::withTag('urgent')
    ->whereHas('comments', function ($query) {
        $query->where('created_at', '>=', now()->subDays(7));
    })
    ->with(['comments' => function ($query) {
        $query->latest()->limit(5);
    }])
    ->get();

// Find invoices with attachments but no comments
$invoices = Invoice::has('attachments')
    ->doesntHave('comments')
    ->get();

// Find meters with tags and recent activities
$meters = Meter::withAnyTag(['maintenance', 'replacement'])
    ->whereHas('activities', function ($query) {
        $query->where('created_at', '>=', now()->subMonth());
    })
    ->get();
```

### Aggregations with Relationships

```php
// Count comments per invoice
$invoices = Invoice::withCount('comments')
    ->having('comments_count', '>', 5)
    ->get();

// Sum attachment sizes per property
$properties = Property::withSum('attachments', 'size')
    ->get();

// Average monthly rent from active tenant assignments
$avgRent = PropertyTenantPivot::active()
    ->avg('monthly_rent');

// Count activities per user
$users = User::withCount('activities')
    ->orderBy('activities_count', 'desc')
    ->get();
```

### Subquery Relationships

```php
// Get latest comment for each invoice
use Illuminate\Database\Eloquent\Builder;

$invoices = Invoice::addSelect([
    'latest_comment_id' => Comment::select('id')
        ->whereColumn('commentable_id', 'invoices.id')
        ->where('commentable_type', Invoice::class)
        ->latest()
        ->limit(1)
])->with('latestComment')->get();

// Add this relationship to Invoice model
public function latestComment()
{
    return $this->belongsTo(Comment::class, 'latest_comment_id');
}
```

---

## Performance Optimization

### Eager Loading to Prevent N+1

```php
// BAD: N+1 query problem
$invoices = Invoice::all();
foreach ($invoices as $invoice) {
    echo $invoice->tenant->name; // N+1 queries
    foreach ($invoice->comments as $comment) {
        echo $comment->user->name; // N+1 queries
    }
}

// GOOD: Eager load relationships
$invoices = Invoice::with([
    'tenant',
    'comments.user',
    'attachments',
    'tags',
])->get();

foreach ($invoices as $invoice) {
    echo $invoice->tenant->name; // No additional query
    foreach ($invoice->comments as $comment) {
        echo $comment->user->name; // No additional query
    }
}
```

### Lazy Eager Loading

```php
// Load relationship after initial query
$invoices = Invoice::all();

// Later, realize you need comments
$invoices->load('comments.user');

// Or load if not already loaded
$invoices->loadMissing('comments.user');
```

### Counting Related Models Efficiently

```php
// BAD: Loads all comments into memory
$invoice = Invoice::find(1);
$commentCount = $invoice->comments->count();

// GOOD: Counts in database
$commentCount = $invoice->comments()->count();

// BEST: Use withCount for multiple models
$invoices = Invoice::withCount(['comments', 'attachments', 'tags'])->get();
foreach ($invoices as $invoice) {
    echo "Comments: {$invoice->comments_count}";
    echo "Attachments: {$invoice->attachments_count}";
    echo "Tags: {$invoice->tags_count}";
}
```

### Chunking Large Result Sets

```php
// Process large number of invoices with comments
Invoice::with('comments')
    ->chunk(100, function ($invoices) {
        foreach ($invoices as $invoice) {
            // Process invoice
        }
    });

// Or use lazy collections for memory efficiency
Invoice::with('comments')
    ->lazy()
    ->each(function ($invoice) {
        // Process invoice
    });
```

### Caching Expensive Queries

```php
use Illuminate\Support\Facades\Cache;

// Cache popular tags for 1 hour
$popularTags = Cache::remember('popular_tags', 3600, function () {
    return Tag::popular(10)->get();
});

// Cache property with all relationships for 5 minutes
$property = Cache::remember("property.{$id}.full", 300, function () use ($id) {
    return Property::with([
        'building',
        'tenants',
        'meters.readings',
        'comments.user',
        'attachments',
        'tags',
    ])->find($id);
});
```

---

## Testing Examples

See the separate testing documentation for comprehensive test examples.

