# Entity-Relationship Diagram (Visual)

## Core Billing Flow

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         MULTI-TENANT ARCHITECTURE                        │
│                                                                          │
│  ┌──────────────┐                                                       │
│  │organizations │ (Superadmin manages multiple organizations)           │
│  │──────────────│                                                       │
│  │ id           │                                                       │
│  │ name         │                                                       │
│  │ slug         │                                                       │
│  │ is_active    │                                                       │
│  └──────┬───────┘                                                       │
│         │                                                               │
│         │ 1:N                                                           │
│         ▼                                                               │
│  ┌──────────────┐         ┌──────────────┐                            │
│  │    users     │────────►│subscriptions │                            │
│  │──────────────│  1:1    │──────────────│                            │
│  │ id           │         │ id           │                            │
│  │ tenant_id ◄──┼─────────┤ user_id      │                            │
│  │ role (enum)  │         │ plan_type    │                            │
│  │ property_id  │         │ status       │                            │
│  │ parent_id    │         │ expires_at   │                            │
│  └──────┬───────┘         └──────────────┘                            │
│         │                                                               │
│         │ tenant_id (multi-tenancy key)                                │
│         │                                                               │
└─────────┼───────────────────────────────────────────────────────────────┘
          │
          │ Scopes all queries: WHERE tenant_id = session('tenant_id')
          │
          ├──────────────────────────────────────────────────────────────┐
          │                                                              │
          ▼                                                              ▼
   ┌──────────────┐                                             ┌──────────────┐
   │  buildings   │                                             │  properties  │
   │──────────────│                                             │──────────────│
   │ id           │                                             │ id           │
   │ tenant_id    │                                             │ tenant_id    │
   │ address      │                                             │ address      │
   │ hot water circulation_   │                                             │ type (enum)  │
   │   summer_avg │                                             │ area_sqm     │
   └──────┬───────┘                                             │ building_id  │
          │                                                     └──────┬───────┘
          │ 1:N                                                        │
          │                                                            │ 1:N
          ▼                                                            │
   ┌──────────────┐                                                   │
   │  properties  │◄──────────────────────────────────────────────────┘
   │──────────────│
   │ building_id  │
   └──────┬───────┘
          │
          │ 1:N
          ▼
   ┌──────────────┐         ┌──────────────────┐
   │    meters    │────────►│ meter_readings   │
   │──────────────│  1:N    │──────────────────│
   │ id           │         │ id               │
   │ tenant_id    │         │ tenant_id        │
   │ property_id  │         │ meter_id         │
   │ type (enum)  │         │ reading_date     │
   │ serial_no    │         │ value            │
   │ supports_    │         │ zone             │
   │   zones      │         │ entered_by       │
   └──────────────┘         └────────┬─────────┘
                                     │
                                     │ 1:N
                                     ▼
                            ┌──────────────────────┐
                            │meter_reading_audits  │
                            │──────────────────────│
                            │ id                   │
                            │ meter_reading_id     │
                            │ changed_by_user_id   │
                            │ old_value            │
                            │ new_value            │
                            │ change_reason        │
                            └──────────────────────┘
```

## Tenant & Invoice Flow

```
   ┌──────────────┐         ┌──────────────────┐         ┌──────────────┐
   │  properties  │◄────────┤ property_tenant  │────────►│   tenants    │
   │──────────────│  M:N    │──────────────────│  M:N    │──────────────│
   │ id           │         │ property_id      │         │ id           │
   │ tenant_id    │         │ tenant_id        │         │ tenant_id    │
   │ address      │         │ assigned_at      │         │ name         │
   │ type         │         │ vacated_at       │         │ email        │
   └──────────────┘         └──────────────────┘         │ property_id  │
                                                          └──────┬───────┘
                                                                 │
                                                                 │ 1:N
                                                                 ▼
                                                          ┌──────────────┐
                                                          │   invoices   │
                                                          │──────────────│
                                                          │ id           │
                                                          │ tenant_id    │
                                                          │ tenant_      │
                                                          │   renter_id  │
                                                          │ period_start │
                                                          │ period_end   │
                                                          │ total_amount │
                                                          │ status (enum)│
                                                          │ finalized_at │
                                                          └──────┬───────┘
                                                                 │
                                                                 │ 1:N
                                                                 ▼
                                                          ┌──────────────┐
                                                          │invoice_items │
                                                          │──────────────│
                                                          │ id           │
                                                          │ invoice_id   │
                                                          │ description  │
                                                          │ quantity     │
                                                          │ unit_price   │
                                                          │ total        │
                                                          │ meter_       │
                                                          │   reading_   │
                                                          │   snapshot   │
                                                          └──────────────┘
```

## Tariff & Provider System

```
   ┌──────────────┐         ┌──────────────┐
   │  providers   │────────►│   tariffs    │
   │──────────────│  1:N    │──────────────│
   │ id           │         │ id           │
   │ name         │         │ provider_id  │
   │ service_type │         │ name         │
   │   (enum)     │         │ configuration│
   │ contact_info │         │   (JSON)     │
   └──────────────┘         │ active_from  │
                            │ active_until │
                            └──────────────┘
                                   │
                                   │ Referenced in
                                   │ invoice_items.
                                   │ meter_reading_
                                   │ snapshot
                                   ▼
                            ┌──────────────┐
                            │invoice_items │
                            │──────────────│
                            │ meter_       │
                            │   reading_   │
                            │   snapshot:  │
                            │   {          │
                            │     tariff_id│
                            │     tariff_  │
                            │       config │
                            │   }          │
                            └──────────────┘
```

## Audit Trail System

```
   ┌──────────────────────────────────────────────────────────────┐
   │                      AUDIT TRAIL SYSTEM                       │
   └──────────────────────────────────────────────────────────────┘

   ┌──────────────────────┐
   │meter_reading_audits  │  Tracks meter reading corrections
   │──────────────────────│
   │ meter_reading_id     │
   │ changed_by_user_id   │
   │ old_value            │
   │ new_value            │
   │ change_reason        │
   └──────────────────────┘

   ┌──────────────────────────┐
   │hot water circulation_calculation_    │  Tracks hot water circulation calculations
   │         audits           │
   │──────────────────────────│
   │ building_id              │
   │ tenant_id                │
   │ calculated_by_user_id    │
   │ billing_month            │
   │ season                   │
   │ circulation_energy       │
   │ distribution_result (JSON)│
   └──────────────────────────┘

   ┌──────────────────────────┐
   │invoice_generation_       │  Tracks invoice generation performance
   │         audits           │
   │──────────────────────────│
   │ invoice_id               │
   │ tenant_id                │
   │ user_id                  │
   │ execution_time_ms        │
   │ query_count              │
   │ metadata (JSON)          │
   └──────────────────────────┘

   ┌──────────────────────────┐
   │organization_activity_log │  Tracks organization actions
   │──────────────────────────│
   │ organization_id          │
   │ user_id                  │
   │ action                   │
   │ resource_type            │
   │ resource_id              │
   │ metadata (JSON)          │
   └──────────────────────────┘
```

## Hierarchical User Structure

```
   ┌──────────────┐
   │ superadmin   │  (tenant_id = NULL)
   └──────┬───────┘
          │
          │ manages
          ▼
   ┌──────────────┐
   │organizations │
   └──────┬───────┘
          │
          │ contains
          ▼
   ┌──────────────┐
   │    admin     │  (tenant_id = 1, parent_user_id = NULL)
   └──────┬───────┘
          │
          │ creates (parent_user_id)
          ├────────────────┬────────────────┐
          ▼                ▼                ▼
   ┌──────────┐     ┌──────────┐    ┌──────────┐
   │ manager  │     │ manager  │    │  tenant  │
   │ (child)  │     │ (child)  │    │ (child)  │
   └──────────┘     └──────────┘    └──────────┘
```

## Data Flow: Invoice Generation

```
1. Manager initiates invoice generation
   │
   ▼
2. BillingService.generateInvoice()
   │
   ├─► Load tenant with property, building, meters
   │   (Eager loading: 2-3 queries)
   │
   ├─► Load meter readings for period (±7 day buffer)
   │   (Already loaded via eager loading: 0 queries)
   │
   ├─► For each meter:
   │   ├─► Get provider (cached: 0 queries after first)
   │   ├─► Resolve tariff (cached: 0 queries after first)
   │   ├─► Find start/end readings (collection lookup: 0 queries)
   │   ├─► Calculate consumption
   │   └─► Create invoice item with snapshot
   │
   ├─► Calculate hot water circulation (if applicable)
   │   └─► hot water circulationCalculator.calculate()
   │
   ├─► Create invoice (status: draft)
   │
   └─► Create invoice items
       │
       └─► Total: 10-15 queries (constant, regardless of meter count)

3. Invoice created with status = 'draft'
   │
   ▼
4. Manager reviews and finalizes
   │
   ▼
5. BillingService.finalizeInvoice()
   │
   ├─► Set status = 'finalized'
   ├─► Set finalized_at = now()
   └─► Invoice becomes immutable
```

## Index Strategy Visualization

```
┌─────────────────────────────────────────────────────────────────┐
│                    COMPOSITE INDEX STRATEGY                      │
└─────────────────────────────────────────────────────────────────┘

meter_readings_meter_date_zone_index (meter_id, reading_date, zone)
├─► Covers: WHERE meter_id = ?
├─► Covers: WHERE meter_id = ? AND reading_date = ?
└─► Covers: WHERE meter_id = ? AND reading_date = ? AND zone = ?

idx_invoices_tenant_period (tenant_id, billing_period_start, status)
├─► Covers: WHERE tenant_id = ?
├─► Covers: WHERE tenant_id = ? AND billing_period_start >= ?
└─► Covers: WHERE tenant_id = ? AND billing_period_start >= ? AND status = ?

meters_property_type_index (property_id, type)
├─► Covers: WHERE property_id = ?
└─► Covers: WHERE property_id = ? AND type = ?

┌─────────────────────────────────────────────────────────────────┐
│                    COVERING INDEX STRATEGY                       │
└─────────────────────────────────────────────────────────────────┘

meter_readings_value_lookup (meter_id, reading_date, value)
└─► SELECT value FROM meter_readings WHERE meter_id = ? AND reading_date = ?
    (No table access needed - all data in index)
```

## Cascade Rules Visualization

```
┌─────────────────────────────────────────────────────────────────┐
│                      CASCADE BEHAVIORS                           │
└─────────────────────────────────────────────────────────────────┘

CASCADE (Delete children with parent):
  providers ──DELETE──► tariffs
  meters ──DELETE──► meter_readings
  meter_readings ──DELETE──► meter_reading_audits
  invoices ──DELETE──► invoice_items
  properties ──DELETE──► meters
  organizations ──DELETE──► organization_activity_log

SET NULL (Preserve children, nullify FK):
  buildings ──DELETE──► properties.building_id = NULL
  properties ──DELETE──► tenants.property_id = NULL
  users ──DELETE──► meter_readings.entered_by = NULL

RESTRICT (Prevent deletion if children exist):
  tenants ──DELETE──X invoices (Cannot delete tenant with invoices)
```

## Multi-Tenancy Scope Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                    TENANT SCOPE ENFORCEMENT                      │
└─────────────────────────────────────────────────────────────────┘

1. User logs in
   │
   ▼
2. Session stores tenant_id
   │
   ▼
3. TenantScope applied to all queries
   │
   ├─► Building::all()
   │   └─► SELECT * FROM buildings WHERE tenant_id = session('tenant_id')
   │
   ├─► Property::all()
   │   └─► SELECT * FROM properties WHERE tenant_id = session('tenant_id')
   │
   ├─► Meter::all()
   │   └─► SELECT * FROM meters WHERE tenant_id = session('tenant_id')
   │
   └─► Invoice::all()
       └─► SELECT * FROM invoices WHERE tenant_id = session('tenant_id')

4. Superadmin can bypass scope
   │
   └─► Building::withoutGlobalScope(TenantScope::class)->all()
       └─► SELECT * FROM buildings (no tenant_id filter)
```

---

## Legend

```
┌──────────┐
│  Table   │  = Database table
└──────────┘

──────►     = One-to-Many relationship (1:N)
◄─────►     = Many-to-Many relationship (M:N)
──────X     = Restricted (cannot delete)
──DELETE──► = Cascade delete
(enum)      = Enum-backed column
(JSON)      = JSON column
```
