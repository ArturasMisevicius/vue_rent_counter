## Context
The application already contains a substantial policy layer, but enforcement is inconsistent across surfaces. Controllers often call `$this->authorize()`, while Blade templates and Filament resources still contain raw role checks or one-off `can*()` logic. The current repository shows policy coverage for many core resources, but at least one named target in scope (`Translation`) does not yet have a dedicated policy file. The end result is that route protection, action visibility, and metadata visibility are not guaranteed to agree everywhere.

## Goals / Non-Goals
- Goals:
  - One canonical authorization model across Blade, Livewire, and Filament.
  - Full policy coverage for the named core resources.
  - Superadmin global full-control semantics defined once and reused everywhere.
  - Scoped authorization for admin, manager, and tenant preserved by tenant/property boundaries.
  - Sensitive metadata visibility normalized and limited to superadmin surfaces.
- Non-Goals:
  - Replacing route middleware with policies.
  - Rewriting tenant-isolation rules or subscription business rules.
  - Broad UI redesign work outside authorization behavior.

## Decisions
- Policies are the source of truth for resource-level capabilities; route middleware remains the coarse role/scope guard.
- Blade surfaces should prefer `@can`, `@cannot`, and `@canany` or authorization-aware view models instead of direct role comparisons.
- Livewire components should authorize actions explicitly and derive privileged UI state from policy checks instead of raw role checks.
- Filament resources, pages, and widgets should use policy-backed authorization hooks consistently for navigation visibility, access, and actions.
- Superadmin is the only global role with full CRUD plus restore/force-delete, export, impersonation, audit, and system-configuration control.
- Deep operational metadata is treated as privileged data and is hidden unless the superadmin authorization contract explicitly allows it.

## Risks / Trade-offs
- Some existing views or Filament pages may currently rely on direct role checks for convenience.
  - Mitigation: inventory those checks first and migrate them ability-by-ability instead of bulk replacing blindly.
- Filament resources may already have custom `can*()` behavior that diverges from policies.
  - Mitigation: make those hooks delegate to the same policy abilities instead of maintaining separate logic.
- Superadmin full-control semantics can unintentionally leak privileged metadata into non-superadmin shared views.
  - Mitigation: define a superadmin-only metadata contract and test it directly.

## Migration Plan
1. Inventory policies, raw role checks, and Filament authorization hooks.
2. Fill missing policy coverage and normalize ability names/semantics.
3. Replace Blade raw role checks with authorization directives or shared authorized view data.
4. Replace Livewire raw role checks with explicit policy-backed authorization.
5. Align Filament navigation/resource/page hooks with the policy layer.
6. Add superadmin-only metadata visibility rules and tests.
7. Publish an authorization normalization report with follow-up items.
