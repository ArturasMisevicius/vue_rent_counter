# Legacy Domain Import Ledger

This ledger maps every top-level model from `_old/app/Models` into the current
Tenanto codebase. It exists to keep the import one-way and additive: we extend
current models where the concept already exists, import missing domain models
where the current project has no equivalent, and defer only where the current
platform already has an authoritative implementation or no current surface
depends on the legacy concept yet.

| Legacy Model | Action | Current Target | Missing Schema | Missing Model Support | Follow-Up Notes |
| --- | --- | --- | --- | --- | --- |
| Activity | import | `App\Models\Activity` | activity log table and indexes | polymorphic actor/subject relations | Land in collaboration foundation |
| Attachment | import | `App\Models\Attachment` | attachments table and polymorphic columns | uploader and attachable relations | Keep storage metadata additive |
| AuditLog | merge | `App\Models\AuditLog` | legacy context columns only if missing | actor/organization convenience relations | Extend current audit surface instead of duplicating |
| BillingRecord | import | `App\Models\BillingRecord` | billing records table and invoice linkage | invoice, property, and provider relations | Operations foundation |
| Building | merge | `App\Models\Building` | legacy lookup/support columns only if missing | utility/billing relations if needed | Preserve current building table as canonical |
| Comment | import | `App\Models\Comment` | comments table and morph columns | author, parent, and commentable relations | Collaboration foundation |
| CommentReaction | import | `App\Models\CommentReaction` | comment reactions table | user and comment relations | Collaboration foundation |
| Currency | import | `App\Models\Currency` | currencies table | exchange-rate, property, and invoice relations | Reference foundation |
| DashboardCustomization | import | `App\Models\DashboardCustomization` | dashboard customizations table | owner/organization relations and casted preferences | Collaboration foundation |
| EnhancedTask | import | `App\Models\EnhancedTask` | enhanced tasks table | links to task/project ownership | Collaboration foundation |
| ExchangeRate | import | `App\Models\ExchangeRate` | exchange rates table | from/to currency relations | Reference foundation |
| Faq | import | `App\Models\Faq` | faqs table | language/status casts and scopes | Reference foundation |
| IntegrationHealthCheck | merge | `App\Models\IntegrationHealthCheck` | legacy metadata columns only if missing | provider/system relations and scopes | Keep current health-check model authoritative |
| Invoice | merge | `App\Models\Invoice` | legacy billing/support columns only if missing | items, audits, currency, and billing relations | Extend current invoice model additively |
| InvoiceGenerationAudit | import | `App\Models\InvoiceGenerationAudit` | invoice generation audits table | invoice and actor relations | Operations foundation |
| InvoiceItem | import | `App\Models\InvoiceItem` | invoice items table | invoice, tariff, and meter relations | Operations foundation |
| Language | merge | `App\Models\Language` | translation support columns only if missing | translations relation and locale scopes | Wave 2 will also remove `es` runtime support |
| Lease | import | `App\Models\Lease` | leases table | property, tenant, and organization relations | Operations foundation |
| Meter | merge | `App\Models\Meter` | universal utility fields only if missing | tariff/service/provider relations | Preserve current meter records |
| MeterReading | merge | `App\Models\MeterReading` | legacy support columns only if missing | audit and utility relations | Extend current validation/submission model |
| MeterReadingAudit | import | `App\Models\MeterReadingAudit` | meter reading audits table | meter reading and actor relations | Operations foundation |
| Organization | merge | `App\Models\Organization` | legacy superadmin/platform fields only if missing | memberships and system-tenant relation | Extend current organization model |
| OrganizationActivityLog | import | `App\Models\OrganizationActivityLog` | organization activity table | organization and actor relations | Consider consolidation with audit logs later |
| OrganizationInvitation | merge | `App\Models\OrganizationInvitation` | legacy invitation columns only if missing | inviter/invitee support | Preserve current invitation flow |
| OrganizationUser | import | `App\Models\OrganizationUser` | organization-user membership table | membership roles, status casts, and relations | Supplements current `users.organization_id` model |
| PersonalAccessToken | defer | `Laravel\Sanctum\PersonalAccessToken` | none | none | Current Sanctum implementation is authoritative; add a local model only if customization becomes necessary |
| PlatformNotification | merge | `App\Models\PlatformNotification` | legacy delivery/support columns only if missing | recipients and delivery relations | Preserve current notification surface |
| PlatformNotificationRecipient | import | `App\Models\PlatformNotificationRecipient` | notification recipients table | notification and user relations | Platform foundation |
| PlatformOrganizationInvitation | import | `App\Models\PlatformOrganizationInvitation` | platform organization invitations table | inviter, invitee, and organization relations | Platform foundation |
| Project | import | `App\Models\Project` | projects table | organization, owner, and task relations | Collaboration foundation |
| Property | merge | `App\Models\Property` | legacy billing/currency fields only if missing | lease, tariff, and utility relations | Keep current property model canonical |
| PropertyTenantPivot | merge | `App\Models\PropertyAssignment` | only additive assignment fields if missing | assignment lifecycle helpers | Current project already models tenant occupancy via property assignments |
| Provider | import | `App\Models\Provider` | providers table | tariffs, services, and organization relations | Reference foundation |
| SecurityViolation | merge | `App\Models\SecurityViolation` | legacy support columns only if missing | actor/context relations and scopes | Preserve current violation tracking |
| ServiceConfiguration | import | `App\Models\ServiceConfiguration` | service configurations table | provider, utility service, and tariff relations | Reference foundation |
| SharedService | defer | `App\Models\ServiceConfiguration` | none | current shared-service behavior already rides on service configuration flags and distribution rules | Add a first-class shared-service catalog only when allocation workflows require a durable model |
| Subscription | merge | `App\Models\Subscription` | auto-renewal/support columns only if missing | renewal history relation | Keep current subscription table authoritative |
| SubscriptionRenewal | import | `App\Models\SubscriptionRenewal` | subscription renewals table | subscription and actor relations | Operations foundation |
| SuperAdminAuditLog | import | `App\Models\SuperAdminAuditLog` | superadmin audit logs table | actor and subject relations | Platform foundation |
| SystemConfiguration | import | `App\Models\SystemConfiguration` | system configurations table | key/value casts and system-tenant relation | Platform foundation |
| SystemTenant | import | `App\Models\SystemTenant` | system tenants table | organizations/users relation | Platform foundation |
| Tag | import | `App\Models\Tag` | tags and taggables tables | polymorphic tagging relations | Collaboration foundation |
| Tariff | import | `App\Models\Tariff` | tariffs table | provider, currency, and service relations | Reference foundation |
| Task | import | `App\Models\Task` | tasks table | project, assignee, and status relations | Collaboration foundation |
| TaskAssignment | import | `App\Models\TaskAssignment` | task assignments table | task and user relations | Collaboration foundation |
| Tenant | merge | `App\Models\User` + `App\Models\PropertyAssignment` | no dedicated tenant table planned | tenant-role scopes and occupancy helpers | Current project models tenants as users with assignments |
| TimeEntry | import | `App\Models\TimeEntry` | time entries table | task, user, and project relations | Collaboration foundation |
| Translation | import | `App\Models\Translation` | translations table | language-aware translation scopes | Reference foundation and Wave 2 localization |
| User | merge | `App\Models\User` | legacy hierarchy/support columns only if missing | system-tenant, currency, and assignment helpers | Extend current role-based user model |
| UtilityReading | merge | `App\Models\MeterReading` | no dedicated schema planned | current meter reading fields already cover value, reading date, notes, and property-meter scoping | Keep the modern meter-reading model canonical instead of importing a duplicate legacy type |
| UtilityService | import | `App\Models\UtilityService` | utility services table | provider, tariff, and configuration relations | Reference foundation |

## Summary

- `merge`: reuse and extend current project models where the concept already exists
- `import`: add missing domain models and additive schema into the current project
- `defer`: keep out of the current implementation until a concrete current-project dependency exists

## Immediate Wave 1 Focus

Wave 1 covers the lowest-risk foundations needed to unlock later Baltic
localization and large logical fixtures:

- reference/platform imports such as `Currency`, `ExchangeRate`, `Faq`,
  `Translation`, `Provider`, `Tariff`, `UtilityService`, and
  `ServiceConfiguration`
- operations support such as `InvoiceItem`, `BillingRecord`, `Lease`, and
  `SubscriptionRenewal`
- platform/collaboration primitives only after the reference and billing
  foundation is stable
