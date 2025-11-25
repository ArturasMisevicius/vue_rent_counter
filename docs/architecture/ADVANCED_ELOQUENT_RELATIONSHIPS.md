# Advanced Eloquent Relationships Guide

## Overview

This guide covers advanced Eloquent relationship patterns used in the Vilnius Utilities Billing Platform, including polymorphic relationships, pivot models, self-referencing hierarchies, and complex querying strategies.

---

## Table of Contents

1. [Pivot Models with Additional Data](#1-pivot-models-with-additional-data)
2. [Polymorphic Relationships](#2-polymorphic-relationships)
3. [Self-Referencing Hierarchies](#3-self-referencing-hierarchies)
4. [Has-Many-Through Relationships](#4-has-many-through-relationships)
5. [Has-One-Through Relationships](#5-has-one-through-relationships)
6. [Morph-To-Many Relationships](#6-morph-to-many-relationships)
7. [Complex Querying Patterns](#7-complex-querying-patterns)
8. [Performance Optimization](#8-performance-optimization)
9. [Testing Relationships](#9-testing-relationships)

---

## 1. Pivot Models with Additional Data

### Property-Tenant Assignment with History

The `property_tenant` pivot table tracks tenant assignments over time with `assigned_at` and `vacated_at` timestamps.

#### Migration

```php
Schema::create('property_tenant', function (Blueprint $table) {
    $table->id();
    $table->foreignId('property_id')->constrained()->onDelete('cascade');
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
    $table->timestamp('assigned_at')->nullable();
    $table->timestamp('vacated_at')->nullable();
    $table->timestamps();
    
    $table->unique(['property_id', 'tenant_id']);
    $table->index('assigned_at');
});
```

#### Pivot Model (`app/Models/PropertyTenantPivot.php`)

```php
class PropertyTenantPivot extends Pivot
{
    protected $table = 'property_tenant';
    public $incrementing = true;

    protected $casts = [
        'assigned_at' => 'datetime',
        'vacated_at' => 'datetime',
    ];

    // Relationships
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // Accessors
    public function getIsCurrentAttribute(): bool
    {
        return $this->vacated_at === null;
    }

    public function getTenureDaysAttribute(): ?int
    {
        if (!$this->assigned_at) {
            return null;
        }
        $endDate = $this->vacated_at ?? now();
        return $this->assigned_at->diffInDays($endDate);
    }

    // Scopes
    public function scopeCurrent($query)
    {
        return $query->whereNull('vacated_at');
    }

    public function scopeActiveDuring($query, Carbon $start, Carbon $end)
    {
        return $query->where('assigned_at', '<=', $end)
            ->where(function ($q) use ($start) {
                $q->whereNull('vacated_at')
                  ->orWhere('vacated_at', '>=', $start);
            });
    }
}
```

#### Using the Pivot Model

```php
// In Property model
public function tenants(): BelongsToMany
{
    return $this->belongsToMany(Tenant::class, 'property_tenant')
        ->using(PropertyTenantPivot::class)
        ->withPivot(['assigned_at', 'vacated_at'])
        ->withTimestamps();
}

// In Tenant model
public function properties(): BelongsToMany
{
    return $this->belongsToMany(Property::class, 'property_tenant')
        ->using(PropertyTenantPivot::class)
        ->withPivot(['assigned_at', 'vacated_at'])
        ->withTimestamps();
}
```

#### Querying Examples

```php
// Get current tenants for a property
$currentTenants = $property->tenants()
    ->wherePivotNull('vacated_at')
    ->get();

// Get tenant assignment history
$history = $property->tenants()
    ->wherePivotNotNull('vacated_at')
    ->orderByPivot('assigned_at', 'desc')
    ->get();

// Get tenants active during a specific period
$activeTenants = $property->tenants()
    ->wherePivot('assigned_at', '<=', $endDate)
    ->where(function ($q) use ($startDate) {
        $q->wherePivotNull('vacated_at')
          ->orWherePivot('vacated_at', '>=', $startDate);
    })
    ->get();

// Access pivot data
foreach ($property->tenants as $tenant) {
    echo $tenant->pivot->assigned_at;
    echo $tenant->pivot->tenure_days; // Custom accessor
    echo $tenant->pivot->is_current;  // Custom accessor
}

// Create assignment with pivot data
$property->tenants()->attach($tenant->id, [
    'assigned_at' => now(),
    'vacated_at' => null,
]);

// Update pivot data
$property->tenants()->updateExistingPivot($tenant->id, [
    'vacated_at' => now(),
]);
```

---

## 2. Polymorphic Relationships

### Polymorphic Audit Trail

Track changes to any model (invoices, meters, properties, etc.) using a single audit log table.

#### Migration

```php
Schema::create('audit_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained('users')->onDelete('cascade');
    $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
    
    // Polymorphic relationship
    $table->morphs('auditable');
    
    $table->string('event'); // created, updated, deleted, restored
    $table->json('old_values')->nullable();
    $table->json('new_values')->nullable();
    $table->string('ip_address', 45)->nullable();
    $table->text('user_agent')->nullable();
    $table->text('notes')->nullable();
    
    $table->timestamps();
    
    // Indexes
    $table->index(['tenant_id', 'created_at']);
    $table->index(['auditable_type', 'auditable_id', 'created_at']);
});
```

#### AuditLog Model

```php
class AuditLog extends Model
{
    use BelongsToTenant;

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    // Polymorphic relationship
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeEvent($query, string $event)
    {
        return $query->where('event', $event);
    }

    public function scopeForModel($query, string $modelType)
    {
        return $query->where('auditable_type', $modelType);
    }
}
```

#### Auditable Trait

```php
trait Auditable
{
    protected static function bootAuditable(): void
    {
        static::created(fn($model) => $model->auditEvent('created', null, $model->getAttributes()));
        static::updated(fn($model) => $model->isDirty() && $model->auditEvent('updated', $model->getOriginal(), $model->getAttributes()));
        static::deleted(fn($model) => $model->auditEvent('deleted', $model->getOriginal(), null));
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable')
            ->orderBy('created_at', 'desc');
    }

    protected function auditEvent(string $event, ?array $oldValues, ?array $newValues): void
    {
        AuditLog::create([
            'tenant_id' => $this->tenant_id ?? session('tenant_id'),
            'user_id' => auth()->id(),
            'auditable_type' => get_class($this),
            'auditable_id' => $this->id,
            'event' => $event,
            'old_values' => $this->filterAuditableAttributes($oldValues),
            'new_values' => $this->filterAuditableAttributes($newValues),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
```

#### Using Auditable Models

```php
// Add trait to any model
class Invoice extends Model
{
    use Auditable;
    
    // Optionally exclude attributes from audit
    protected $auditExclude = ['updated_at'];
}

// Query audit logs
$invoice->auditLogs; // All audit logs
$invoice->auditLogsForEvent('updated'); // Only updates
$invoice->latestAudit(); // Most recent change

// Query across all models
AuditLog::forModel(Invoice::class)
    ->event('updated')
    ->where('tenant_id', $tenantId)
    ->with('auditable', 'user')
    ->get();

// Get all changes for a tenant
AuditLog::where('tenant_id', $tenantId)
    ->with('auditable')
    ->orderBy('created_at', 'desc')
    ->paginate(50);
```

---

## 3. Self-Referencing Hierarchies

### Hierarchical User Management

Users can have parent-child relationships (admin creates managers/tenants).

#### Migration

```php
Schema::table('users', function (Blueprint $table) {
    $table->foreignId('parent_user_id')
        ->nullable()
        ->constrained('users')
        ->onDelete('set null');
    
    $table->index('parent_user_id');
});
```

#### User Model

```php
class User extends Model
{
    // Parent relationship
    public function parentUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_user_id');
    }

    // Children relationship
    public function childUsers(): HasMany
    {
        return $this->hasMany(User::class, 'parent_user_id');
    }

    // Recursive: Get all descendants
    public function descendants(): HasMany
    {
        return $this->childUsers()->with('descendants');
    }

    // Get all ancestors
    public function ancestors()
    {
        $ancestors = collect();
        $parent = $this->parentUser;
        
        while ($parent) {
            $ancestors->push($parent);
            $parent = $parent->parentUser;
        }
        
        return $ancestors;
    }

    // Check if user is descendant of another
    public function isDescendantOf(User $user): bool
    {
        return $this->ancestors()->contains('id', $user->id);
    }

    // Get depth in hierarchy
    public function getDepth(): int
    {
        return $this->ancestors()->count();
    }

    // Scope: Root users (no parent)
    public function scopeRoots($query)
    {
        return $query->whereNull('parent_user_id');
    }

    // Scope: Leaf users (no children)
    public function scopeLeaves($query)
    {
        return $query->whereDoesntHave('childUsers');
    }
}
```

#### Querying Hierarchies

```php
// Get all root users (admins)
$admins = User::roots()->get();

// Get all children of an admin
$managersAndTenants = $admin->childUsers;

// Get all descendants recursively
$allDescendants = $admin->descendants;

// Get user's ancestors
$ancestors = $user->ancestors();

// Check hierarchy
if ($user->isDescendantOf($admin)) {
    // User is under this admin
}

// Get users at specific depth
$managers = User::whereHas('parentUser', function ($q) {
    $q->whereNull('parent_user_id');
})->get();

// Eager load hierarchy
$users = User::with(['parentUser', 'childUsers'])->get();

// Load full tree
$tree = User::with('descendants')->roots()->get();
```

#### Closure Table Pattern (for better performance)

For large hierarchies, use a closure table:

```php
Schema::create('user_hierarchy', function (Blueprint $table) {
    $table->foreignId('ancestor_id')->constrained('users')->onDelete('cascade');
    $table->foreignId('descendant_id')->constrained('users')->onDelete('cascade');
    $table->integer('depth');
    
    $table->primary(['ancestor_id', 'descendant_id']);
    $table->index('depth');
});

// Query with closure table
$descendants = DB::table('user_hierarchy')
    ->where('ancestor_id', $userId)
    ->where('depth', '>', 0)
    ->pluck('descendant_id');

$users = User::whereIn('id', $descendants)->get();
```

---

## 4. Has-Many-Through Relationships

### Tenant → Meter Readings (through Property → Meters)

```php
// In Tenant model
public function meterReadings(): HasManyThrough
{
    return $this->hasManyThrough(
        MeterReading::class,  // Final model
        Meter::class,         // Intermediate model
        'property_id',        // Foreign key on meters table
        'meter_id',           // Foreign key on meter_readings table
        'property_id',        // Local key on tenants table
        'id'                  // Local key on meters table
    );
}

// Advanced queries
public function meterReadingsForPeriod(Carbon $start, Carbon $end)
{
    return $this->meterReadings()
        ->whereBetween('reading_date', [$start, $end])
        ->with('meter')
        ->orderBy('reading_date', 'desc')
        ->get();
}

// Latest reading per meter (subquery)
public function latestMeterReadings()
{
    return $this->meterReadings()
        ->whereIn('meter_readings.id', function ($query) {
            $query->selectRaw('MAX(id)')
                ->from('meter_readings')
                ->join('meters', 'meter_readings.meter_id', '=', 'meters.id')
                ->where('meters.property_id', $this->property_id)
                ->groupBy('meter_id');
        })
        ->with('meter')
        ->get();
}
```

### Organization → Invoices (through Users)

```php
// In Organization model
public function invoices(): HasManyThrough
{
    return $this->hasManyThrough(
        Invoice::class,
        User::class,
        'organization_id', // Foreign key on users table
        'tenant_id',       // Foreign key on invoices table
        'id',              // Local key on organizations table
        'tenant_id'        // Local key on users table
    );
}

// Get total revenue
public function getTotalRevenue(): float
{
    return $this->invoices()
        ->where('status', InvoiceStatus::PAID)
        ->sum('total_amount');
}

// Get invoices for a period
public function invoicesForPeriod(Carbon $start, Carbon $end)
{
    return $this->invoices()
        ->whereBetween('billing_period_start', [$start, $end])
        ->with(['items', 'tenant'])
        ->get();
}
```

---

## 5. Has-One-Through Relationships

### Tenant → Building (through Property)

```php
// In Tenant model
public function building(): HasOneThrough
{
    return $this->hasOneThrough(
        Building::class,
        Property::class,
        'id',          // Foreign key on properties table
        'id',          // Foreign key on buildings table
        'property_id', // Local key on tenants table
        'building_id'  // Local key on properties table
    );
}

// Usage
$tenant->building; // Direct access to building
$tenant->building->gyvatukas_summer_average;
```

### Invoice → Property (through Tenant)

```php
// In Invoice model
public function property(): HasOneThrough
{
    return $this->hasOneThrough(
        Property::class,
        Tenant::class,
        'id',              // Foreign key on tenants table
        'id',              // Foreign key on properties table
        'tenant_renter_id', // Local key on invoices table
        'property_id'      // Local key on tenants table
    );
}

// Usage
$invoice->property; // Direct access without loading tenant
$invoice->property->address;
```

---

## 6. Morph-To-Many Relationships

### Polymorphic Tags System

```php
// Migration
Schema::create('tags', function (Blueprint $table) {
    $table->id();
    $table->string('name')->unique();
    $table->string('slug')->unique();
    $table->timestamps();
});

Schema::create('taggables', function (Blueprint $table) {
    $table->foreignId('tag_id')->constrained()->onDelete('cascade');
    $table->morphs('taggable');
    $table->timestamps();
    
    $table->unique(['tag_id', 'taggable_id', 'taggable_type']);
});

// Tag model
class Tag extends Model
{
    public function properties(): MorphToMany
    {
        return $this->morphedByMany(Property::class, 'taggable');
    }

    public function buildings(): MorphToMany
    {
        return $this->morphedByMany(Building::class, 'taggable');
    }

    public function invoices(): MorphToMany
    {
        return $this->morphedByMany(Invoice::class, 'taggable');
    }
}

// In Property/Building/Invoice models
trait Taggable
{
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function syncTags(array $tags): void
    {
        $tagIds = collect($tags)->map(function ($tag) {
            return Tag::firstOrCreate(['name' => $tag], [
                'slug' => Str::slug($tag)
            ])->id;
        });

        $this->tags()->sync($tagIds);
    }
}

// Usage
$property->tags()->attach($tag);
$property->syncTags(['residential', 'apartment', 'vilnius']);
$properties = Property::whereHas('tags', fn($q) => $q->where('name', 'residential'))->get();
```

---

## 7. Complex Querying Patterns

### Subquery Relationships

```php
// Add subquery as relationship
use Illuminate\Database\Eloquent\Builder;

// In Property model
public function latestMeterReading(): HasOne
{
    return $this->hasOne(MeterReading::class)
        ->ofMany([
            'reading_date' => 'max',
        ], function (Builder $query) {
            $query->whereHas('meter', function ($q) {
                $q->where('property_id', $this->id);
            });
        });
}

// Usage
$properties = Property::with('latestMeterReading')->get();
```

### Conditional Relationships

```php
// In Invoice model
public function paidItems(): HasMany
{
    return $this->hasMany(InvoiceItem::class)
        ->where('status', 'paid');
}

public function unpaidItems(): HasMany
{
    return $this->hasMany(InvoiceItem::class)
        ->where('status', 'unpaid');
}

// Dynamic relationship
public function itemsByStatus(string $status): HasMany
{
    return $this->hasMany(InvoiceItem::class)
        ->where('status', $status);
}
```

### Relationship Existence Queries

```php
// Properties with at least one meter
$properties = Property::has('meters')->get();

// Properties with more than 3 meters
$properties = Property::has('meters', '>', 3)->get();

// Properties with electricity meters
$properties = Property::whereHas('meters', function ($query) {
    $query->where('type', MeterType::ELECTRICITY);
})->get();

// Properties without any meters
$properties = Property::doesntHave('meters')->get();

// Properties without electricity meters
$properties = Property::whereDoesntHave('meters', function ($query) {
    $query->where('type', MeterType::ELECTRICITY);
})->get();

// Complex: Properties with unpaid invoices over €100
$properties = Property::whereHas('tenants.invoices', function ($query) {
    $query->where('status', InvoiceStatus::DRAFT)
        ->where('total_amount', '>', 100);
})->get();
```

### Counting Related Models

```php
// Eager load counts
$properties = Property::withCount([
    'meters',
    'meters as electricity_meters_count' => fn($q) => $q->where('type', MeterType::ELECTRICITY),
    'tenants',
])->get();

// Access counts
$property->meters_count;
$property->electricity_meters_count;

// Conditional counts
$properties = Property::withCount([
    'meters' => fn($q) => $q->where('installation_date', '>', now()->subYear()),
])->having('meters_count', '>', 0)->get();
```

### Aggregate Relationships

```php
// In Property model
public function totalConsumption(): float
{
    return $this->meters()
        ->join('meter_readings', 'meters.id', '=', 'meter_readings.meter_id')
        ->sum('meter_readings.value');
}

// Using withSum
$properties = Property::withSum('meters.readings', 'value')->get();
$property->meters_readings_sum_value;

// Multiple aggregates
$properties = Property::withSum('meters.readings', 'value')
    ->withAvg('meters.readings', 'value')
    ->withMax('meters.readings', 'reading_date')
    ->get();
```

---

## 8. Performance Optimization

### Eager Loading Strategies

```php
// Basic eager loading
$invoices = Invoice::with(['tenant', 'items'])->get();

// Nested eager loading
$properties = Property::with([
    'building',
    'meters.readings' => fn($q) => $q->latest()->limit(10),
    'tenants.invoices',
])->get();

// Conditional eager loading
$properties = Property::with([
    'meters' => fn($q) => $q->where('type', MeterType::ELECTRICITY),
    'meters.readings' => fn($q) => $q->whereBetween('reading_date', [$start, $end]),
])->get();

// Lazy eager loading (load after initial query)
$properties = Property::all();
$properties->load('meters.readings');

// Load specific columns
$properties = Property::with([
    'meters:id,property_id,type,serial_number',
    'building:id,name,address',
])->get();
```

### Avoiding N+1 Queries

```php
// ❌ BAD: N+1 queries
$properties = Property::all();
foreach ($properties as $property) {
    echo $property->building->name; // N queries
}

// ✅ GOOD: Eager loading
$properties = Property::with('building')->all();
foreach ($properties as $property) {
    echo $property->building->name; // 2 queries total
}

// Detect N+1 in tests
use Illuminate\Database\Eloquent\Model;

Model::preventLazyLoading(! app()->isProduction());
```

### Chunking Large Datasets

```php
// Process in chunks to avoid memory issues
Property::with('meters')
    ->chunk(100, function ($properties) {
        foreach ($properties as $property) {
            // Process property
        }
    });

// Lazy collections for memory efficiency
Property::with('meters')
    ->lazy()
    ->each(function ($property) {
        // Process property
    });
```

### Caching Relationships

```php
// In model
public function cachedMeters()
{
    return Cache::remember(
        "property.{$this->id}.meters",
        now()->addHours(24),
        fn() => $this->meters
    );
}

// Invalidate cache on changes
protected static function booted(): void
{
    static::saved(fn($property) => Cache::forget("property.{$property->id}.meters"));
}
```

---

## 9. Testing Relationships

### Factory Relationships

```php
// PropertyFactory
public function definition(): array
{
    return [
        'tenant_id' => User::factory(),
        'building_id' => Building::factory(),
        'address' => fake()->address(),
        'type' => PropertyType::APARTMENT,
        'area_sqm' => fake()->randomFloat(2, 30, 200),
    ];
}

// Create with relationships
$property = Property::factory()
    ->for(Building::factory())
    ->has(Meter::factory()->count(3))
    ->create();

// Create with specific relationships
$property = Property::factory()
    ->for($building)
    ->has(
        Meter::factory()
            ->electricity()
            ->has(MeterReading::factory()->count(10))
    )
    ->create();
```

### Testing Relationship Integrity

```php
test('property belongs to building', function () {
    $building = Building::factory()->create();
    $property = Property::factory()->for($building)->create();
    
    expect($property->building)->toBeInstanceOf(Building::class)
        ->and($property->building->id)->toBe($building->id);
});

test('tenant has many invoices', function () {
    $tenant = Tenant::factory()
        ->has(Invoice::factory()->count(5))
        ->create();
    
    expect($tenant->invoices)->toHaveCount(5)
        ->and($tenant->invoices->first())->toBeInstanceOf(Invoice::class);
});

test('property tenant pivot stores assignment dates', function () {
    $property = Property::factory()->create();
    $tenant = Tenant::factory()->create();
    
    $property->tenants()->attach($tenant->id, [
        'assigned_at' => now(),
        'vacated_at' => null,
    ]);
    
    $property->refresh();
    
    expect($property->tenants)->toHaveCount(1)
        ->and($property->tenants->first()->pivot->assigned_at)->not->toBeNull()
        ->and($property->tenants->first()->pivot->vacated_at)->toBeNull();
});
```

### Testing Cascade Deletes

```php
test('deleting property deletes meters', function () {
    $property = Property::factory()
        ->has(Meter::factory()->count(3))
        ->create();
    
    $meterIds = $property->meters->pluck('id');
    
    $property->delete();
    
    expect(Meter::whereIn('id', $meterIds)->count())->toBe(0);
});

test('deleting meter deletes readings', function () {
    $meter = Meter::factory()
        ->has(MeterReading::factory()->count(10))
        ->create();
    
    $readingIds = $meter->readings->pluck('id');
    
    $meter->delete();
    
    expect(MeterReading::whereIn('id', $readingIds)->count())->toBe(0);
});
```

---

## Best Practices

### 1. Always Define Inverse Relationships

```php
// Property → Meters
public function meters(): HasMany
{
    return $this->hasMany(Meter::class);
}

// Meter → Property (inverse)
public function property(): BelongsTo
{
    return $this->belongsTo(Property::class);
}
```

### 2. Use Morph Maps for Polymorphic Relationships

```php
// In AppServiceProvider
use Illuminate\Database\Eloquent\Relations\Relation;

Relation::enforceMorphMap([
    'property' => Property::class,
    'building' => Building::class,
    'invoice' => Invoice::class,
    'meter' => Meter::class,
]);
```

### 3. Scope Relationships to Tenant

```php
public function meters(): HasMany
{
    return $this->hasMany(Meter::class)
        ->where('tenant_id', session('tenant_id'));
}
```

### 4. Document Complex Relationships

```php
/**
 * Get meter readings for this tenant through their property's meters.
 * 
 * Traverses: Tenant → Property → Meters → MeterReadings
 * 
 * @return HasManyThrough
 */
public function meterReadings(): HasManyThrough
{
    return $this->hasManyThrough(/* ... */);
}
```

### 5. Use Relationship Methods for Queries

```php
// ✅ GOOD: Use relationship method
$property->meters()->where('type', MeterType::ELECTRICITY)->get();

// ❌ BAD: Query directly
Meter::where('property_id', $property->id)
    ->where('type', MeterType::ELECTRICITY)
    ->get();
```

---

## Related Documentation

- [COMPREHENSIVE_SCHEMA_ANALYSIS.md](./COMPREHENSIVE_SCHEMA_ANALYSIS.md) - Database schema details
- [ERD_VISUAL.md](../database/ERD_VISUAL.md) - Visual relationship diagrams
- [DATABASE_QUERY_OPTIMIZATION_GUIDE.md](../performance/DATABASE_QUERY_OPTIMIZATION_GUIDE.md) - Query optimization
- [MULTI_TENANT_ARCHITECTURE.md](./MULTI_TENANT_ARCHITECTURE.md) - Multi-tenancy patterns

---

**Last Updated**: 2025-11-26
**Version**: 1.0
**Status**: Complete ✅
