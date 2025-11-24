# GraphQL API Template

**Hook coverage:** `graphql-api-builder`.  
Use this template when GraphQL endpoints are introduced.

## Schema & Types
- Define root queries/mutations; include pagination (connections/edges or offset-based) with totals.
- Types should mirror domain models; include tenant-aware fields only.
- Prefer IDs as opaque (UUID) if exposed externally; avoid leaking internal auto-increment IDs.

## Auth & Tenant Isolation
- Require auth on all operations unless explicitly public.
- Derive `tenant_id` from auth context; never accept it from client input.
- Apply policies/guards per field/resolver when needed.

## Validation & Errors
- Validate input via rules/DTOs before resolver logic.
- Standardize error shape (code/message/path); include extensions for validation errors.
- Mask unauthorized/missing resources as appropriate (404 vs. forbidden).

## Performance
- Avoid N+1: use DataLoader/batching or eager loading in resolvers.
- Add limits on list queries (max page size), depth/complexity limits.
- Index columns used in resolvers; cache where safe.

## Example Skeleton
```php
type Query {
  invoices(status: InvoiceStatus, first: Int = 20, after: String): InvoiceConnection @auth
}

type Mutation {
  finalizeInvoice(id: ID!): Invoice @auth @can(ability: "finalize", find: "id")
}
```

## Testing
- Add GraphQL feature tests for auth, tenant isolation, validation, pagination, and N+1 safeguards.
- Include malformed queries and complexity/depth limit tests.

## When to Update
- When GraphQL is actually implemented; convert this template into a concrete guide and link it in `HOOKS_DOCUMENTATION_MAP.md`.
