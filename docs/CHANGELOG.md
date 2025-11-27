# Changelog

All notable changes to the Vilnius Utilities Billing Platform are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

#### TariffResource Documentation (2024-11-27)
- **Comprehensive Documentation Suite**
  - Created `TARIFF_RESOURCE_API.md` with complete API reference including authorization matrix, form schema, validation rules, table configuration, and usage examples
  - Created `TARIFF_RESOURCE_USAGE_GUIDE.md` with user-facing guide covering flat rate and time-of-use tariff creation, common scenarios, and troubleshooting
  - Updated `TARIFF_RESOURCE_NAVIGATION_UPDATE.md` with enhanced documentation details
  - Enhanced code-level documentation with comprehensive PHPDoc blocks for all methods

- **Navigation Visibility Enhancement**
  - Updated `shouldRegisterNavigation()` to include SUPERADMIN role alongside ADMIN
  - Added explicit `instanceof` check to prevent null pointer exceptions
  - Implemented strict type checking in `in_array()` for security
  - Matched pattern used in ProviderResource for consistency across configuration resources

- **Code Documentation Improvements**
  - Enhanced class-level PHPDoc with complete feature overview, security notes, and cross-references
  - Added detailed method documentation for `shouldRegisterNavigation()` explaining requirements addressed and implementation notes
  - Documented all authorization methods (`canViewAny()`, `canCreate()`, `canEdit()`, `canDelete()`) with parameter types and policy references
  - Added comprehensive `@see` tags linking to related classes, policies, observers, and tests

- **API Documentation**
  - Complete authorization matrix showing role-based access (SUPERADMIN and ADMIN only)
  - Detailed form schema documentation with all validation rules
  - Security features documentation (XSS prevention, numeric overflow protection, zone ID injection prevention)
  - Table schema with query optimization notes
  - Audit logging documentation via TariffObserver
  - Usage examples for creating, editing, and deleting tariffs
  - Error handling documentation with example responses
  - Testing documentation with test file references and commands

- **Usage Guide**
  - Step-by-step instructions for creating flat rate tariffs
  - Step-by-step instructions for creating time-of-use tariffs with zone configuration
  - Common scenarios: annual rate increases, switching tariff types, temporary rate adjustments
  - Troubleshooting section with common errors and solutions
  - Security considerations and best practices
  - Related resources and support information

#### UserResource Enhancements (2025-11-26)
- **Comprehensive Documentation Suite**
  - Created `USER_RESOURCE_API.md` with complete API reference including all form fields, validation rules, table configuration, and authorization matrix
  - Created `USER_RESOURCE_USAGE_GUIDE.md` with user-facing guide covering common workflows, troubleshooting, and best practices
  - Created `USER_RESOURCE_ARCHITECTURE.md` with technical architecture documentation including component relationships, data flow, security architecture, and performance considerations
  - Enhanced code-level documentation with comprehensive PHPDoc blocks for all methods

- **Form Schema Improvements**
  - Reorganized form into two logical sections: "User Details" and "Role and Access"
  - Added section descriptions for better UX
  - Added placeholder text for all input fields
  - Added helper text for password, role, tenant, and is_active fields
  - Improved password field with proper dehydration and hashing logic
  - Enhanced tenant field with relationship scoping and conditional visibility/requirement based on role

- **ViewUser Page**
  - Created dedicated view page with comprehensive infolist
  - Three sections: User Details, Role and Access, Metadata
  - Copyable fields for name and email with toast notifications
  - Color-coded role badges (Superadmin=red, Admin=yellow, Manager=blue, Tenant=green)
  - Conditional display of tenant field based on assignment
  - Header actions for edit and delete operations
  - Collapsible metadata section with created_at and updated_at timestamps

- **Table Enhancements**
  - Added role and is_active filters with proper localization
  - Made email column copyable with toast notification
  - Enhanced role column with color-coded badges
  - Added session persistence for sort, search, and filters
  - Improved empty state with heading, description, and create action
  - Added navigation badge showing user count (respects tenant scoping)

- **Tenant Scoping**
  - Implemented `getEloquentQuery()` override for proper tenant scoping in table queries
  - Superadmins see all users across all tenants
  - Admins and Managers see only users within their tenant
  - Tenant field options filtered by authenticated user's tenant
  - Prevents cross-tenant data leakage

- **Authorization Integration**
  - Full integration with UserPolicy for all CRUD operations
  - Navigation visibility controlled by role (hidden from Tenant users)
  - All operations gated by policy methods (viewAny, view, create, update, delete)
  - Audit logging for sensitive operations (update, delete, restore, forceDelete, impersonate)

- **Localization**
  - All labels, placeholders, helper text, and validation messages localized
  - Translation keys organized in `lang/{locale}/users.php`
  - Validation messages loaded via `HasTranslatedValidation` trait
  - Support for English, Lithuanian, and Russian locales

### Changed

#### UserResource Refactoring (2025-11-26)
- **Code Quality Improvements**
  - Removed duplicate method definitions (`getEloquentQuery()`, `isTenantRequired()`, `isTenantVisible()`)
  - Consolidated helper methods into single definitions with proper PHPDoc
  - Improved code organization and readability
  - Enhanced type safety with proper type hints and return types

- **Navigation Configuration**
  - Changed navigation group from "Administration" to "System"
  - Changed navigation sort order from 1 to 8
  - Updated `shouldRegisterNavigation()` to include Manager role

- **Form Field Updates**
  - Password field now uses `operation` context instead of deprecated `context`
  - Password dehydration logic improved with proper null handling
  - Tenant field now uses `Forms\Get` instead of deprecated `Get` utility
  - Removed organization_name and property_id fields (legacy from old structure)
  - Removed parent_user_id display field (redundant with tenant relationship)

- **Table Configuration**
  - Removed bulk actions for Filament v4 compatibility
  - Changed from `->actions()` to `->recordActions()` for row actions
  - Removed empty state actions (use page header actions instead)
  - Updated column labels and formatting

### Fixed

#### UserResource Bug Fixes (2025-11-26)
- Fixed missing `getEloquentQuery()` override causing incorrect tenant scoping in table queries
- Fixed duplicate method definitions causing PHP errors
- Fixed password field dehydration logic to properly handle null values
- Fixed tenant field visibility logic to properly show/hide based on role
- Fixed navigation badge to respect tenant scoping for non-superadmin users

### Security

#### UserResource Security Enhancements (2025-11-26)
- **Password Security**
  - Passwords hashed using `Hash::make()` before storage
  - Password confirmation field not dehydrated (validation only)
  - Passwords never displayed in table or view pages
  - Optional password updates on edit (only updated if filled)

- **Tenant Isolation**
  - All queries scoped by tenant_id for non-superadmin users
  - Tenant field options filtered by authenticated user's tenant
  - UserPolicy enforces tenant boundaries on all operations
  - Prevents cross-tenant data access and modification

- **Authorization**
  - All CRUD operations gated by UserPolicy
  - Sensitive operations audit logged with actor/target details, IP, and user agent
  - Self-deletion prevented at policy level
  - Impersonation restricted to superadmins only

- **Audit Logging**
  - All sensitive operations logged to audit channel
  - Includes operation type, actor details, target details, IP, user agent, and timestamp
  - Logged operations: update, delete, restore, forceDelete, impersonate

### Performance

#### UserResource Performance Optimizations (2025-11-26)
- **Query Optimization**
  - Tenant scoping applied at query level using indexed column
  - Relationship preloading for tenant select field to prevent N+1 queries
  - Session persistence for sort, search, and filters reduces database queries

- **Database Indexes**
  - Documented required indexes: tenant_id, role, is_active, email (unique)
  - Suggested composite index for common queries: (tenant_id, role)

- **Caching Opportunities**
  - Navigation badge count calculated on each request (consider caching for high-traffic)
  - Translation strings cached by Laravel's translation system

### Documentation

#### UserResource Documentation (2025-11-26)
- **API Documentation** (`docs/filament/USER_RESOURCE_API.md`)
  - Complete API reference with all form fields, validation rules, and table configuration
  - Authorization matrix showing permissions by role
  - Tenant scoping behavior and implementation details
  - Navigation badge configuration and behavior
  - Security considerations and audit logging
  - Usage examples for creating, filtering, updating, and deleting users
  - Performance considerations and database indexes
  - Testing examples with Pest

- **Usage Guide** (`docs/filament/USER_RESOURCE_USAGE_GUIDE.md`)
  - Quick start guide for accessing and using the interface
  - Step-by-step instructions for creating, viewing, editing, and deleting users
  - Role-based tenant field behavior table
  - Common workflows: onboarding managers, deactivating accounts, changing roles, resetting passwords
  - Troubleshooting section with common issues and solutions
  - Best practices for password management, account management, and security
  - Programmatic usage examples

- **Architecture Documentation** (`docs/filament/USER_RESOURCE_ARCHITECTURE.md`)
  - Component relationships and dependencies diagram
  - Data flow diagrams for creating, viewing, and updating users
  - Security architecture with multi-tenant isolation and authorization layers
  - Form architecture with conditional field logic
  - Table architecture with column and filter configuration
  - Performance considerations and query optimization strategies
  - Localization architecture and translation structure
  - Testing architecture and coverage strategy
  - Integration points with upstream and downstream systems
  - Future enhancements and planned features

- **Code Documentation**
  - Enhanced PHPDoc blocks for all methods with @param, @return, and @throws tags
  - Documented requirements traceability (6.1, 6.2, 6.3, 6.4, 6.5, 6.6)
  - Added inline comments for non-obvious logic
  - Documented helper methods with usage examples

- **README Updates**
  - Updated `docs/filament/README.md` with UserResource documentation links
  - Added UserResource to User Management section with all documentation references

### Testing

#### UserResource Testing (2025-11-26)
- **Planned Property Tests**
  - Property 13: User validation consistency (validates requirement 6.4)
  - Property 14: Conditional tenant requirement for non-admin users (validates requirement 6.5)
  - Property 15: Null tenant allowance for admin users (validates requirement 6.6)

- **Test Coverage**
  - Unit tests for form validation rules
  - Feature tests for CRUD operations
  - Authorization tests for policy integration
  - Tenant isolation tests for query scoping

## [Previous Releases]

See individual changelog files:
- [Authentication Testing Changelog](./CHANGELOG_AUTHENTICATION_TESTS.md)
- [Exception Documentation Changelog](./CHANGELOG_EXCEPTION_DOCUMENTATION.md)
- [Migration Refactoring Changelog](./CHANGELOG_MIGRATION_REFACTORING.md)

---

## Notes

### Versioning Strategy
- **Major**: Breaking changes to public APIs or database schema
- **Minor**: New features, non-breaking changes
- **Patch**: Bug fixes, documentation updates, performance improvements

### Changelog Maintenance
- Update this file with every significant change
- Group changes by type: Added, Changed, Deprecated, Removed, Fixed, Security
- Include dates and requirement references where applicable
- Link to related documentation and issue trackers

### Related Documentation
- [Project Brief](../memory-bank/projectbrief.md)
- [Progress Tracking](../memory-bank/progress.md)
- [Task Tracking](../.kiro/specs/4-filament-admin-panel/tasks.md)
