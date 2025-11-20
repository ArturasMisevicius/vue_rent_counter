# Implementation Plan

- [x] 1. Initialize Laravel project and configure SQLite with WAL mode
  - Create new Laravel 11/12 project
  - Configure database/database.sqlite file
  - Set DB_CONNECTION=sqlite and DB_FOREIGN_KEYS=true in .env
  - Enable WAL mode in database configuration
  - Install required packages: Pest PHP, spatie/laravel-backup
  - _Requirements: 9.1, 9.2, 12.5_

- [x] 2. Create database migrations for core domain models
  - Create migration for users table with role enum (admin, manager, tenant)
  - Create migration for buildings table with gyvatukas fields
  - Create migration for properties table with type enum and tenant_id
  - Create migration for tenants table (renters) with lease dates
  - Create migration for providers table with service_type enum
  - Create migration for tariffs table with JSON configuration column
  - Create migration for meters table with type enum and supports_zones
  - Create migration for meter_readings table with zone field
  - Create migration for meter_reading_audits table
  - Create migration for invoices table with status enum
  - Create migration for invoice_items table with meter_reading_snapshot JSON
  - Add all foreign key constraints with appropriate cascade rules
  - Add indexes for performance (meter_id+reading_date, tenant_id, etc.)
  - _Requirements: 1.1, 2.1, 3.1, 5.1, 7.1, 8.1, 9.4, 9.5_

- [x] 3. Create Eloquent models with relationships and casts
  - Create User model with role enum cast
  - Create Building model with gyvatukas_summer_average decimal cast
  - Create Property model with PropertyType enum cast
  - Create Tenant model (renter) with date casts
  - Create Provider model with ServiceType enum cast
  - Create Tariff model with array cast for configuration and datetime casts
  - Create Meter model with MeterType enum cast and supports_zones boolean
  - Create MeterReading model with datetime and decimal casts
  - Create MeterReadingAudit model with decimal casts
  - Create Invoice model with InvoiceStatus enum cast and date casts
  - Create InvoiceItem model with decimal casts and array cast for snapshot
  - Define all relationships (BelongsTo, HasMany, HasManyThrough)
  - _Requirements: 1.1, 2.1, 3.1, 5.1, 7.1, 8.1_

- [x] 4. Implement multi-tenancy with Global Scopes
  - Create TenantScope class that adds WHERE tenant_id = ? to queries
  - Apply TenantScope to Property, Meter, MeterReading, Invoice, Tenant models
  - Create EnsureTenantContext middleware to validate session tenant_id
  - Modify authentication to set tenant_id in session on login
  - _Requirements: 7.1, 7.2, 7.3, 7.5_

- [x] 4.1 Write property test for tenant data isolation
  - **Property 15: Tenant data isolation**
  - **Validates: Requirements 7.1, 7.2, 7.3, 7.5**

- [x] 5. Create Form Requests for validation
  - Create StoreMeterReadingRequest with monotonicity and temporal validation
  - Create StoreTariffRequest with JSON schema and time-of-use zone validation
  - Create UpdateMeterReadingRequest for corrections
  - Create FinalizeInvoiceRequest
  - _Requirements: 1.2, 1.3, 2.2_

- [x] 6. Implement TariffResolver service
  - Create TariffResolver class with resolve() method
  - Implement temporal tariff selection logic (active_from, active_until)
  - Implement calculateCost() method for flat and time-of-use tariffs
  - Add determineZone() helper for day/night/weekend logic
  - _Requirements: 2.3, 2.4, 2.5_

- [x] 7. Implement GyvatukasCalculator service
  - Create GyvatukasCalculator class
  - Implement isHeatingSeason() method (Oct-Apr check)
  - Implement calculateSummerGyvatukas() with Q_circ = Q_total - (V_water × c × ΔT) formula
  - Implement calculateWinterGyvatukas() using stored summer average
  - Implement distributeCirculationCost() for equal or area-based distribution
  - Add calculateSummerAverage() method for Building model
  - _Requirements: 4.1, 4.2, 4.3, 4.5_

- [x] 8. Implement BillingService for invoice generation
  - Create BillingService class
  - Implement generateInvoice() method that collects meter readings for period
  - Integrate TariffResolver to snapshot current tariff rates
  - Integrate GyvatukasCalculator for heating/hot water
  - Implement water bill calculation with supply, sewage, and fixed fee
  - Create InvoiceItems with snapshotted prices and meter_reading_snapshot
  - Calculate total_amount and set status to 'draft'
  - Implement finalizeInvoice() method that sets finalized_at and makes immutable
  - _Requirements: 3.1, 3.2, 3.3, 5.1, 5.2, 5.5_

- [x] 9. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 10. Implement audit trail with Eloquent Observers
  - Create MeterReadingObserver class
  - Implement updating() method to create MeterReadingAudit records
  - Store old_value, new_value, change_reason, changed_by_user_id
  - Register observer in AppServiceProvider
  - _Requirements: 8.1, 8.2_

- [x] 11. Implement draft invoice recalculation on reading correction
  - Add logic to MeterReadingObserver to find affected draft invoices
  - Recalculate invoice totals when readings change
  - Prevent recalculation for finalized invoices
  - _Requirements: 8.3_

- [x] 12. Create authorization Policies for RBAC
  - Create TariffPolicy with viewAny, create, update, delete methods
  - Create InvoicePolicy with view, create, finalize methods
  - Create MeterReadingPolicy with create, update methods
  - Implement role-based logic (admin: all, manager: invoices, tenant: view only)
  - Register policies in AuthServiceProvider
  - _Requirements: 11.1, 11.2, 11.3, 11.4_

- [x] 13. Create controllers for meter reading management
  - Create MeterReadingController with store() method
  - Validate input using StoreMeterReadingRequest
  - Store reading with entered_by user ID and timestamp
  - Handle multi-zone readings for electricity meters
  - Create MeterReadingUpdateController for corrections
  - Return JSON response for API endpoints
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5_

- [x] 14. Create controllers for tariff management
  - Create TariffController with index, store, update, destroy methods
  - Authorize using TariffPolicy
  - Validate tariff configuration JSON
  - Return tariff list for provider selection
  - _Requirements: 2.1, 2.2, 11.2_

- [x] 15. Create controllers for invoice management
  - Create InvoiceController with index, show, store methods
  - Integrate BillingService for invoice generation
  - Create FinalizeInvoiceController for finalization
  - Filter invoices by tenant_id (automatic via Global Scope)
  - Support property filtering for multi-property tenants
  - _Requirements: 5.1, 5.2, 5.5, 6.1, 6.5_

- [x] 16. Create Blade components for meter reading form
  - Create x-meter-reading-form component with Alpine.js
  - Implement dynamic provider/tariff selection without page refresh
  - Add real-time validation for reading monotonicity
  - Add client-side charge preview calculation
  - Display previous reading and consumption
  - Include Alpine.js via CDN in layout
  - _Requirements: 10.1, 10.2, 10.3_

- [x] 17. Create Blade components for invoice display




  - Create x-invoice-summary component
  - Display itemized breakdown by utility type
  - Show consumption amount and rate applied for each item
  - Display chronologically ordered consumption history
  - Add property filter dropdown for multi-property tenants
  - _Requirements: 6.2, 6.3, 6.4, 6.5_

- [x] 18. Enhance Blade views with Alpine.js interactivity





  - Enhance dashboard views with role-based content (@can directives)
  - Enhance meter readings views with Alpine.js reactive forms
  - Enhance tariffs views with JSON configuration editor (admin only)
  - Enhance invoices views with itemized breakdown display
  - Create consumption history view for tenants
  - Apply TailwindCSS for styling
  - _Requirements: 10.5, 11.5_

- [x] 19. Implement scheduled task for summer average calculation
  - Create CalculateSummerAverageCommand artisan command
  - Schedule to run at start of heating season (October 1st)
  - Calculate average gyvatukas for May-September for each building
  - Store in building->gyvatukas_summer_average
  - Update building->gyvatukas_last_calculated
  - Register command in routes/console.php schedule
  - _Requirements: 4.4_

- [x] 20. Configure automated backup scheduling




  - Add backup schedule to routes/console.php using Schedule::command('backup:run')->daily()
  - Add cleanup schedule to routes/console.php using Schedule::command('backup:clean')->daily()
  - Verify backup retention policy in config/backup.php (keep_all_backups_for_days: 7, keep_daily_backups_for_days: 16)
  - _Requirements: 12.1, 12.3, 12.4_

- [x] 21. Create database seeders with realistic Lithuanian data
  - Create ProvidersSeeder (Ignitis, Vilniaus Vandenys, Vilniaus Energija)
  - Create TariffsSeeder with realistic rates and time-of-use configurations
  - Create UsersSeeder with admin, manager, and tenant users
  - Create BuildingsSeeder with Vilnius addresses
  - Create PropertiesSeeder with apartments and houses
  - Create MetersSeeder with Lithuanian serial number format
  - _Requirements: 2.1, 3.1_

- [x] 22. Create model factories for testing
  - Create UserFactory with role states
  - Create BuildingFactory with gyvatukas fields
  - Create PropertyFactory with type states
  - Create TenantFactory with lease dates
  - Create ProviderFactory with service_type states
  - Create TariffFactory with Ignitis, VV, VE states
  - Create MeterFactory with type and zone support
  - Create MeterReadingFactory with realistic values
  - Create InvoiceFactory with status states
  - _Requirements: All (for testing)_

- [x] 23. Set up routes with middleware protection
  - Define API routes for meter readings (manager, admin)
  - Define web routes for invoices (all roles, filtered by policy)
  - Define web routes for tariffs (admin only)
  - Apply EnsureTenantContext middleware to all tenant-scoped routes
  - Apply auth middleware to all protected routes
  - _Requirements: 7.1, 11.1_

- [x] 24. Write remaining property-based tests for correctness properties




- [x] 24.1 Write property test for meter reading monotonicity







  - **Property 1: Meter reading monotonicity**
  - **Validates: Requirements 1.2**

- [x] 24.2 Write property test for meter reading temporal validity






  - **Property 2: Meter reading temporal validity**
  - **Validates: Requirements 1.3**
-

- [x] 24.3 Write property test for meter reading audit trail completeness





  - **Property 3: Meter reading audit trail completeness**
  - **Validates: Requirements 1.4**

- [x] 24.4 Write property test for multi-zone meter reading acceptance






  - **Property 4: Multi-zone meter reading acceptance**
  - **Validates: Requirements 1.5**
-

- [x] 24.5 Write property test for tariff configuration JSON round-trip





  - **Property 5: Tariff configuration JSON round-trip**
  - **Validates: Requirements 2.1**

- [x] 24.6 Write property test for time-of-use zone validation

  - **Property 6: Time-of-use zone validation**
  - **Validates: Requirements 2.2**

- [x] 24.7 Write property test for tariff temporal selection

  - **Property 7: Tariff temporal selection**
  - **Validates: Requirements 2.3, 2.4**

- [x] 24.8 Write property test for weekend tariff rate application

  - **Property 8: Weekend tariff rate application**
  - **Validates: Requirements 2.5**

- [x] 24.9 Write property test for water bill component calculation

  - **Property 9: Water bill component calculation**
  - **Validates: Requirements 3.1, 3.2**

- [x] 24.10 Write property test for property type tariff differentiation

  - **Property 10: Property type tariff differentiation**
  - **Validates: Requirements 3.3**

- [x] 24.11 Write property test for invoice immutability after finalization

  - **Property 11: Invoice immutability after finalization**
  - **Validates: Requirements 5.1, 5.2, 5.3, 5.4, 5.5**

- [x] 24.12 Write property test for summer gyvatukas calculation formula

  - **Property 12: Summer gyvatukas calculation formula**
  - **Validates: Requirements 4.1, 4.3**

- [x] 24.13 Write property test for winter gyvatukas norm application

  - **Property 13: Winter gyvatukas norm application**
  - **Validates: Requirements 4.2**

- [x] 24.14 Write property test for circulation cost distribution

  - **Property 14: Circulation cost distribution**
  - **Validates: Requirements 4.5**

- [x] 24.15 Write property test for tenant account initialization

  - **Property 16: Tenant account initialization**
  - **Validates: Requirements 7.4**

- [x] 24.16 Write property test for meter reading modification audit

  - **Property 17: Meter reading modification audit**
  - **Validates: Requirements 8.1, 8.2**

- [x] 24.17 Write property test for draft invoice recalculation on reading correction

  - **Property 18: Draft invoice recalculation on reading correction**
  - **Validates: Requirements 8.3**

- [x] 24.18 Write property test for foreign key constraint enforcement

  - **Property 19: Foreign key constraint enforcement**
  - **Validates: Requirements 9.4, 9.5**

- [x] 24.19 Write property test for client-side charge preview calculation

  - **Property 20: Client-side charge preview calculation**
  - **Validates: Requirements 10.3**

- [x] 24.20 Write property test for role-based resource access control

  - **Property 21: Role-based resource access control**
  - **Validates: Requirements 11.1**

- [x] 24.21 Write property test for tenant role data access restriction

  - **Property 22: Tenant role data access restriction**
  - **Validates: Requirements 11.4**

- [x] 24.22 Write property test for backup retention policy enforcement

  - **Property 23: Backup retention policy enforcement**
  - **Validates: Requirements 12.4**

- [x] 24.23 Write property test for invoice itemization completeness

  - **Property 24: Invoice itemization completeness**
  - **Validates: Requirements 6.2, 6.4**

- [x] 24.24 Write property test for consumption history chronological ordering

  - **Property 25: Consumption history chronological ordering**
  - **Validates: Requirements 6.3**

- [x] 24.25 Write property test for multi-property filtering

  - **Property 26: Multi-property filtering**
  - **Validates: Requirements 6.5**

