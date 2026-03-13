## ADDED Requirements
### Requirement: Unified Layout Shell
The system SHALL render all role dashboards and operational pages through a single canonical layout shell that applies the shared design system and role-aware navigation.

#### Scenario: Admin dashboard renders through canonical layout
- **WHEN** an admin navigates to the dashboard route
- **THEN** the page renders using the canonical layout shell and shared components

#### Scenario: Tenant dashboard renders through canonical layout
- **WHEN** a tenant navigates to the dashboard route
- **THEN** the page renders using the canonical layout shell with tenant-specific navigation items

### Requirement: Shared Design Tokens and Components
The system SHALL define and reuse a single shared design token source and component library for cards, buttons, forms, tables, alerts, badges, and empty states across all roles.

#### Scenario: Shared components drive role pages
- **WHEN** a role dashboard renders a stat card or alert
- **THEN** the output uses the shared component library and shared design tokens

### Requirement: Legacy UI Removal
The system SHALL remove or consolidate legacy layouts, duplicate components, and unused templates once their canonical replacements are in place.

#### Scenario: Legacy layouts are no longer referenced
- **WHEN** the application renders any dashboard or operational page
- **THEN** it does not reference superseded layout files or duplicate component stacks
