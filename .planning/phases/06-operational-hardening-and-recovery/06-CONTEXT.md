# Phase 6: Operational Hardening and Recovery - Context

**Gathered:** 2026-03-19
**Status:** Ready for planning

<domain>
## Phase Boundary

Phase 6 proves the cleaned-up application can be monitored, recovered, and released with trustworthy operational evidence. It turns existing health and backup placeholders into runnable, testable operational behavior. This phase does not expand the product surface; it hardens the system for release confidence.

</domain>

<decisions>
## Implementation Decisions

### Health and readiness policy
- Health probes must prove real connectivity or execution, not configuration presence alone.
- Database, queue, and mail status should expose degraded states clearly when runtime behavior fails.

### Backup and restore policy
- Backup and restore confidence must come from runnable commands and documented procedures, not just seeded settings categories.
- Phase 6 should prefer the repository's current primitives first and only add package surface deliberately if truly necessary.

### Release evidence policy
- The milestone should end with documented operational checks that another maintainer can run.
- Operational proof should be backed by executable tests or commands wherever possible.

</decisions>

<canonical_refs>
## Canonical References

- `.planning/ROADMAP.md`
- `.planning/REQUIREMENTS.md`
- `.planning/codebase/CONCERNS.md`
- `app/Filament/Pages/IntegrationHealth.php`
- `app/Filament/Support/Superadmin/Integration/IntegrationProbeRegistry.php`
- `app/Filament/Support/Superadmin/Integration/Probes/DatabaseProbe.php`
- `app/Filament/Support/Superadmin/Integration/Probes/QueueProbe.php`
- `app/Filament/Support/Superadmin/Integration/Probes/MailProbe.php`
- `routes/console.php`
- `composer.json`
- `database/seeders/SystemSettingSeeder.php`
- `app/Enums/SystemSettingCategory.php`
- `tests/Feature/Superadmin/IntegrationHealthPageTest.php`

</canonical_refs>

<code_context>
## Existing Code Insights

- The concerns audit explicitly flags the current integration-health page as capable of showing false healthy states because probes check config or table presence rather than real runtime behavior.
- The repository has queue and scheduler commands available, but no first-class backup or restore command surface yet.
- The current codebase already exposes a `backups` system-setting category, which should be treated as configuration input, not proof of working recovery.

</code_context>

---

*Phase: 06-operational-hardening-and-recovery*
*Context gathered: 2026-03-19*
