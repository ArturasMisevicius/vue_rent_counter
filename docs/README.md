# CFlow Documentation

Welcome to the CFlow documentation. This documentation is organized into logical groups for easy navigation and maintenance.

## Documentation Structure

### 🏗️ Framework & Technology
- **[Laravel](./laravel/)** - Laravel-specific documentation and patterns
- **[Filament](./filament/)** - Filament admin panel documentation
- **[Frontend](./frontend/)** - Frontend development (Alpine.js, Tailwind CSS)
  - [Landing Page Localization](frontend/landing-page-localization.md) - Multi-language landing page system
- **[Database](./database/)** - Database design, migrations, and optimization
- **[Architecture](./architecture/)** - System architecture, patterns, and design decisions
  - [TenantInitializationService Architecture](architecture/TenantInitializationService-Architecture.md) - Service architecture and integration patterns
  - [PolicyRegistry Architecture](architecture/POLICY_REGISTRY_ARCHITECTURE.md) - Policy registration system architecture and design patterns
- **[Testing](./testing/)** - Testing strategies, patterns, and guidelines
  - [TenantInitializationService Testing Guide](testing/TenantInitializationService-Testing-Guide.md) - Comprehensive testing patterns and strategies
  - [PolicyRegistry Testing Guide](testing/POLICY_REGISTRY_TESTING_GUIDE.md) - Testing patterns for defensive policy registration system

### 🔧 Features & Components
- **[Authentication](./features/authentication/)** - User authentication and authorization
- **[Billing](./features/billing/)** - Subscription and billing system
- **[Notifications](./features/notifications/)** - Notification system
- **[Translations](./features/translations/)** - Multi-language support
- **[Landing Page Development](./guides/landing-page-development.md)** - Landing page development guide
- **[Tagging System](./traits/HASTAGS_TRAIT_DOCUMENTATION.md)** - Polymorphic tagging with HasTags trait
- **[Services](./services/)** - Application services and business logic
  - [TenantInitializationService](services/TenantInitializationService.md) - Tenant onboarding and utility service setup
  - [AccountManagementService](services/AccountManagementService.md) - Hierarchical user account management
- **[API](./api/)** - API documentation and integration guides
  - [Authentication API](api/authentication.md) - API authentication and token management
  - [User Model API](api/USER_MODEL_API.md) - User model API token functionality

### 📊 Operations & Maintenance
- **[Performance](./performance/)** - Performance optimization and monitoring
- **[Security](./security/)** - Security guidelines and best practices
  - [PolicyRegistry Security Guide](security/POLICY_REGISTRY_SECURITY_GUIDE.md) - Comprehensive security guide for policy registration system
  - [PolicyRegistry Quick Reference](security/POLICY_REGISTRY_QUICK_REFERENCE.md) - Quick reference for PolicyRegistry usage and troubleshooting
- **[Monitoring](./monitoring/)** - Application monitoring and logging
- **[Troubleshooting](./troubleshooting/)** - Common issues and solutions

### 📚 Reference
- **[Enums](./enums/)** - Enum documentation and usage patterns
- **[Code Standards](./standards/)** - Coding standards and conventions
- **[Translation Keys](./reference/)** - Translation key references and specifications
  - [Landing Page Translation Keys](reference/landing-translation-keys.md) - Complete landing page translation reference
- **[Changelog](./changelog/)** - Version history and changes
  - [Landing Page Localization Update](changelog/LANDING_PAGE_LOCALIZATION_UPDATE.md) - Recent landing page improvements
  - [PolicyRegistry Test Resilience Enhancement](changelog/POLICY_REGISTRY_TEST_RESILIENCE_ENHANCEMENT.md) - Enhanced defensive programming validation
- **[Migration Guides](./migration/)** - Upgrade and migration guides

## Quick Start

1. **New Developers**: Start with [Development Setup](development/setup.md)
2. **Architecture Overview**: Read [System Architecture](architecture/overview.md)
3. **Coding Standards**: Review [Code Standards](standards/overview.md)
4. **Testing**: Follow [Testing Guidelines](testing/overview.md)

## Contributing to Documentation

- Keep documentation up-to-date with code changes
- Use clear, concise language
- Include code examples where appropriate
- Follow the established structure and naming conventions
- Update this README when adding new sections

## Documentation Maintenance

This documentation is automatically read by the development agent to ensure consistency and adherence to project standards. All steering rules and guidelines are derived from this documentation.

Last Updated: December 2024