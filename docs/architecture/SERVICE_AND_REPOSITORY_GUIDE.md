# Service & Repository Guide

**Stack:** Laravel 12, Filament v4, multi-tenant (`tenant_id` scope).  
**Hooks covered:** `service-layer-implementation`, `repository-pattern-implementation`.

## When to Introduce a Repository/Service
- Use a **service** for business workflows spanning multiple models (e.g., billing finalization, tenant reassignment).
- Use a **repository** for complex queries or aggregates that don’t fit cleanly in Eloquent scopes, especially if multiple consumers need the same query shape.
- Keep controllers/resources thin; delegate to services for orchestration and to repositories for data access patterns.

## Conventions
- **Repositories:**
  - Interface in `App\Contracts\Repositories\...`
  - Implementation in `App\Repositories\...`
  - Inject dependencies via constructors; avoid static helpers.
  - Respect tenant scope: accept `tenant_id` explicitly or rely on `BelongsToTenant`-scoped models.
  - Provide paginated/projected methods instead of returning raw builders from controllers.
- **Services:**
  - Live in `App\Services\...`
  - Accept DTOs/validated arrays (from FormRequest/Action) to avoid leaking request objects.
  - Handle transactions where workflows touch multiple aggregates.
  - Emit domain events where useful (e.g., invoice finalized).

## Testing
- **Repositories:** contract tests for query shape and tenant isolation; use factories with `forTenantId`.
- **Services:** unit tests for pure logic; integration tests for workflows (e.g., invoice generation) using seeders/factories.
- Prefer fakes/stubs for external integrations; keep database assertions tenant-aware.

## Tenancy Considerations
- Always thread `tenant_id` through service/repository boundaries or rely on scoped models that already include `BelongsToTenant`.
- Avoid cross-tenant joins; validate inputs against the current tenant context.

## Example Skeleton (Repository)
```php
namespace App\Repositories;

use App\Contracts\Repositories\InvoiceRepositoryContract;
use App\Models\Invoice;

class InvoiceRepository implements InvoiceRepositoryContract
{
    public function listForTenant(int $tenantId, array $filters = [])
    {
        return Invoice::query()
            ->where('tenant_id', $tenantId)
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->latest('billing_period_start')
            ->paginate(20);
    }
}
```

## Example Skeleton (Service)
```php
namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class InvoiceFinalizationService
{
    public function finalize(Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($invoice) {
            $invoice->finalize();
            // emit events / notifications as needed
            return $invoice->fresh();
        });
    }
}
```

## When to Document
- Add a short note in this guide and link in `docs/reference/HOOKS_DOCUMENTATION_MAP.md` when a new repository/service is created for a feature.
