# Documentation

Complete documentation for the Vilnius Utilities Billing Platform.

**Last Updated**: 2025-11-25

## Quick Start

- **[Setup Guide](guides/SETUP.md)** - Installation and configuration
- **[User Guide](guides/HIERARCHICAL_USER_GUIDE.md)** - Guide for each user role
- **[Testing Guide](guides/TESTING_GUIDE.md)** - Testing approach and conventions

## Feature Documentation

### Invoice Management
- **[Invoice Documentation Index](controllers/INVOICE_DOCUMENTATION_INDEX.md)** - Complete invoice management documentation
- **[Invoice Finalization Quick Reference](reference/INVOICE_FINALIZATION_QUICK_REFERENCE.md)** - Quick reference guide
- **[Invoice Finalization Flow](architecture/INVOICE_FINALIZATION_FLOW.md)** - Architecture and data flow

### Billing System
- **[BillingService API](api/BILLING_SERVICE_API.md)** - Billing service methods
- **[BillingService Implementation](implementation/BILLING_SERVICE_V2_IMPLEMENTATION.md)** - Implementation details
- **[BillingService Performance](performance/BILLING_SERVICE_PERFORMANCE_OPTIMIZATION.md)** - Performance optimization

### Meter Reading Management
- **[Meter Reading Controller](api/METER_READING_CONTROLLER_API.md)** - Meter reading endpoints
- **[Meter Reading Update Controller](api/METER_READING_UPDATE_CONTROLLER_API.md)** - Update endpoints
- **[Meter Reading Observer](api/METER_READING_OBSERVER_API.md)** - Audit trail implementation

### Tariff Management
- **[Tariff Controller](api/TARIFF_CONTROLLER_API.md)** - Tariff management endpoints
- **[Tariff Policy](api/TARIFF_POLICY_API.md)** - Authorization rules
- **[Tariff Resolver](implementation/TARIFF_RESOLVER_IMPLEMENTATION.md)** - Tariff selection logic

### Gyvatukas Calculator
- **[Gyvatukas Calculator API](api/GYVATUKAS_CALCULATOR_API.md)** - Circulation fee calculation
- **[Gyvatukas Implementation](implementation/GYVATUKAS_CALCULATOR_IMPLEMENTATION.md)** - Implementation details
- **[Gyvatukas Performance](performance/GYVATUKAS_CALCULATOR_OPTIMIZATION.md)** - Performance optimization

## Architecture

### System Architecture
- **[Multi-Tenancy Architecture](architecture/MULTI_TENANCY_ARCHITECTURE.md)** - Tenant isolation patterns
- **[Authorization Architecture](architecture/AUTHORIZATION_ARCHITECTURE.md)** - Policy-based access control
- **[Database Schema](architecture/DATABASE_SCHEMA.md)** - Database design

### Data Flow
- **[Invoice Finalization Flow](architecture/INVOICE_FINALIZATION_FLOW.md)** - Complete finalization architecture
- **[Billing Flow](architecture/BILLING_FLOW.md)** - Invoice generation flow
- **[Meter Reading Flow](architecture/METER_READING_FLOW.md)** - Reading submission flow

## API Reference

### Controllers
- **[FinalizeInvoiceController](api/FINALIZE_INVOICE_CONTROLLER_API.md)** - Invoice finalization
- **[InvoiceController](api/INVOICE_CONTROLLER_API.md)** - Invoice CRUD operations
- **[MeterReadingController](api/METER_READING_CONTROLLER_API.md)** - Meter reading submission
- **[MeterReadingUpdateController](api/METER_READING_UPDATE_CONTROLLER_API.md)** - Reading corrections
- **[TariffController](api/TARIFF_CONTROLLER_API.md)** - Tariff management

### Services
- **[BillingService](api/BILLING_SERVICE_API.md)** - Core billing operations
- **[GyvatukasCalculator](api/GYVATUKAS_CALCULATOR_API.md)** - Circulation fee calculation
- **[TariffResolver](api/TARIFF_RESOLVER_API.md)** - Tariff selection
- **[SubscriptionService](api/SUBSCRIPTION_SERVICE_API.md)** - Subscription management

### Policies
- **[InvoicePolicy](api/INVOICE_POLICY_API.md)** - Invoice authorization
- **[TariffPolicy](api/TARIFF_POLICY_API.md)** - Tariff authorization
- **[MeterReadingPolicy](api/METER_READING_POLICY_API.md)** - Meter reading authorization

## Implementation Guides

### Controllers
- **[Invoice Controller Implementation](controllers/INVOICE_CONTROLLER_IMPLEMENTATION_COMPLETE.md)** - Invoice management
- **[FinalizeInvoiceController Implementation](controllers/FINALIZE_INVOICE_CONTROLLER_REFACTORING_COMPLETE.md)** - Finalization logic
- **[FinalizeInvoiceController Usage](controllers/FINALIZE_INVOICE_CONTROLLER_USAGE.md)** - Usage examples
- **[FinalizeInvoiceController Summary](controllers/FINALIZE_INVOICE_CONTROLLER_SUMMARY.md)** - Executive summary
- **[Tariff Controller](controllers/TARIFF_CONTROLLER_COMPLETE.md)** - Tariff management
- **[Meter Reading Update Controller](controllers/METER_READING_UPDATE_CONTROLLER_COMPLETE.md)** - Reading corrections

### Services
- **[BillingService v2](implementation/BILLING_SERVICE_V2_IMPLEMENTATION.md)** - Complete implementation
- **[BillingService Refactoring](implementation/BILLING_SERVICE_REFACTORING.md)** - Refactoring report
- **[Gyvatukas Calculator](implementation/GYVATUKAS_CALCULATOR_IMPLEMENTATION.md)** - Implementation details
- **[Tariff Resolver](implementation/TARIFF_RESOLVER_IMPLEMENTATION.md)** - Tariff selection logic

### Observers
- **[Meter Reading Observer](implementation/METER_READING_OBSERVER_IMPLEMENTATION.md)** - Audit trail
- **[Draft Invoice Recalculation](implementation/DRAFT_INVOICE_RECALCULATION_IMPLEMENTATION.md)** - Auto-recalculation

## Performance

### Optimization Guides
- **[BillingService Performance](performance/BILLING_SERVICE_PERFORMANCE_OPTIMIZATION.md)** - 85% query reduction
- **[Gyvatukas Performance](performance/GYVATUKAS_CALCULATOR_OPTIMIZATION.md)** - 80% faster execution
- **[Tariff Controller Performance](performance/TARIFF_CONTROLLER_PERFORMANCE_OPTIMIZATION.md)** - 90% query reduction
- **[Policy Performance](performance/POLICY_PERFORMANCE_ANALYSIS.md)** - <0.05ms overhead

### Performance Summaries
- **[BillingService Summary](performance/BILLING_SERVICE_PERFORMANCE_SUMMARY.md)** - Executive summary
- **[Gyvatukas Summary](performance/GYVATUKAS_PERFORMANCE_SUMMARY.md)** - Performance metrics
- **[Tariff Controller Summary](performance/TARIFF_CONTROLLER_PERFORMANCE_SUMMARY.md)** - Optimization results

## Security

### Security Audits
- **[BillingService Security](security/BILLING_SERVICE_SECURITY_AUDIT.md)** - Comprehensive audit
- **[Tariff Policy Security](security/TARIFF_POLICY_SECURITY_AUDIT.md)** - Security hardening
- **[Migration Security](security/MIGRATION_SECURITY_AUDIT.md)** - SQL injection prevention

### Security Implementation
- **[BillingService Security Implementation](security/BILLING_SERVICE_SECURITY_IMPLEMENTATION.md)** - Step-by-step guide
- **[Security Implementation Complete](security/SECURITY_IMPLEMENTATION_COMPLETE.md)** - Implementation summary

## Testing

### Test Coverage
- **[Gyvatukas Test Coverage](testing/GYVATUKAS_CALCULATOR_TEST_COVERAGE.md)** - 43 tests, 100% coverage
- **[Meter Reading Observer Tests](testing/METER_READING_OBSERVER_TEST_COVERAGE.md)** - 6 tests, 100% coverage
- **[Tariff Policy Tests](testing/TARIFF_POLICY_TEST_SUMMARY.md)** - 20 tests, 100% coverage

### Test Guides
- **[Testing Guide](guides/TESTING_GUIDE.md)** - Testing approach
- **[Property-Based Testing](testing/PROPERTY_BASED_TESTING.md)** - Property tests
- **[Model Verification](testing/MODEL_VERIFICATION_GUIDE.md)** - Model testing

### Quick References
- **[Gyvatukas Test Quick Reference](testing/GYVATUKAS_CALCULATOR_TEST_QUICK_REFERENCE.md)** - Test scenarios
- **[Meter Reading Observer Quick Reference](testing/METER_READING_OBSERVER_TEST_QUICK_REFERENCE.md)** - Test cases

## Database

### Migrations
- **[Migration Patterns](database/MIGRATION_PATTERNS.md)** - Best practices
- **[Migration Refactoring](database/MIGRATION_REFACTORING_COMPLETE.md)** - Refactoring report
- **[Migration Final Status](database/MIGRATION_FINAL_STATUS.md)** - Status summary

### Database Design
- **[Database Schema](architecture/DATABASE_SCHEMA.md)** - Complete schema
- **[Indexing Strategy](database/INDEXING_STRATEGY.md)** - Performance indexes

## Upgrades

### Framework Upgrades
- **[Laravel 12 & Filament 4 Upgrade](upgrades/LARAVEL_12_FILAMENT_4_UPGRADE.md)** - Complete upgrade guide
- **[Framework Version Test](upgrades/FRAMEWORK_VERSION_TEST.md)** - Version verification

### Migration Guides
- **[Batch Migration Guide](upgrades/BATCH_MIGRATION_GUIDE.md)** - Batch migrations
- **[Upgrade Complete Summary](upgrades/UPGRADE_COMPLETE_SUMMARY.md)** - Upgrade status

## Frontend

### Blade & Components
- **[Frontend Guide](frontend/FRONTEND.md)** - Blade, Tailwind, Alpine
- **[Blade Components](frontend/BLADE_COMPONENTS.md)** - Reusable components
- **[View Composers](frontend/VIEW_COMPOSERS.md)** - Navigation logic

### Filament
- **[Filament Admin Panel](filament/FILAMENT_ADMIN_PANEL.md)** - Admin interface
- **[Filament Resources](filament/FILAMENT_RESOURCES.md)** - Resource configuration
- **[Filament Namespace Consolidation](filament/FILAMENT_NAMESPACE_CONSOLIDATION.md)** - Namespace cleanup

## Routes

### Route Documentation
- **[Routes Implementation](routes/ROUTES_IMPLEMENTATION_COMPLETE.md)** - Complete route documentation
- **[Route Cleanup](routes/ROUTE_CLEANUP.md)** - Route organization

## Specifications

### Feature Specs
- **[Vilnius Utilities Billing](.kiro/specs/2-vilnius-utilities-billing/)** - Complete billing spec
- **[Hierarchical User Management](.kiro/specs/3-hierarchical-user-management/)** - User management spec
- **[Filament Admin Panel](.kiro/specs/4-filament-admin-panel/)** - Admin panel spec
- **[Framework Upgrade](.kiro/specs/1-framework-upgrade/)** - Upgrade spec

## Quick References

### Invoice Management
- **[Invoice Finalization Quick Reference](reference/INVOICE_FINALIZATION_QUICK_REFERENCE.md)** - At-a-glance guide

### Common Tasks
- **[Common Commands](reference/COMMON_COMMANDS.md)** - Frequently used commands
- **[Troubleshooting](reference/TROUBLESHOOTING.md)** - Common issues and solutions

## Changelog

- **[Changelog](CHANGELOG.md)** - All notable changes
- **[Migration Changelog](CHANGELOG_MIGRATION_REFACTORING.md)** - Migration changes

## Status Documents

### Completion Reports
- **[Billing Service Performance Complete](../BILLING_SERVICE_PERFORMANCE_COMPLETE.md)** - Performance optimization
- **[Billing Service Security Complete](../BILLING_SERVICE_SECURITY_COMPLETE.md)** - Security hardening
- **[Meter Reading Update Controller Complete](../METER_READING_UPDATE_CONTROLLER_SPEC_COMPLETE.md)** - Controller implementation
- **[Tariff Controller Implementation Complete](../TARIFF_CONTROLLER_IMPLEMENTATION_COMPLETE.md)** - Tariff management
- **[Final Verification Status](../FINAL_VERIFICATION_STATUS.md)** - Overall status

## Contributing

### Development Guidelines
- Follow Laravel 12 conventions
- Use Pest 3.x for testing
- Maintain 100% test coverage for critical paths
- Document all public APIs
- Use strict typing (`declare(strict_types=1)`)
- Follow PSR-12 coding standards

### Documentation Guidelines
- Keep documentation up-to-date with code changes
- Include code examples in usage guides
- Add architecture diagrams for complex flows
- Cross-reference related documentation
- Update changelog for notable changes

## Support

### Getting Help
- Check the relevant documentation section
- Review the quick reference guides
- Search the changelog for recent changes
- Run the test suite to verify functionality
- Check logs for error details

### Reporting Issues
- Include relevant error messages
- Provide steps to reproduce
- Include environment details
- Reference related documentation
- Attach relevant logs

---

**Maintained by**: Development Team  
**Last Review**: 2025-11-25  
**Next Review**: 2026-02-25
