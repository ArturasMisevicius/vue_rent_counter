# Changelog

## 2026-03-28

### Organization single-subscription relation

- changed the organization `Subscriptions` relation tab to manage only the current subscription record
- added relation-scoped create when an organization has no subscription yet
- added relation-scoped edit for the current subscription while preserving history access through the existing modal
- added request and action classes for creating and updating organization subscriptions from the relation manager
- added focused Pest coverage for the new single-subscription relation behavior
