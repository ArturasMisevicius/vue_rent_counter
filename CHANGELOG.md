# Changelog

## 2026-03-28

### Organization single-subscription relation

- changed the organization `Subscriptions` relation tab to manage only the current subscription record
- added relation-scoped create when an organization has no subscription yet
- added relation-scoped edit for the current subscription while preserving history access through the existing modal
- added request and action classes for creating and updating organization subscriptions from the relation manager
- added focused Pest coverage for the new single-subscription relation behavior

### Manager permission matrix

- added a manager permission matrix system with dedicated model, factory, migration, exceptions, catalog, service, notification, and Livewire-backed superadmin editor
- gated manager write access through new policies, resource middleware, and navigation filtering so manager mutations are explicitly permissioned per resource
- synchronized manager membership state through observers and seeded the login demo workspace with organization memberships and a default property-manager preset
- added focused manager permission regression coverage across admin resources, the superadmin organization-user editor, and manager workspace parity
- aligned legacy admin resource and tenant UI coverage with explicit manager permission fixtures so managers stay read-only by default unless their matrix grants write access
- flushed the in-memory manager permission cache in Pest bootstrap so request-scoped permission checks stay isolated across feature tests
- keyed the in-memory manager permission cache by organization and user identity so equivalent model instances reuse the same per-request permission matrix
- scoped the “changes take effect immediately” banner to superadmin-only matrix contexts instead of showing it for every manager-permission editor

### Demo manager presets

- added a second seeded demo manager account with the billing-manager preset so both property and billing permission profiles are visible in demo data
- updated the curated login demo account presenter and Pest coverage to include the new billing manager example

### Subscription request validation coverage

- added request-structure and validation scenario coverage for superadmin organization subscription create and update requests

### Superadmin organization roster management

- added create, edit, and delete roster actions to the superadmin organization users relation manager with shared roster form components
- added organization roster store and update requests plus validation scenario coverage for the new superadmin user-management flow
- added translated action and notification strings for the superadmin organization user roster in English, Lithuanian, Russian, and Spanish
