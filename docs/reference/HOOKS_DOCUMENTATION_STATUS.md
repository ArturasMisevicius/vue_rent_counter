# Hooks Documentation Status (Audit Snapshot)

All `.kiro/hooks` have been mapped to documentation or templates. Use this snapshot to confirm coverage and identify the action if a new feature lands.

## Implemented Hooks → Docs
- Filament v4 best practices / doc generation → [docs/filament/FILAMENT_V4_COMPATIBILITY_GUIDE.md](../filament/FILAMENT_V4_COMPATIBILITY_GUIDE.md), [docs/filament/PROPERTY_RESOURCE_VALIDATION_LOCALIZATION.md](../filament/PROPERTY_RESOURCE_VALIDATION_LOCALIZATION.md), [docs/filament/VALIDATION_LOCALIZATION_COMPLETE.md](../filament/VALIDATION_LOCALIZATION_COMPLETE.md)
- Multi-tenancy → [docs/architecture/MULTI_TENANT_ARCHITECTURE.md](../architecture/MULTI_TENANT_ARCHITECTURE.md), [docs/testing/FACTORY_AND_SEEDING_GUIDE.md](../testing/FACTORY_AND_SEEDING_GUIDE.md), [docs/database/TENANT_HISTORY_SEEDING.md](../database/TENANT_HISTORY_SEEDING.md)
- API architecture → [docs/api/API_ARCHITECTURE_GUIDE.md](../api/API_ARCHITECTURE_GUIDE.md)
- DB schema/migrations → [docs/architecture/DATABASE_SCHEMA_AND_MIGRATION_GUIDE.md](../architecture/DATABASE_SCHEMA_AND_MIGRATION_GUIDE.md)
- Services/repositories → [docs/architecture/SERVICE_AND_REPOSITORY_GUIDE.md](../architecture/SERVICE_AND_REPOSITORY_GUIDE.md)
- Eloquent relationships → [docs/architecture/ELOQUENT_RELATIONSHIPS_GUIDE.md](../architecture/ELOQUENT_RELATIONSHIPS_GUIDE.md)
- Performance/N+1/DB → `docs/performance/`, [docs/implementation/PROPERTIES_RELATION_MANAGER_COMPLETE.md](../implementation/PROPERTIES_RELATION_MANAGER_COMPLETE.md)
- Security → [docs/security/SECURITY_AUDIT_SUMMARY.md](../security/SECURITY_AUDIT_SUMMARY.md)
- Testing/code quality → `docs/testing/`, [docs/architecture/DEBUGGING_AND_CODE_QUALITY.md](../architecture/DEBUGGING_AND_CODE_QUALITY.md)
- Content moderation → [docs/reference/CONTENT_MODERATION_POLICY.md](CONTENT_MODERATION_POLICY.md)
- Docs organization → [docs/reference/DOCS_ORGANIZATION_SOP.md](DOCS_ORGANIZATION_SOP.md)
- Feature architecture → [docs/architecture/FEATURE_ARCHITECTURE_TEMPLATE.md](../architecture/FEATURE_ARCHITECTURE_TEMPLATE.md)
- Content pipelines (template) → [docs/frontend/CONTENT_PIPELINES_TEMPLATE.md](../frontend/CONTENT_PIPELINES_TEMPLATE.md)
- News/fact-check (template) → [docs/reference/NEWS_AND_FACTCHECK_TEMPLATE.md](NEWS_AND_FACTCHECK_TEMPLATE.md)
- Hook maps/templates → [docs/reference/HOOKS_DOCUMENTATION_MAP.md](HOOKS_DOCUMENTATION_MAP.md), [HOOKS_PENDING_FEATURES.md](HOOKS_PENDING_FEATURES.md), [HOOK_RESPONSE_TEMPLATES.md](HOOK_RESPONSE_TEMPLATES.md)

## Pending Hooks (no feature yet)
- GraphQL (`graphql-api-builder`): create a GraphQL API guide if/when implemented.
- Content engines (orchestrator/recommendation/SEO/newsletters): use [docs/frontend/CONTENT_PIPELINES_TEMPLATE.md](../frontend/CONTENT_PIPELINES_TEMPLATE.md) to create a feature doc when built.
- Any new hook: add entry to [HOOKS_DOCUMENTATION_MAP.md](HOOKS_DOCUMENTATION_MAP.md) and create/extend a guide.

Status: No undocumented areas remain for current features. Update this file when new hooks are added or features go live.*** End Patch
