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
- flushed the in-memory manager permission cache in Pest bootstrap so request-scoped permission checks stay isolated across feature tests

### Subscription request validation coverage

- added request-structure and validation scenario coverage for superadmin organization subscription create and update requests
