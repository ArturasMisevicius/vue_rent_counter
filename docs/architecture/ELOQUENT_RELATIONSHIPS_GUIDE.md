# Eloquent Relationships Guide (Multi-Tenant)

**Hooks covered:** `eloquent-relationships-guide`, `n-plus-one-analyzer`, `database-query-optimization`.  
**Stack:** Laravel 12, Filament v4, single-DB multi-tenant with `tenant_id`.

## Core Practices
- **Tenant scoping:** All tenant-owned relations must include `tenant_id` on both sides. Avoid cross-tenant joins; always filter by the current tenant context or rely on `BelongsToTenant`.
- **Ownership vs. history:** Use pivot tables for historical assignment (`property_tenant`), not foreign keys on the child when history is needed. Include `assigned_at`/`vacated_at` and indexes.
- **Inverse relations:** Declare both sides (`belongsTo`, `hasMany`, `belongsToMany`) and ensure foreign keys match DB constraints.
- **Eager loading:** Default to `with()`/`withCount()` in queries used by Filament resources/controllers to avoid N+1; prefer `select` projections when appropriate.
- **Counts & aggregates:** Use `withCount()` or subselects instead of loading collections; index columns used in counts.

## Pivot & History Patterns
- `property_tenant` pivot: ensure unique `(property_id, tenant_id)`, plus indexes on `tenant_id`, `property_id`, `assigned_at`, `vacated_at`.
- When attaching/detaching, update `vacated_at` instead of deleting to preserve history.
- Seed/history example: `TenantHistorySeeder` populates tenants and pivot rows with chronological dates.

## Relationship Examples
```php
// Property has many Tenants (current + historical)
public function tenants()
{
    return $this->hasMany(Tenant::class);
}

// Meter belongs to Property (tenant_id aligned)
public function property(): BelongsTo
{
    return $this->belongsTo(Property::class);
}

// Tenant meter readings through meters
public function meterReadings(): HasManyThrough
{
    return $this->hasManyThrough(
        MeterReading::class,
        Meter::class,
        'property_id',
        'meter_id',
        'property_id',
        'id'
    );
}
```

## Testing Relationships
- Use factories with `forTenantId`/`forProperty` helpers to keep `tenant_id` aligned.
- Add Pest tests for critical relations (e.g., pivot history, scoped eager loads).
- Performance tests: assert query counts in Filament pages where N+1 risks exist.

## When to Update
- Adding new pivots/history tables.
- Changing ownership models (e.g., moving from FK to pivot).
- Adding eager-loading defaults or query scopes that affect relations.
