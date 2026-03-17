---
name: eloquent-best-practices
description: Use when optimizing Laravel Eloquent queries, relationships, eager loading, scopes, aggregates, or model-layer performance issues such as N+1 queries.
---

# Eloquent Best Practices

## When Refactoring a Model

When the task is to review or refactor a specific Eloquent model, explicitly check:

- repeated `where(...)` chains that should become local scopes
- relationship correctness, inverse relations, and eager-loading strategy
- N+1 risks in loops, views, table formatters, widgets, and jobs
- whether `withCount()` or `withExists()` is a better fit than loading full relations
- casts, cheap accessors/mutators, and expensive computed attributes
- mass-assignment safety through explicit `$fillable`
- SQL-first filtering, `exists()` for booleans, and chunking/lazy iteration for large datasets
- indexes that match repeated filters, joins, and sort order
- logic that belongs in actions, services, query objects, observers, or policies instead of the model

For model-refactor requests, prefer returning the full refactored model first, then a concise flat list of practical improvements.

## Query Optimization

### Use Small Composable Scopes

```php
class Invoice extends Model
{
    public function scopeForOrganization($query, int $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeOutstanding($query)
    {
        return $query->whereColumn('amount_paid', '<', 'total_amount');
    }

    public function scopeOrdered($query)
    {
        return $query->orderByDesc('due_date')->orderByDesc('id');
    }
}
```

- Keep scopes focused and chainable
- Use query builders/query objects only when scopes become too numerous or branch heavily

### Always Eager Load Relationships

```php
// ❌ N+1 Query Problem
$posts = Post::all();
foreach ($posts as $post) {
    echo $post->user->name; // N additional queries
}

// ✅ Eager Loading
$posts = Post::with('user')->get();
foreach ($posts as $post) {
    echo $post->user->name; // No additional queries
}

// ✅ Constrained eager loading
$properties = Property::with([
    'meters' => fn ($query) => $query->select(['id', 'property_id', 'name'])->active(),
])->get();
```

### Select Only Needed Columns

```php
// ❌ Fetches all columns
$users = User::all();

// ✅ Only needed columns
$users = User::select(['id', 'name', 'email'])->get();

// ✅ With relationships
$posts = Post::with(['user:id,name'])->select(['id', 'title', 'user_id'])->get();
```

### Use Query Scopes

```php
// ✅ Define reusable query logic
class Post extends Model
{
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
                    ->whereNotNull('published_at');
    }
    
    public function scopePopular($query, $threshold = 100)
    {
        return $query->where('views', '>', $threshold);
    }
}

// Usage
$posts = Post::published()->popular()->get();
```

## Relationship Best Practices

### Define Return Types

```php
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}
```

### Use withCount for Counts

```php
// ❌ Triggers additional queries
foreach ($posts as $post) {
    echo $post->comments()->count();
}

// ✅ Load counts efficiently
$posts = Post::withCount('comments')->get();
foreach ($posts as $post) {
    echo $post->comments_count;
}

// ✅ Use withExists for booleans
$posts = Post::withExists('comments')->get();
foreach ($posts as $post) {
    echo $post->comments_exists ? 'Yes' : 'No';
}
```

## Mass Assignment Protection

```php
class Post extends Model
{
    // ✅ Whitelist fillable attributes
    protected $fillable = ['title', 'content', 'status'];
}
```

- Prefer explicit `$fillable` over `$guarded`
- Avoid `protected $guarded = [];`

## Use Casts for Type Safety

```php
class Post extends Model
{
    protected $casts = [
        'published_at' => 'datetime',
        'metadata' => 'array',
        'is_featured' => 'boolean',
        'views' => 'integer',
    ];
}
```

## Prefer Exists for Boolean Checks

```php
// ❌ Loads more work than needed
if (Invoice::where('tenant_id', $tenantId)->count() > 0) {
    // ...
}

// ✅ Stops at the first match
if (Invoice::where('tenant_id', $tenantId)->exists()) {
    // ...
}
```

## Chunking for Large Datasets

```php
// ✅ Process in chunks to save memory
Post::chunk(200, function ($posts) {
    foreach ($posts as $post) {
        // Process each post
    }
});

// ✅ Or use lazy collections
Post::lazy()->each(function ($post) {
    // Process one at a time
});
```

## Database-Level Operations

```php
// ❌ Slow - loads into memory first
$posts = Post::where('status', 'draft')->get();
foreach ($posts as $post) {
    $post->update(['status' => 'archived']);
}

// ✅ Fast - single query
Post::where('status', 'draft')->update(['status' => 'archived']);

// ✅ Increment/decrement
Post::where('id', $id)->increment('views');
```

## Use Model Events Wisely

```php
class Post extends Model
{
    protected static function booted()
    {
        static::creating(function ($post) {
            $post->slug = Str::slug($post->title);
        });
        
        static::deleting(function ($post) {
            $post->comments()->delete();
        });
    }
}
```

Prefer observers for cross-cutting side effects once the hooks become non-trivial or are shared by multiple write paths.

## Common Pitfalls to Avoid

### Don't Query in Loops

```php
// ❌ Bad
foreach ($userIds as $id) {
    $user = User::find($id);
}

// ✅ Good
$users = User::whereIn('id', $userIds)->get();
```

### Don't Filter Large Collections in PHP

```php
// ❌ Pulls too much into memory
$overdue = $invoices->filter(fn ($invoice) => $invoice->due_date->isPast());

// ✅ Keep the filtering in SQL
$overdue = Invoice::query()->whereDate('due_date', '<', today())->get();
```

### Select Only What the UI Needs

```php
$users = User::query()
    ->select(['id', 'organization_id', 'name', 'email'])
    ->with('organization:id,name')
    ->get();
```

### Don't Forget Indexes

```php
// Migration
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->index();
    $table->string('slug')->unique();
    $table->string('status')->index();
    $table->timestamp('published_at')->nullable()->index();
    
    // Composite index for common queries
    $table->index(['status', 'published_at']);
});
```

Add indexes for:
- repeated organization/tenant/property scopes
- latest-first queries such as `(created_at, id)` or `(reading_date, id)`
- status/date combinations used by dashboards, tables, and widgets

### Prevent Lazy Loading in Development

```php
// In AppServiceProvider boot method
Model::preventLazyLoading(!app()->isProduction());
```

## Checklist

- [ ] Relationships eagerly loaded where needed
- [ ] Only selecting required columns
- [ ] Using query scopes for reusability
- [ ] Explicit `$fillable` used for mass-assigned models
- [ ] `exists()` used for boolean checks
- [ ] `withCount()` / `withExists()` used where the UI does not need full relations
- [ ] Large collection filtering left in SQL
- [ ] Appropriate casts defined
- [ ] Indexes on foreign keys and query columns
- [ ] Using database-level operations when possible
- [ ] Chunking for large datasets
- [ ] Model events used appropriately
- [ ] Lazy loading prevented in development
