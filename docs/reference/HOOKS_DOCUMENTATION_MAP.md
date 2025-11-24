# Kiro Hooks Documentation Map

This project ships custom `.kiro/hooks` to enforce guidance on multi-tenancy, Filament v4, code quality, and Laravel practices. Use this map to find the corresponding in-repo docs that satisfy each hook’s requirements and what to add when new areas arise.

## Hook Coverage Map
- **filament-v4-best-practices / laravel-doc-generator**  
  `docs/filament/FILAMENT_V4_COMPATIBILITY_GUIDE.md`, `docs/filament/PROPERTY_RESOURCE_VALIDATION_LOCALIZATION.md`, `docs/filament/VALIDATION_LOCALIZATION_COMPLETE.md`
- **multi-tenant-architecture**  
  `docs/architecture/MULTI_TENANT_ARCHITECTURE.md`, `docs/testing/FACTORY_AND_SEEDING_GUIDE.md`, `docs/database/TENANT_HISTORY_SEEDING.md`
- **laravel-performance-optimizer / n-plus-one-analyzer / database-query-optimization**  
  `docs/performance/` folder, `docs/implementation/PROPERTIES_RELATION_MANAGER_COMPLETE.md`
- **laravel-security-audit / content-moderation-specialist**  
  `docs/security/SECURITY_AUDIT_SUMMARY.md`
- **laravel-test-generator / laravel-code-review**  
  `docs/testing/` folder (TESTING_RECOMMENDATIONS, PROPERTIES_RELATION_MANAGER_TESTING_SUMMARY, INVOICE_FINALIZATION_TEST_SUMMARY)
- **code-quality-analyzer / laravel-bug-debugger / laravel-expert-assistant / laravel-code-refactor / laravel-legacy-refactor**  
  `docs/architecture/DEBUGGING_AND_CODE_QUALITY.md`
- **feature-architecture-generator**  
  `docs/architecture/FEATURE_ARCHITECTURE_TEMPLATE.md`
- **database-schema-designer / zero-downtime-migration**  
  `docs/architecture/DATABASE_SCHEMA_AND_MIGRATION_GUIDE.md`
- **repository-pattern-implementation / service-layer-implementation**  
  `docs/architecture/SERVICE_AND_REPOSITORY_GUIDE.md`
- **laravel-api-architect / laravel-architecture-advisor / laravel-requirements-analyst**  
  `docs/api/API_ARCHITECTURE_GUIDE.md`
- **eloquent-relationships-guide**  
  `docs/architecture/ELOQUENT_RELATIONSHIPS_GUIDE.md`
- **graphql-api-builder**  
  `docs/api/GRAPHQL_API_TEMPLATE.md` (template until implemented)
- **content-orchestrator-master / content-recommendation-engine / newsletter-generator / it-news-curator / headline-optimizer / seo-content-optimizer / landing-content-sync**  
  `docs/frontend/CONTENT_PIPELINES_TEMPLATE.md`
- **content-moderation-specialist**  
  `docs/reference/CONTENT_MODERATION_POLICY.md`
- **tech-news-writer / tech-fact-checker / it-news-curator / newsletter-generator**  
  `docs/reference/NEWS_AND_FACTCHECK_TEMPLATE.md`
- **docs-relocator / md-file-organizer**  
  `docs/reference/DOCS_ORGANIZATION_SOP.md`
- **multi-tenant-supporting hooks (multi-tenant-architecture overlaps)**  
  See multi-tenant docs above.

## When to Add New Docs
- A new Filament resource/relation manager: add a short resource note and update the Filament guide if patterns change.
- New tenant-aware factories/seeders: update `FACTORY_AND_SEEDING_GUIDE.md` and `TENANT_HISTORY_SEEDING.md`.
- New performance-critical feature: add a brief note under `docs/performance/`.
- New security-sensitive flow: add to `docs/security/` and link in this map.
- Hooks marked “Not in use” above: create a 1–2 page guide in the relevant folder when the associated feature/stack is added.

## How to Keep This Map Fresh
- On introducing new hooks or major features, add a bullet mapping the hook to the doc path.
- If a hook triggers and no doc exists, create a minimal guide and list it here.
