---
name: "laravel-models"
description: Use when creating, reviewing, or refactoring Laravel Eloquent models, relationships, casts, scopes, observers, or model-layer query patterns.
---
# Laravel Models

Models represent database tables and domain entities.

**Related guides:**
- [Query Builders](../laravel-query-builders/SKILL.md) - Custom query builders (not scopes)
- [Actions](../laravel-actions/SKILL.md) - Actions contain business logic
- [DTOs](../laravel-dtos/SKILL.md) - Casting model JSON columns to DTOs

## Philosophy

Models should:
- Prefer **small, composable local scopes** for repeated query conditions
- Use custom query builders or query objects only when query composition becomes too large or cross-model
- Define relationships
- Define casts
- Contain simple accessors/mutators
- Keep mass assignment explicit with `$fillable`
- **NOT contain deep business logic** (that belongs in Actions, services, policies, or observers)

## Optimization Checklist

- Extract repeated filters into expressive scopes like `forOrganization()`, `active()`, `ordered()`, `pending()`
- Keep scopes focused and chainable; always return the builder
- Use `with()`, constrained eager loading, `withCount()`, and `withExists()` to prevent N+1 queries
- Select only needed columns for summary/workspace queries instead of relying on `SELECT *`
- Prefer `exists()` over `count() > 0` for boolean checks
- Avoid filtering big collections in PHP when the database can do it
- Add indexes for frequently combined `where`, `orderBy`, and relationship lookup columns
- Move workflows, side effects, and authorization decisions out of the model

## When Refactoring an Existing Model

When asked to review or refactor a specific model, cover these concerns explicitly:

- query optimization through small, composable local scopes
- relationship correctness, inverse relationships, and eager-loading strategy
- N+1 risks in tables, dashboards, Blade views, jobs, or loops
- casts, cheap accessors/mutators, and removal of expensive computed attributes
- safe mass assignment with explicit `$fillable`
- SQL-first filtering, `exists()` for booleans, and chunking/lazy iteration for large datasets
- index recommendations based on repeated filters, joins, and sort order
- whether workflows, side effects, or authorization should move to actions, services, observers, or policies

For model-focused review/refactor requests, prefer this response shape:

1. return the fully refactored model code
2. list the practical improvements in flat bullets
3. call out performance risks or follow-up architecture changes only when they materially matter

## Scope-First Model Structure

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'total',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'total' => 'integer',
        ];
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', OrderStatus::Pending);
    }

    public function scopeWithListSummary(Builder $query): Builder
    {
        return $query
            ->select(['id', 'user_id', 'status', 'total', 'created_at'])
            ->with('user:id,name');
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
```

Use a dedicated query builder or query object when:
- the scope list becomes difficult to discover or maintain
- the query needs branching strategies, reusable filter DTOs, or cross-model orchestration
- the same complex query is shared by multiple UI surfaces

## Casts

**Define casts for type safety:**

```php
protected function casts(): array
{
    return [
        'status' => OrderStatus::class,         // Enum
        'total' => 'integer',                   // Integer
        'is_paid' => 'boolean',                 // Boolean
        'metadata' => OrderMetadataData::class, // DTO
        'completed_at' => 'datetime',           // Carbon
        'tags' => 'array',                      // JSON array
    ];
}
```

**Available casts:**
- `'integer'`, `'real'`, `'float'`, `'double'`
- `'string'`, `'boolean'`
- `'array'`, `'json'`, `'object'`, `'collection'`
- `'date'`, `'datetime'`, `'immutable_date'`, `'immutable_datetime'`
- `'timestamp'`
- `'encrypted'`, `'encrypted:array'`, `'encrypted:collection'`, `'encrypted:json'`, `'encrypted:object'`
- Custom cast classes
- Enum classes
- DTO classes

## Relationships

### Relationship Review Rules

- Name relationships by what they return, not how they are implemented
- Add inverse relationships when consumers need them (`belongsTo`, `morphTo`, pivot relations)
- Prefer summary eager-load helpers for UI-driven queries
- Use constrained eager loading for nested dashboards, tables, and portal summaries
- Use `withCount()` / `withExists()` instead of loading full relations when only aggregates or booleans are needed

### BelongsTo

```php
public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}

public function customer(): BelongsTo
{
    return $this->belongsTo(Customer::class, 'customer_id', 'id');
}
```

### HasMany

```php
public function orders(): HasMany
{
    return $this->hasMany(Order::class);
}

public function items(): HasMany
{
    return $this->hasMany(OrderItem::class);
}
```

### HasOne

```php
public function profile(): HasOne
{
    return $this->hasOne(UserProfile::class);
}
```

### BelongsToMany

```php
public function roles(): BelongsToMany
{
    return $this->belongsToMany(Role::class)
        ->withTimestamps()
        ->withPivot('assigned_at');
}
```

### HasManyThrough

```php
public function deployments(): HasManyThrough
{
    return $this->hasManyThrough(Deployment::class, Environment::class);
}
```

### MorphTo / MorphMany

```php
// MorphTo
public function commentable(): MorphTo
{
    return $this->morphTo();
}

// MorphMany
public function comments(): MorphMany
{
    return $this->morphMany(Comment::class, 'commentable');
}
```

## Accessors & Mutators

### Accessors (Get)

```php
use Illuminate\Database\Eloquent\Casts\Attribute;

protected function fullName(): Attribute
{
    return Attribute::make(
        get: fn () => "{$this->first_name} {$this->last_name}",
    );
}

// Usage
$user->full_name; // "John Doe"
```

### Mutators (Set)

```php
protected function password(): Attribute
{
    return Attribute::make(
        set: fn (string $value) => bcrypt($value),
    );
}

// Usage
$user->password = 'secret'; // Automatically hashed
```

### Both Get and Set

```php
protected function email(): Attribute
{
    return Attribute::make(
        get: fn (string $value) => strtolower($value),
        set: fn (string $value) => strtolower(trim($value)),
    );
}
```

## Model Methods

**Simple helper methods** are acceptable:

```php
class Order extends Model
{
    public function isPending(): bool
    {
        return $this->status === OrderStatus::Pending;
    }

    public function isCompleted(): bool
    {
        return $this->status === OrderStatus::Completed;
    }

    public function canBeCancelled(): bool
    {
        return $this->isPending() || $this->status === OrderStatus::Processing;
    }
}
```

**But NOT business logic:**

```php
// ❌ Bad - business logic in model
class Order extends Model
{
    public function cancel(): void
    {
        DB::transaction(function () {
            $this->update(['status' => OrderStatus::Cancelled]);
            $this->refundPayment();
            $this->notifyCustomer();
        });
    }
}

// ✅ Good - business logic in action
class CancelOrderAction
{
    public function __invoke(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $order->update(['status' => OrderStatus::Cancelled]);
            resolve(RefundPaymentAction::class)($order);
            resolve(NotifyCustomerAction::class)($order);

            return $order;
        });
    }
}
```

## Query Performance Patterns

```php
// ✅ Boolean existence check
$hasInvoices = Invoice::query()
    ->forTenant($tenantId)
    ->exists();

// ✅ Constrained eager loading
$properties = Property::query()
    ->forOrganization($organizationId)
    ->with([
        'meters' => fn ($query) => $query
            ->select(['id', 'organization_id', 'property_id', 'name'])
            ->active(),
    ])
    ->withCount('meters')
    ->get();
```

## Mass Assignment and Casts

- Prefer explicit `$fillable` over `$guarded`
- Cast enums, booleans, JSON, and temporal columns in `casts()`
- Use accessors for cheap derived values only; do not hide expensive queries behind accessors
- If a computed attribute is expensive or relation-backed, consider a presenter/query class instead

## When To Move Logic Out

- Actions/services: workflows, writes, transactions, side effects
- Query objects/builders: large branching filters or cross-model read models
- Observers: audit logs, cache invalidation, notifications
- Policies: authorization and visibility rules

## Index Guidance

Before adding a new summary/workspace scope, check whether an index is needed for:
- `organization_id` plus status/date combinations
- latest-first queries such as `(created_at, id)` or `(reading_date, id)`
- tenant/property lookup pairs like `(organization_id, property_id)`
- relation-driven filters used by tables, widgets, and dashboards

## Model Observers

**For model lifecycle hooks:**

```php
<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Order;
use Illuminate\Support\Str;

class OrderObserver
{
    public function creating(Order $order): void
    {
        if (! $order->uuid) {
            $order->uuid = Str::uuid();
        }
    }

    public function created(Order $order): void
    {
        // Dispatch event, queue job, etc.
    }

    public function updating(Order $order): void
    {
        // Before update
    }

    public function updated(Order $order): void
    {
        // After update
    }

    public function deleted(Order $order): void
    {
        // After delete
    }
}
```

**Register in AppServiceProvider:**

```php
use App\Models\Order;
use App\Observers\OrderObserver;

public function boot(): void
{
    Order::observe(OrderObserver::class);
}
```

## Model Concerns (Traits)

**Extract reusable behavior:**

**[View full implementation →](references/HasUuid.php)**

**Use in models:**

```php
class Order extends Model
{
    use HasUuid;
}
```

## Route Model Binding

### Implicit Binding

```php
// Route
Route::get('/orders/{order}', [OrderController::class, 'show']);

// Controller - automatically receives Order model
public function show(Order $order) { }
```

### Custom Key

```php
Route::get('/orders/{order:uuid}', [OrderController::class, 'show']);
```

### Custom Resolution

```php
public function resolveRouteBinding($value, $field = null)
{
    return $this->where($field ?? 'id', $value)
        ->where('is_active', true)
        ->firstOrFail();
}
```

## Mass Assignment Protection

Use explicit `$fillable` properties on models that are mass assigned from validated input or data objects.

```php
class Order extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'total',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'metadata' => 'array',
        ];
    }
}
```

Avoid `Model::unguard()` and avoid `protected $guarded = [];` in application code. If a model should never be mass assigned, omit mass-assignment entry points rather than relying on a broad guarded list.
- **Cleaner Models**: Less boilerplate code

**Important:** Always validate input in Form Requests before passing to Actions/Models.

## Timestamps

```php
// Disable timestamps
public $timestamps = false;

// Custom timestamp columns
const CREATED_AT = 'creation_date';
const UPDATED_AT = 'updated_date';
```

## Soft Deletes

```php
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;
}
```

**Usage:**

```php
$order->delete();      // Soft delete
$order->forceDelete(); // Permanent delete
$order->restore();     // Restore

Order::withTrashed()->find($id);
Order::onlyTrashed()->get();
```

## Collections

**Query results return Collections:**

```php
$orders = Order::all(); // Illuminate\Database\Eloquent\Collection

$orders->filter(fn($order) => $order->isPending());
$orders->map(fn($order) => $order->total);
$orders->sum('total');
```

## Model Organization

```
app/Models/
├── Order.php
├── User.php
├── Concerns/
│   ├── HasUuid.php
│   ├── BelongsToTenant.php
│   └── Searchable.php
└── Contracts/
    └── Searchable.php
```

## Testing Models

```php
it('can mass assign attributes', function () {
    $order = Order::create([
        'user_id' => 1,
        'status' => 'pending',
        'total' => 1000,
        'notes' => 'Test order',
    ]);

    expect($order->user_id)->toBe(1)
        ->and($order->total)->toBe(1000);
});

it('casts status to enum', function () {
    $order = Order::factory()->create(['status' => 'pending']);

    expect($order->status)->toBeInstanceOf(OrderStatus::class);
});

it('has user relationship', function () {
    $order = Order::factory()->create();

    expect($order->user)->toBeInstanceOf(User::class);
});
```

## Summary

**Models should:**
- Use explicit `$fillable` when mass assignment is allowed
- Define structure (casts, relationships, small scopes)
- Prefer local scopes first, then query builders/query objects when complexity justifies them
- Have simple helper methods
- Use observers for lifecycle hooks

**Models should NOT:**
- Contain business logic (use Actions)
- Have complex methods (use Actions)
- Rely on `Model::unguard()` or `protected $guarded = [];`
