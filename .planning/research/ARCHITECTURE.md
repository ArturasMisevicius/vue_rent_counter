# Architecture Research

**Domain:** Brownfield Laravel multi-tenant utility billing and property-management SaaS
**Researched:** 2026-03-19
**Confidence:** MEDIUM

## Standard Architecture

### System Overview

The safest target for Tenanto is a **boundary-first modular monolith**: one Laravel application, one primary relational data model, one shared deployment/runtime, but with sharply separated internal modules and security contexts. This is the right fit for a brownfield billing system because billing, metering, property assignments, reporting, and tenant access all currently share the same data graph and would become more dangerous, not less, if they were split into services before invariants are stabilized.

The architectural goal for the standardization milestone is not "clean folder names." It is to make **every read and write pass through a predictable chain**:

`Route / Panel / Livewire -> Middleware -> Workspace context -> Policy -> Query or Action -> Eloquent -> Presenter / View`

That gives Tenanto three things it currently lacks consistently: a single tenant/workspace boundary, a single write pipeline, and a single source of truth for UI composition decisions such as navigation and role visibility.

```text
┌──────────────────────────────────────────────────────────────────────┐
│ Delivery Boundaries                                                 │
├──────────────────────────────────────────────────────────────────────┤
│ Public/Auth │ Control Plane │ Organization Workspace │ Tenant Portal │
│ Blade/LW    │ Superadmin    │ Admin / Manager        │ Tenant        │
└─────────────┬───────────────┬────────────────────────┬───────────────┘
              │               │                        │
┌─────────────┴────────────────────────────────────────────────────────┐
│ Application Boundary Layer                                          │
├──────────────────────────────────────────────────────────────────────┤
│ WorkspaceContext │ Policies │ Form Requests / DTOs                  │
│ Query Builders / Read Models │ Actions │ Domain Events / Jobs       │
└─────────────┬────────────────────────────────────────────────────────┘
              │
┌─────────────┴────────────────────────────────────────────────────────┐
│ Domain Modules (Eloquent-first modular monolith)                    │
├──────────────────────────────────────────────────────────────────────┤
│ Platform │ Identity & Access │ Organizations │ Properties           │
│ Metering │ Billing │ Reporting │ Notifications / Integrations       │
└─────────────┬────────────────────────────────────────────────────────┘
              │
┌─────────────┴────────────────────────────────────────────────────────┐
│ Persistence & Infrastructure                                        │
├──────────────────────────────────────────────────────────────────────┤
│ Relational DB │ Queue │ Cache │ Mail │ Storage / PDF │ Audit / Logs │
└──────────────────────────────────────────────────────────────────────┘
```

### Component Responsibilities

| Component | Responsibility | Typical Implementation |
|-----------|----------------|------------------------|
| Workspace context | Resolve actor, organization, tenant assignment, subscription state, and current security scope for every request/job | `app/Filament/Support/Workspace/*`, middleware, panel tenant middleware, auth helpers |
| Control plane | Superadmin-only platform administration across organizations | Filament pages/resources with explicit platform queries and policies |
| Organization workspace | Admin/manager CRUD and operational workflows for buildings, properties, tenants, meters, invoices | Filament resources/pages backed by org-scoped query builders and actions |
| Tenant portal | Tenant self-service for readings, invoices, profile, and property details | Filament page wrappers + Livewire components using tenant-scoped read models and actions |
| Billing engine | Candidate selection, tariff resolution, distribution, invoice assembly, finalization, payment application | Focused services/actions under `app/Services/Billing/*` plus transactional actions |
| Reporting and projections | Read-heavy aggregates, exports, dashboards, and cached summaries | Dedicated report builders, query objects, cached projections, scheduled refresh jobs |
| Identity and authorization | Role rules, policy checks, impersonation, and one source of truth for elevated access | Policies, middleware, user role helpers, migration to a single superadmin authority field |
| Shared UI composition | Navigation, shell state, search, panel middleware, and page registration | Extracted registries/builders under `app/Filament/Support/*`, not ad hoc provider logic |

## Recommended Project Structure

This should be treated as the **target shape for touched code**, not a big-bang rename of the whole tree.

```text
app/
├── Filament/
│   ├── Resources/
│   │   ├── Platform/          # Superadmin-only CRUD/read surfaces
│   │   ├── Organization/      # Buildings, properties, tenants, meters
│   │   ├── Billing/           # Invoices, tariffs, providers, payments
│   │   └── Reporting/         # Read-only report resources/pages
│   ├── Pages/
│   │   ├── Platform/          # Settings, health, translation, platform dashboards
│   │   ├── Organization/      # Workspace dashboards and operations
│   │   └── Tenant/            # Tenant-facing shells that mount Livewire
│   ├── Actions/
│   │   ├── Platform/          # Platform mutations
│   │   ├── Organization/      # Org-scoped mutations
│   │   ├── Billing/           # Invoice generation/finalization/payment
│   │   └── Tenant/            # Tenant write actions
│   └── Support/
│       ├── Workspace/         # Actor/org/tenant/subscription context
│       ├── Navigation/        # One canonical navigation registry
│       ├── Reports/           # Read models, export builders, projections
│       └── Security/          # Shared panel security helpers
├── Http/
│   ├── Controllers/           # Thin redirect/download/webhook boundaries only
│   ├── Middleware/
│   │   ├── Security/          # CSP, headers, throttles
│   │   └── Workspace/         # Access, onboarding, tenant context, subscription
│   └── Requests/
│       ├── Shared/            # Cross-role payload validation
│       ├── Superadmin/
│       ├── Admin/
│       └── Tenant/
├── Livewire/
│   ├── PublicSite/            # Public and marketing pages
│   ├── Auth/                  # Login/register/reset/onboarding
│   ├── Shell/                 # Search, sidebar, topbar, account widgets
│   └── Tenant/                # Tenant-interaction components only
├── Models/                    # Eloquent entities, relations, casts, scoped builders
├── Policies/                  # Resource and record authorization
└── Services/
    ├── Billing/
    │   ├── Candidates/        # Who should be billed
    │   ├── Calculators/       # Pricing/distribution math
    │   ├── Finalization/      # Draft -> finalized invoice workflow
    │   └── Payments/          # Payment application logic
    ├── Security/              # CSP and monitoring infrastructure
    └── Integrations/          # Mail, export, provider adapters
```

### Structure Rationale

- **Keep Laravel delivery layers, but make them thin:** Filament resources/pages, Livewire components, and controllers stay where Laravel expects them, but they stop owning billing rules, tenant filtering, or navigation truth.
- **Centralize workspace resolution first:** the current `OrganizationContext` is a useful seed, but it needs to grow into a broader `WorkspaceContext` that can consistently answer "who is acting, for which org, against which tenant-visible records, under which subscription state?"
- **Decompose billing in place, not by rewrite:** keep billing in `app/Services/Billing/*`, but split the current mega-orchestrators into candidate selection, calculation, finalization, and payment collaborators.
- **Treat reporting as its own read module:** reports are not just another Livewire page. They need dedicated read models, query-side pagination, and cache/precompute hooks.
- **Do not split the application into services during this milestone:** Tenanto's risk is inconsistent boundaries, not lack of deploy separation.

## Architectural Patterns

### Pattern 1: Boundary-First Modular Monolith

**What:** One Laravel app with domain modules separated by namespace and responsibility, plus hard security/workspace boundaries enforced at the application layer.
**When to use:** Brownfield SaaS cleanup where domains are tightly coupled and a rewrite would be high-risk.
**Trade-offs:** Lower migration risk and easier transactions, but less independent deployability. That trade-off is correct for Milestone 1.

**Example:**
```php
final class WorkspaceContext
{
    public function actor(): User {}

    public function organizationId(): ?int {}

    public function tenantUserId(): ?int {}

    public function isPlatform(): bool {}
}
```

### Pattern 2: Query Builders and Read Models at the Boundary

**What:** Every screen and export starts from a boundary-aware query builder or read model, never from ad hoc `parent::getEloquentQuery()` plus hand-added filters.
**When to use:** Any Filament resource, Livewire list, report builder, or tenant-facing read path.
**Trade-offs:** More up-front structure, but far fewer tenant leaks and duplicated filters.

**Example:**
```php
final class InvoiceWorkspaceQuery
{
    public function forActor(WorkspaceContext $workspace): Builder
    {
        return Invoice::query()
            ->select(['id', 'organization_id', 'tenant_user_id', 'status', 'due_date', 'total'])
            ->when(
                $workspace->tenantUserId(),
                fn (Builder $query, int $tenantId) => $query->forTenantWorkspace(
                    $workspace->organizationId(),
                    $tenantId,
                ),
                fn (Builder $query) => $query->forAdminWorkspace($workspace->organizationId()),
            );
    }
}
```

### Pattern 3: Action Pipeline for Writes

**What:** Every mutation follows `Request/DTO -> Policy -> Action -> Transaction -> Event/Job`.
**When to use:** Invoice generation, meter-reading submission, tenant assignment changes, organization creation, notification dispatch.
**Trade-offs:** More files and indirection, but safer writes and better tests.

**Example:**
```php
public function save(StoreMeterReadingRequest $request, SubmitTenantReadingAction $action): void
{
    $payload = $request->validated();

    $this->authorize('submitReading', [MeterReading::class, $payload['property_id']]);

    $action->handle($this->workspaceContext, $payload);
}
```

### Pattern 4: Projection-Based Reporting Inside the Monolith

**What:** Heavy reports use dedicated builders or cached projections instead of loading whole collections into Livewire and paginating in memory.
**When to use:** Outstanding balances, revenue, consumption, compliance dashboards, and export surfaces.
**Trade-offs:** More background/cache plumbing, but the alternative is guaranteed pain as org size grows.

## Data Flow

### Request Flow

All user-facing flows should move in one direction only:

```text
[Browser / Filament / Livewire Action]
    ↓
[Route Group + Middleware Chain]
    ↓
[Panel Page | Livewire Component | Thin Controller]
    ↓
[WorkspaceContext + Policy + Form Request / DTO]
    ↓
[Action or Query Builder]
    ↓
[Eloquent Models / Services / Transaction]
    ↓
[DB + Cache + Queue]
    ↓
[Presenter / Resource / ViewModel]
    ↓
[Blade / Filament Response]
```

### State Management

Tenanto is server-rendered. The durable state is the database; transient state lives in session, cache, and Livewire component properties.

```text
[Relational DB + Cache]
    ↓
[Query Builders / Projections]
    ↓
[Filament Tables / Livewire Properties]
    ↓
[User Action]
    ↓
[Validated Payload + Policy + Action]
    ↓
[Transaction + Event/Job + Cache Bust]
    ↓
[Relational DB + Cache]
```

### Key Data Flows

1. **Tenant read flow:** authenticated tenant route -> tenant/workspace middleware -> tenant query builder -> presenter -> Livewire/Filament page. No Blade query and no record lookup outside tenant-scoped builders.
2. **Admin mutation flow:** Filament action -> admin request/DTO -> policy -> org-scoped action -> transaction -> audit/event -> optional queued notification.
3. **Billing cycle flow:** manual or scheduled trigger -> candidate selection -> tariff/distribution calculators -> invoice assembly/finalization -> persistence -> reminder/export jobs.
4. **Report flow:** filter payload -> report query/projection -> database-side aggregation/pagination -> cached summary -> page/export response.

## Suggested Build Order

This sequencing is an **inference from the current repository risks**, not a framework rule. It is the safest order because it reduces blast radius before changing high-value billing flows.

### Sequence

1. **Freeze invariants before refactoring**
   - Add characterization tests for tenant isolation, invoice visibility, meter-reading permissions, overdue calculations, and role boundaries.
   - Add architecture tests for allowed placement of actions, requests, support classes, and navigation sources.
   - Add query-budget checks for the most sensitive dashboard/report/billing screens.

2. **Centralize security and workspace context**
   - Replace scattered org/tenant resolution with a single `WorkspaceContext`.
   - Collapse superadmin authority onto one source of truth.
   - Normalize middleware stacks for public, platform, organization, and tenant surfaces.
   - Add throttling/retention around unauthenticated security intake like CSP reports.

3. **Standardize read paths**
   - Move Filament resource queries and tenant pages onto boundary-aware query builders/read models.
   - Pull report aggregation out of Livewire-memory pipelines.
   - Consolidate navigation to one canonical registry while keeping route names stable.

4. **Standardize write paths**
   - Route all mutations through action classes backed by Form Requests or DTO-like validated payloads.
   - Introduce explicit transactions, audit hooks, and event/job boundaries.
   - Queue notifications and slow side effects instead of doing them inline.

5. **Decompose billing last among the core domain refactors**
   - Split `BillingService` and neighboring invoice services into candidate selection, pricing, distribution, draft/finalize, and payment units.
   - Only do this after workspace boundaries and characterization tests exist, because billing is the highest-coupling domain.

6. **Then simplify panel composition**
   - Extract `AppPanelProvider` navigation/middleware composition into registries and support classes.
   - Keep a single panel during the cleanup milestone unless there is a proven security reason to split.
   - Revisit multi-panel separation only after tenant/org/platform boundaries are explicit and tested.

7. **Finish with operational hardening**
   - Replace config-only health checks with real connectivity checks.
   - Add production-like queue/cache integration tests.
   - Introduce projection refresh jobs and cache invalidation rules for reporting/dashboard surfaces.

### Sequencing Implications

- **Do not start with folder moves.** Moving namespaces before invariants are tested makes the milestone noisy and unsafe.
- **Do not split into microservices or separate apps.** The current risk is inconsistent boundaries inside one app, not deployment topology.
- **Do not refactor billing first.** Billing currently has the highest business blast radius and depends on access/context correctness.
- **Do not split Filament panels first.** The app still routes tenant URLs into the current panel. Runtime separation should follow, not precede, boundary consolidation.

## Scaling Considerations

| Scale | Architecture Adjustments |
|-------|--------------------------|
| 0-1k users | Single modular monolith is fine. Focus on context consistency, query discipline, and removing inline slow work from requests. |
| 1k-100k users | Queue notifications/exports, precompute heavy report data, harden cache invalidation, and benchmark billing windows with larger fixtures. |
| 100k+ users | Add read replicas or reporting projections, isolate heavy billing/report processing onto workers, and only then consider splitting runtime surfaces or services. |

### Scaling Priorities

1. **First bottleneck:** billing preview/finalization plus report builders that currently materialize too much data in PHP. Fix query shape and projections before anything else.
2. **Second bottleneck:** inline notifications and weak operational checks. Move side effects to jobs and make health/queue visibility real before traffic grows.

## Anti-Patterns

### Anti-Pattern 1: Filament as the Domain Layer

**What people do:** Put business rules, billing math, tenant filtering, and navigation policy straight into resources, pages, and provider classes.
**Why it's wrong:** UI classes become the only place behavior exists, which makes reuse, testing, and tenant safety fragile.
**Do this instead:** Keep Filament/Livewire as delivery surfaces only. Use actions, query builders, workspace context, and policies underneath them.

### Anti-Pattern 2: Manual Tenant Scoping Everywhere

**What people do:** Rebuild tenant/org filters inside every resource, page, relation manager, and component.
**Why it's wrong:** One forgotten `find()` or unscoped relation becomes a data leak.
**Do this instead:** Start every read from one boundary-aware query builder or reusable workspace scope.

### Anti-Pattern 3: Big-Bang Modernization

**What people do:** Rename folders, split panels, rewrite services, and change routes all in one pass.
**Why it's wrong:** The milestone becomes impossible to verify, and billing or tenant regressions hide inside structural noise.
**Do this instead:** Lock invariants first, then refactor one boundary at a time in the sequence above.

## Integration Points

### External Services

| Service | Integration Pattern | Notes |
|---------|---------------------|-------|
| Mail delivery | Queue-backed notification/job dispatch | Move invitation and invoice reminder delivery off interactive requests |
| Queue backend | Explicit jobs carrying actor/workspace IDs | Required for notifications, exports, report refresh, and eventually billing-heavy work |
| Storage / PDF generation | Service adapter invoked from action/job layer | Keep PDF/export generation out of Filament pages and Livewire components |

### Internal Boundaries

| Boundary | Communication | Notes |
|----------|---------------|-------|
| Delivery surfaces -> application layer | Direct method calls with validated payloads | Controllers, Livewire, and Filament must not bypass policies/actions |
| Workspace context -> query builders | Context object or mandatory scoped builder entrypoint | This is the core tenant-safety boundary |
| Actions -> services | Direct orchestration inside transactions | Billing and payments should remain synchronous until durable job boundaries are added intentionally |
| Actions -> events/jobs | Domain event or explicit job dispatch | Use for notifications, exports, cache refresh, and other slow side effects |
| Reporting -> billing/properties | Read-model queries only | Reporting should consume stable read shapes, not poke through UI-layer objects |

## Sources

- Internal repo context: `.planning/PROJECT.md`, `.planning/codebase/ARCHITECTURE.md`, `.planning/codebase/STRUCTURE.md`, `.planning/codebase/CONCERNS.md` - HIGH confidence
- Current hotspot files: `app/Providers/Filament/AppPanelProvider.php`, `app/Services/Billing/BillingService.php`, `app/Filament/Support/Admin/OrganizationContext.php`, `app/Http/Controllers/TenantPortalRouteController.php`, `app/Filament/Resources/Invoices/InvoiceResource.php`, `config/tenanto.php` - HIGH confidence
- Laravel 12 docs: routing, middleware, and container guidance - https://laravel.com/docs/12.x/routing , https://laravel.com/docs/12.x/middleware , https://laravel.com/docs/12.x/container - HIGH confidence
- Filament 5 docs: panel providers and tenancy - https://filamentphp.com/docs/5.x/plugins/panel-plugins , https://filamentphp.com/docs/5.x/users/tenancy - HIGH confidence
- Livewire 4 docs: security, actions, and redirecting - https://livewire.laravel.com/docs/4.x/security , https://livewire.laravel.com/docs/4.x/actions , https://livewire.laravel.com/docs/4.x/redirecting - HIGH confidence

---
*Architecture research for: Tenanto brownfield modernization*
*Researched: 2026-03-19*
