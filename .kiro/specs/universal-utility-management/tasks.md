# Implementation Plan

## Current Status Analysis

**COMPLETED INFRASTRUCTURE:**
- ✅ Core models: `Meter`, `MeterReading`, `Provider`, `Tariff` with full relationships and scopes
- ✅ Enums: `MeterType` (electricity, water_cold, water_hot, heating), `ServiceType` (electricity, water, heating), `DistributionMethod` (equal, area), `TariffType` (flat, time_of_use)
- ✅ Multi-tenancy: `BelongsToTenant` trait implemented across all models with tenant scoping
- ✅ Filament resources: Complete CRUD interfaces for all existing models
- ✅ Gyvatukas system: Comprehensive `GyvatukasCalculator` with summer/winter calculations, caching, distribution methods, and building-specific factors
- ✅ Billing infrastructure: Tariff management with active date ranges, configuration arrays, and provider relationships
- ✅ Meter reading system: Full audit trail, zone support, consumption calculations, and validation scopes
- ✅ Performance optimizations: Caching, memoization, selective column loading, and batch operations

**CURRENT SYSTEM CAPABILITIES:**
- Full gyvatukas circulation energy calculations for heating systems
- Multi-zone meter support (day/night electricity rates)
- Area-based and equal cost distribution methods
- Comprehensive audit trails for meter reading changes
- Provider-based tariff management with time-based activation
- Tenant-scoped data isolation across all operations

**REMAINING WORK:**
Extend the existing robust infrastructure to support universal utility types beyond the current Lithuanian heating focus, while maintaining full backward compatibility with gyvatukas calculations.

## 1. Universal Service Framework

- [-] 1 Create UtilityService model and migration

  - Create model with configurable attributes (name, unit of measurement, default pricing model, calculation formula)
  - Support global templates (SuperAdmin) and tenant customizations (Admin)
  - Include JSON schema for validation rules and business logic configuration
  - Bridge with existing `ServiceType` enum for backward compatibility
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5_

- [x] 1.2 Create ServiceConfiguration model and migration


  - Property-specific utility service configuration linking to existing `Property` model
  - Support multiple pricing models extending current `TariffType` enum capabilities
  - Include rate schedules (JSON) and leverage existing `DistributionMethod` enum
  - Link to existing `Tariff` model for rate data and `Provider` relationships
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5_

- [x] 1.3 Extend existing Meter model with universal capabilities


  - Add `reading_structure` JSON field for flexible multi-value readings
  - Add `service_configuration_id` foreign key to link meters to universal services
  - Maintain existing `type` (MeterType), `supports_zones`, and all current functionality
  - Add migration to preserve all existing meter data and relationships
  - _Requirements: 4.1, 4.2, 4.3_


- [x] 1.4 Extend existing MeterReading model with universal capabilities

  - Add `reading_values` JSON field to support complex reading structures
  - Add `input_method` enum field (manual, photo_ocr, csv_import, api_integration, estimated)
  - Add `validation_status` enum field (pending, validated, rejected, requires_review)
  - Add `photo_path` and `validated_by` fields for enhanced audit trail
  - Maintain existing `value`, `zone`, `entered_by` fields for backward compatibility
  - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_

- [ ] 1.5 Write property test for universal service framework
  - **Property 1: Universal Service Creation and Configuration**
  - **Validates: Requirements 1.1, 1.2, 1.5, 2.1, 2.2, 2.3, 2.4**

## 2. Enhanced Pricing and Calculation Engine

- [ ] 2.1 Create PricingModel enum extending TariffType capabilities
  - Add TIERED_RATES, HYBRID, CUSTOM_FORMULA to existing FLAT and TIME_OF_USE
  - Include mathematical expression parsing for custom formulas
  - Maintain backward compatibility with existing `TariffType` usage
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 5.5_

- [ ] 2.2 Extend DistributionMethod enum with consumption-based allocation
  - Add BY_CONSUMPTION and CUSTOM_FORMULA to existing EQUAL and AREA methods
  - Include support for different area types (total_area, heated_area, commercial_area)
  - Maintain existing `requiresAreaData()` method and add `requiresConsumptionData()`
  - Preserve all existing gyvatukas distribution functionality
  - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_

- [ ] 2.3 Create UniversalBillingCalculator service
  - Integrate with existing `GyvatukasCalculator` as a specialized calculation engine
  - Support all pricing models: fixed monthly, consumption-based, tiered, hybrid
  - Handle time-of-use pricing extending current zone support in meters
  - Apply seasonal adjustments building on gyvatukas summer/winter logic
  - Maintain existing tariff snapshot functionality for invoice immutability
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

- [ ] 2.4 Enhance GyvatukasCalculator distribution methods
  - Extend existing `distributeCirculationCost()` method with consumption-based allocation
  - Add support for historical consumption averages using existing meter reading data
  - Maintain existing caching and performance optimizations
  - Add different area type support leveraging existing property area data
  - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_

- [ ] 2.5 Write property test for universal billing calculations
  - **Property 4: Billing Calculation Accuracy**
  - **Validates: Requirements 5.1, 5.2, 5.4, 5.5**

- [ ] 2.6 Write property test for enhanced cost distribution
  - **Property 5: Shared Service Cost Distribution**
  - **Validates: Requirements 6.1, 6.2, 6.3, 6.4**

## 3. Service Assignment and Configuration Management

- [ ] 3.1 Create AssignUtilityServiceAction
  - Assign utility services to existing `Property` models with individual configurations
  - Create `ServiceConfiguration` records linking properties to utility services
  - Support pricing overrides with full audit trail using existing audit infrastructure
  - Validate configurations don't conflict with existing meter assignments
  - _Requirements: 3.1, 3.2, 3.3_

- [ ] 3.2 Create ServiceTransitionHandler
  - Handle tenant move-in/move-out scenarios for existing property relationships
  - Generate final meter readings using existing `MeterReading` model and audit system
  - Calculate pro-rated charges using existing tariff and billing infrastructure
  - Support temporary service suspensions while preserving meter and configuration data
  - _Requirements: 12.1, 12.2, 12.3, 12.4, 12.5_

- [ ] 3.3 Create ServiceValidationEngine
  - Define consumption limits and validation rules extending existing meter reading validation
  - Support rate change restrictions using existing tariff active date functionality
  - Include seasonal adjustments building on gyvatukas summer/winter logic
  - Implement data quality checks leveraging existing meter reading audit trail
  - _Requirements: 11.1, 11.2, 11.3, 11.4, 11.5_

- [ ] 3.4 Write property test for service assignment and validation
  - **Property 2: Property Service Assignment with Audit Trail**
  - **Validates: Requirements 3.1, 3.2, 3.3**

## 4. Enhanced Reading Collection and Validation

- [ ] 4.1 Create UniversalReadingCollector service
  - Extend existing `MeterReading` creation to support new input methods
  - Add photo upload with OCR processing using new `photo_path` field
  - Implement CSV import functionality leveraging existing meter and property relationships
  - Add API integration endpoints for external meter systems
  - Handle composite readings using new `reading_values` JSON field while maintaining `value` for backward compatibility
  - _Requirements: 4.1, 4.2, 4.3_

- [ ] 4.2 Enhance existing ReadingValidationEngine
  - Extend existing meter reading validation with new `validation_status` field
  - Build on existing consumption calculation methods (`getConsumption()`)
  - Add support for estimated readings with `is_estimated` flag and true-up calculations
  - Leverage existing audit trail system (`MeterReadingAudit`) for validation history
  - Maintain existing scopes (`forPeriod`, `forZone`, `latest`) while adding validation scopes
  - _Requirements: 4.2, 4.4, 4.5_

- [ ] 4.3 Create MobileReadingInterface (Filament-based)
  - Build mobile-responsive Filament forms for field data collection
  - Implement offline data collection using browser storage with sync to existing models
  - Add camera integration for meter photo capture with automatic reading extraction
  - Use existing Filament resource structure for consistent UI/UX
  - _Requirements: 15.1, 15.2, 15.3, 15.4, 15.5_

- [ ] 4.4 Write property test for enhanced reading validation
  - **Property 3: Multi-Input Reading Validation**
  - **Validates: Requirements 4.1, 4.2, 4.4**

## 5. Enhanced Automated Billing System

- [ ] 5.1 Create AutomatedBillingEngine extending existing billing infrastructure
  - Build on existing invoice generation and tariff management systems
  - Support monthly, quarterly, and custom period schedules using existing date handling
  - Automatically collect readings from existing `MeterReading` model with new input methods
  - Calculate charges using `UniversalBillingCalculator` and existing `GyvatukasCalculator`
  - Handle errors gracefully with existing logging infrastructure and admin notifications
  - _Requirements: 7.1, 7.2, 7.3, 7.5_

- [ ] 5.2 Enhance existing InvoiceSnapshotService
  - Extend existing tariff snapshot functionality to include universal service configurations
  - Preserve calculation methods for historical invoices using existing audit trail system
  - Support partial automation with approval workflows leveraging existing user roles
  - Maintain existing invoice immutability while adding universal service data
  - _Requirements: 7.4, 7.5_

- [ ] 5.3 Write property test for enhanced automated billing
  - **Property 6: Automated Billing Cycle Execution**
  - **Validates: Requirements 7.1, 7.2, 7.3, 7.4**

## 6. Multi-Tenant Data Management (Leveraging Existing Infrastructure)

- ✅ 6.1 Multi-tenant data isolation already implemented
  - Existing `BelongsToTenant` trait provides database-level isolation
  - `TenantScope` automatically scopes queries to current tenant
  - `TenantContext` service handles SuperAdmin tenant switching
  - All models (Meter, MeterReading, Provider, Tariff) already tenant-scoped
  - _Requirements: 10.1, 10.2, 10.3, 10.5 - COMPLETED_

- [ ] 6.2 Extend TenantInitializationService for universal services
  - Build on existing tenant initialization to include universal service templates
  - Set up default utility service configurations for new tenants
  - Initialize universal service configurations alongside existing gyvatukas setup
  - Leverage existing tenant isolation infrastructure
  - _Requirements: 10.4, 10.5_

- [ ] 6.3 Write property test for enhanced tenant isolation
  - **Property 7: Multi-Tenant Data Isolation**
  - Test existing tenant isolation with new universal service models
  - Validate SuperAdmin context switching with universal services
  - **Validates: Requirements 10.1, 10.2, 10.3, 10.4, 10.5**

## 7. Gyvatukas Integration System (Bridge Approach)

- [ ] 7.1 Create GyvatukasUniversalBridge service
  - Create heating utility service configuration that maps to existing gyvatukas logic
  - Bridge existing `GyvatukasCalculator` methods with universal billing engine
  - Preserve all existing calculation accuracy and caching optimizations
  - Enable universal features while maintaining gyvatukas backward compatibility
  - _Requirements: 13.1, 13.2, 13.3_

- [ ] 7.2 Implement HeatingServiceConfiguration
  - Create specialized service configuration for Lithuanian heating systems
  - Map existing gyvatukas summer/winter logic to universal pricing models
  - Preserve existing distribution methods (equal, area) with universal extensions
  - Maintain existing building-specific factors and efficiency calculations
  - _Requirements: 13.2, 13.4_

- [ ] 7.3 Write property test for gyvatukas bridge accuracy
  - **Property 10: Gyvatukas Integration Accuracy**
  - Validate that bridge calculations match existing gyvatukas results exactly
  - Test summer/winter calculation preservation through universal system
  - **Validates: Requirements 13.2, 13.3, 13.4**

## 8. External Integration Layer

- [ ] 8.1 Create ExternalIntegrationManager
  - Support REST APIs, file imports, and webhook notifications for utility data
  - Handle rate data import with validation against existing tariff system
  - Synchronize meter readings with existing `MeterReading` model and audit trail
  - Integrate with existing provider system for external utility company data
  - _Requirements: 14.1, 14.2, 14.3_

- [ ] 8.2 Implement IntegrationResilienceHandler
  - Build on existing caching infrastructure for offline operation
  - Queue updates using existing job system with retry mechanisms
  - Provide mapping tools for external data to existing model structures
  - Handle data format differences while preserving existing audit trails
  - _Requirements: 14.4, 14.5_

- [ ] 8.3 Write property test for integration resilience
  - **Property 11: External Integration Resilience**
  - Test external system failures with existing caching fallbacks
  - Validate data synchronization with existing audit trail system
  - **Validates: Requirements 14.1, 14.2, 14.3, 14.4, 14.5**

## 9. User Interface and Experience Enhancement

- [ ] 9.1 Enhance existing tenant dashboard with universal services
  - Extend existing tenant views to display universal utility consumption
  - Build on existing invoice display system for multi-utility bills
  - Add usage trend analysis using existing meter reading data
  - Support multiple utility types while maintaining existing UX patterns
  - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5_

- [ ] 9.2 Create MobileReadingInterface using Filament mobile patterns
  - Build mobile-responsive Filament forms for universal meter reading collection
  - Implement offline data collection with sync to existing `MeterReading` model
  - Add camera integration for meter photo capture with OCR processing
  - Use existing Filament resource patterns for consistent mobile UX
  - _Requirements: 15.1, 15.2, 15.3, 15.4, 15.5_

- [ ] 9.3 Write property test for mobile synchronization
  - **Property 12: Mobile Offline Synchronization**
  - Test offline data collection with existing model synchronization
  - Validate GPS location verification with existing meter data
  - **Validates: Requirements 15.2, 15.4**

## 10. Audit and Reporting System Enhancement

- [ ] 10.1 Extend existing audit system for universal services
  - Build on existing `MeterReadingAudit` system for universal service auditing
  - Enhance existing calculation logging with universal billing formulas
  - Extend existing performance tracking with universal service metrics
  - Maintain existing audit trail patterns for consistency
  - _Requirements: 9.1, 9.2, 9.3, 9.4_

- [ ] 10.2 Create UniversalComplianceReportGenerator
  - Generate reports for multiple utility types using existing report infrastructure
  - Extend existing filtering capabilities for universal service data
  - Support export formats compatible with existing compliance systems
  - Build on existing provider and tariff reporting capabilities
  - _Requirements: 9.2, 9.5_

## 11. Filament Admin Interface Enhancement

- [ ] 11.1 Create UtilityServiceResource (new)
  - CRUD operations for universal utility service management
  - Support global template creation and tenant customization
  - Include validation rules and business logic configuration
  - Follow existing Filament resource patterns for consistency
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5_

- [ ] 11.2 Create ServiceConfigurationResource (new)
  - Property-specific service configuration management
  - Support all pricing models extending existing tariff management
  - Include rate schedule and effective date management
  - Integrate with existing property and meter management workflows
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5_

- [ ] 11.3 Enhance existing MeterResource for universal capabilities
  - Add `reading_structure` JSON field for flexible multi-value readings
  - Add `service_configuration_id` foreign key for universal service linking
  - Support multiple utility types beyond current electricity/water/heating
  - Maintain all existing functionality and backward compatibility
  - _Requirements: 4.1, 4.2, 4.3_

- [ ] 11.4 Enhance existing MeterReadingResource for universal capabilities
  - Add support for new input methods (photo OCR, CSV import, API integration)
  - Add `reading_values` JSON field while maintaining existing `value` field
  - Add `validation_status` and `photo_path` fields for enhanced audit trail
  - Maintain all existing validation, correction, and audit trail functionality
  - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_

## 12. Final Integration and Testing

- [ ] 12.1 Integrate universal system with existing infrastructure
  - Update existing controllers to support universal services alongside gyvatukas
  - Extend existing billing workflows to handle multiple utility types
  - Ensure seamless transition with zero downtime for existing users
  - Maintain full backward compatibility with all existing functionality
  - _Requirements: 13.1, 13.2, 13.3_

- [ ] 12.2 Implement comprehensive test suite
  - Unit tests for all new services and value objects
  - Integration tests for universal service workflows with existing systems
  - Performance tests for large-scale operations with existing optimizations
  - Regression tests to ensure existing gyvatukas functionality unchanged
  - _Requirements: All requirements validation_

- [ ] 12.3 Write property test for service configuration validation
  - **Property 8: Service Configuration Validation and Business Rules**
  - Test validation rules with existing meter and property relationships
  - Validate business logic with existing tariff and provider systems
  - **Validates: Requirements 11.1, 11.2, 11.3, 11.4, 11.5**

- [ ] 12.4 Write property test for tenant lifecycle management
  - **Property 9: Tenant Lifecycle Management**
  - Test tenant transitions with existing property and meter relationships
  - Validate service lifecycle with existing billing and audit systems
  - **Validates: Requirements 12.1, 12.2, 12.3, 12.4, 12.5**

- [ ] 12.5 Final system validation and deployment preparation
  - Validate all existing gyvatukas calculations remain unchanged
  - Ensure all new universal features work alongside existing functionality
  - Verify performance benchmarks meet or exceed existing system performance
  - Complete documentation and deployment guides for universal system features
  - _Requirements: All requirements validation_