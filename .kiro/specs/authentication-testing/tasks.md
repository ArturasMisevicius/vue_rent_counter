# Implementation Plan

- [ ] 1. Create test users seeder with known credentials
  - Create TestUsersSeeder class in database/seeders
  - Create admin user (admin@test.com, password, tenant_id=1)
  - Create manager users for tenant 1 and 2 (manager@test.com, manager2@test.com)
  - Create tenant users for both tenants (tenant@test.com, tenant2@test.com, tenant3@test.com)
  - Hash passwords using Hash::make('password')
  - Assign appropriate UserRole enum values
  - _Requirements: 1.1, 1.2, 1.3, 1.4_

- [ ] 1.1 Write property test for test user tenant assignment
  - **Property 1: Test user tenant assignment**
  - **Validates: Requirements 1.4**

- [ ] 2. Create test buildings seeder with realistic addresses
  - Create TestBuildingsSeeder class
  - Create 2 buildings for tenant 1 with Vilnius addresses
  - Create 1 building for tenant 2
  - Set gyvatukas_summer_average and gyvatukas_last_calculated fields
  - Set total_apartments for each building
  - _Requirements: 5.1_

- [ ] 3. Create test properties seeder
  - Create TestPropertiesSeeder class
  - Create 6 apartments for tenant 1 buildings
  - Create 1 standalone house for tenant 1
  - Create 3 apartments for tenant 2 building
  - Mix of PropertyType::APARTMENT and PropertyType::HOUSE
  - Set realistic area_sqm values
  - Link apartments to buildings, house has null building_id
  - _Requirements: 5.1_

- [ ] 4. Create test tenants (renters) seeder
  - Create TestTenantsSeeder class
  - Create tenant records linked to properties
  - Set lease_start and lease_end dates
  - Link tenant email to user email for authentication
  - _Requirements: 5.1_

- [ ] 5. Create test meters seeder
  - Create TestMetersSeeder class
  - For each property, create electricity meter (supports_zones=true)
  - For each property, create water_cold meter
  - For each property, create water_hot meter
  - For apartments in buildings, create heating meter
  - Generate unique serial numbers (EL-XXXXXX, WC-XXXXXX, WH-XXXXXX, HT-XXXXXX)
  - Set installation_date to 2 years ago
  - _Requirements: 5.2_


- [ ] 6. Create test meter readings seeder
  - Create TestMeterReadingsSeeder class
  - For each meter, create readings for last 3 months
  - For electricity meters, create separate day and night zone readings
  - Increment values realistically based on meter type
  - Set entered_by to manager user ID
  - Ensure monotonically increasing values
  - _Requirements: 5.3_

- [ ] 6.1 Write property test for meter reading storage completeness


- [ ] 7. Create test tariffs seeder
  - Create TestTariffsSeeder class in database/seeders
  - Create Ignitis tariff with time-of-use configuration (day/night rates)
  - Create Vilniaus Vandenys tariff with flat rates (supply, sewage, fixed fee)
  - Create Vilniaus Energija tariff with flat heating rate
  - Set active_from to 1 year ago, active_until to null
  - Use realistic Lithuanian rates
  - _Requirements: 5.4_

- [ ] 8. Create test invoices seeder
  - Create TestInvoicesSeeder class in database/seeders
  - For each tenant, create draft invoice for current month
  - For each tenant, create finalized invoice for last month
  - For each tenant, create paid invoice for 2 months ago
  - Create invoice items for each invoice with realistic consumption
  - Snapshot tariff rates in invoice_items
  - Calculate total_amount from invoice items
  - _Requirements: 5.5_
-

- [ ] 8.1 Write property test for invoice itemization by utility type

-

- [ ] 8.2 Write property test for tariff rate snapshotting


- [ ] 9. Create master test database seeder
  - Create TestDatabaseSeeder class
  - Call seeders in correct order (providers, users, buildings, properties, tenants, meters, readings, tariffs, invoices)
  - Wrap in database transaction for rollback on failure
  - Add error handling and logging
  - _Requirements: 11.3, 11.4_



- [ ] 11. Update TestDatabaseSeeder to call TestTariffsSeeder and TestInvoicesSeeder
  - Uncomment the calls to TestTariffsSeeder and TestInvoicesSeeder in TestDatabaseSeeder
  - Verify seeding order is correct
  - _Requirements: 11.3, 11.4_

- [ ] 12. Checkpoint - Verify test data creation
  - Run php artisan test:setup --fresh
  - Verify all test users are created
  - Verify buildings, properties, meters are created
  - Verify meter readings span 3+ months
  - Verify tariffs for all providers exist
  - Verify invoices in all states exist
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 13. Write authentication tests
  - Create AuthenticationTest.php in tests/Feature
  - Test login with valid admin credentials redirects to /admin/dashboard
  - Test login with valid manager credentials redirects to /manager/dashboard
  - Test login with valid tenant credentials redirects to /tenant/dashboard
  - Test login with invalid credentials returns error
  - Test logout clears session
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5_



-

- [ ] 17. Write multi-tenancy isolation tests for invoices
  - Create InvoiceMultiTenancyTest.php in tests/Feature
  - Test tenant sees only their own invoices
  - Test tenant cannot access another tenant's invoice (404)
  - Test admin can see invoices from all tenants
  - _Requirements: 4.3, 4.4, 4.5_
-

- [ ] 17.1 Write property test for tenant invoice isolation





  - **Property 6: Tenant invoice isolation**
  - **Validates: Requirements 4.3**




- [ ] 17.2 Write property test for cross-tenant invoice access prevention








  - **Property 7: Cross-tenant invoice access prevention**
  - **Validates: Requirements 4.4**

- [ ] 18. Write meter reading validation tests
  - Create MeterReadingValidationTest.php in tests/Feature
  - Test valid reading is stored with timestamp and user reference
  - Test reading lower than previous is rejected
  - Test reading with future date is rejected
  - Test multi-zone meter accepts separate zone readings
  - Test audit trail is created for readings
  - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_
- [ ] 18.1 Write property test for meter reading monotonicity enforcement

  - **Property 10: Meter reading monotonicity enforcement**
  - **Validates: Requirements 6.2**

- [ ] 18.2 Write property test for meter reading temporal validation
  - **Property 11: Meter reading temporal validation**
  - **Validates: Requirements 6.3**


- [ ] 18.3 Write property test for multi-zone meter reading support
  - **Property 12: Multi-zone meter reading support**
  - **Validates: Requirements 6.4**
- [ ] 18.4 Write property test for meter reading audit trail creation


- [ ] 18.4 Write property test for meter reading audit trail creation


  - **Property 13: Meter reading audit trail creation**
  - **Validates: Requirements 6.5**

- [ ] 19. Write invoice generation tests
  - Create InvoiceGenerationTest.php in tests/Feature
  - Test invoice is calculated from meter readings and tariffs
  - Test invoice items are created for each utility type
  - Test tariff rates are snapshotted in invoice_items
  - Test finalized invoice cannot be modified
  - Test finalized invoice is not recalculated when tariffs change
  - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5_
-

- [ ] 19.1 Write property test for invoice calculation from readings

  - **Property 14: Invoice calculation from readings**
  - **Validates: Requirements 7.1**

- [ ] 19.2 Write property test for invoice immutability after finalization
  - **Property 17: Invoice immutability after finalization**
  - **Validates: Requirements 7.4**

- [ ] 19.3 Write property test for finalized invoice tariff independence


  - **Property 18: Finalized invoice tariff independence**
  - **Validates: Requirements 7.5**

- [ ] 20. Write tariff configuration validation tests
  - Create TariffConfigurationTest.php in tests/Feature
  - Test time-of-use zones cannot overlap
  - Test time-of-use zones must cover all 24 hours
  - Test tariff selection based on billing date
  - Test most recent tariff is selected when multiple are active
  - Test weekend rates are applied correctly
  - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5_

- [ ] 20.1 Write property test for time-of-use zone overlap validation






  - **Property 19: Time-of-use zone overlap validation**
  - **Validates: Requirements 8.1**

- [ ] 20.2 Write property test for time-of-use zone coverage validation

  - **Property 20: Time-of-use zone coverage validation**
  - **Validates: Requirements 8.2**
-

- [ ] 20.3 Write property test for tariff temporal selection





  - **Property 21: Tariff temporal selection**
  - **Validates: Requirements 8.3**

- [ ] 20.4 Write property test for tariff precedence with overlaps





  - **Property 22: Tariff precedence with overlaps**
  - **Validates: Requirements 8.4**


- [ ] 20.5 Write property test for weekend tariff rate application





  - **Property 23: Weekend tariff rate application**
  - **Validates: Requirements 8.5**

- [ ] 21. Write gyvatukas calculation tests
  - Create GyvatukasCalculationTest.php in tests/Feature
  - Test summer gyvatukas uses formula Q_circ = Q_total - (V_water × c × ΔT)
  - Test winter gyvatukas uses stored summer average
  - Test summer average is calculated and stored at season start
  - Test circulation costs are distributed correctly (equal or by area)
  - Test gyvatukas appears as separate invoice item
  - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5_
- [ ] 21.1 Write property test for summer gyvatukas calculation formula


- [ ] 21.1 Write property test for summer gyvatukas calculation formula


  - **Property 24: Summer gyvatukas calculation formula**
  - **Validates: Requirements 9.1**

- [ ] 21.2 Write property test for winter gyvatukas norm application




  - **Property 25: Winter gyvatukas norm application**
  - **Validates: Requirements 9.2**

- [ ] 21.3 Write property test for circulation cost distribution




  - **Property 26: Circulation cost distribution**
  - **Validates: Requirements 9.4**

- [ ] 21.4 Write property test for gyvatukas invoice itemization




  - **Property 27: Gyvatukas invoice itemization**
  - **Validates: Requirements 9.5**

- [ ] 22. Create testing documentation
  - Create TESTING_GUIDE.md in project root
  - Document quick start with test:setup command
  - Create test user credentials reference table
  - Document test scenarios for each role (Admin, Manager, Tenant)
  - Include API testing examples with curl commands
  - Add common issues and troubleshooting section
  - Include test data reference (IDs, serial numbers, etc.)
  - _Requirements: 12.1, 12.2, 12.3, 12.4, 12.5_

- [ ] 23. Add API endpoints for testing
  - Create MeterApiController with last-reading endpoint
  - Create API route for getting last meter reading (GET /api/meters/{id}/last-reading)
  - Create API route for submitting meter reading (POST /api/meter-readings)
  - Create ProviderApiController with properties endpoints
  - Create API route for listing properties (GET /api/properties)
  - Create API route for getting property details (GET /api/properties/{id})
  - Add authentication middleware to all API routes
  - Add tenant scope filtering to API routes
  - Return JSON responses with appropriate status codes
  - _Requirements: 12.5_

- [ ] 24. Enhance login controller with session management
  - Verify tenant_id is set in session on successful login
  - Add session regeneration for security
  - Add remember me functionality
  - Add intended URL preservation for redirects
  - _Requirements: 2.1, 10.5_

- [ ] 25. Create test helper methods




  - Create TestHelpers trait in tests/TestCase.php
  - Add actingAsAdmin() method to authenticate as admin
  - Add actingAsManager($tenantId) method to authenticate as manager
  - Add actingAsTenant($tenantId) method to authenticate as tenant
  - Add createTestProperty($tenantId) method for quick property creation
  - Add createTestMeterReading($meterId, $value) method
  - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5_

- [ ] 26. Write property-based tests for authentication testing spec (Properties 1-13 completed)

- [ ] 26.1 Write property test for test user tenant assignment
  - **Property 1: Test user tenant assignment**
  - **Validates: Requirements 1.4**
  - Test that all test users created by seeder have non-null tenant_id
  - Run 100+ iterations with different seeder executions

- [ ] 26.2 Write property test for valid credentials authentication
  - **Property 2: Valid credentials authentication**
  - **Validates: Requirements 2.1**
  - Test that any user with valid credentials successfully authenticates
  - Run 100+ iterations with randomly generated valid users

- [ ] 26.3 Write property test for invalid credentials rejection
  - **Property 3: Invalid credentials rejection**
  - **Validates: Requirements 2.2**
  - Test that any authentication attempt with invalid credentials fails
  - Run 100+ iterations with randomly generated invalid credentials

- [ ] 26.4 Write property test for manager property isolation
  - **Property 4: Manager property isolation**
  - **Validates: Requirements 4.1**
  - Test that any manager user only sees properties from their tenant
  - Run 100+ iterations with randomly generated managers and properties

- [ ] 26.5 Write property test for cross-tenant property access prevention
  - **Property 5: Cross-tenant property access prevention**
  - **Validates: Requirements 4.2**
  - Test that any manager attempting to access cross-tenant property gets 404
  - Run 100+ iterations with randomly generated cross-tenant access attempts

- [ ] 26.6 Write property test for tenant invoice isolation
  - **Property 6: Tenant invoice isolation**
  - **Validates: Requirements 4.3**
  - Test that any tenant user only sees their own invoices
  - Run 100+ iterations with randomly generated tenants and invoices

- [ ] 26.7 Write property test for cross-tenant invoice access prevention
  - **Property 7: Cross-tenant invoice access prevention**
  - **Validates: Requirements 4.4**
  - Test that any tenant attempting to access another tenant's invoice gets 404
  - Run 100+ iterations with randomly generated cross-tenant invoice access attempts

- [ ] 26.8 Write property test for complete meter coverage
  - **Property 8: Complete meter coverage**
  - **Validates: Requirements 5.2**
  - Test that any property has all applicable meter types
  - Run 100+ iterations with randomly generated properties

- [ ] 26.9 Write property test for meter reading storage completeness
  - **Property 9: Meter reading storage completeness**
  - **Validates: Requirements 6.1**
  - Test that any stored meter reading has timestamp and user reference
  - Run 100+ iterations with randomly generated meter readings

- [ ] 26.10 Write property test for meter reading monotonicity enforcement
  - **Property 10: Meter reading monotonicity enforcement**
  - **Validates: Requirements 6.2**
  - Test that any reading lower than previous is rejected
  - Run 100+ iterations with randomly generated decreasing readings

- [ ] 26.11 Write property test for meter reading temporal validation
  - **Property 11: Meter reading temporal validation**
  - **Validates: Requirements 6.3**
  - Test that any reading with future date is rejected
  - Run 100+ iterations with randomly generated future dates

- [ ] 26.12 Write property test for multi-zone meter reading support
  - **Property 12: Multi-zone meter reading support**
  - **Validates: Requirements 6.4**
  - Test that any multi-zone meter accepts separate zone readings
  - Run 100+ iterations with randomly generated zone readings

- [ ] 26.13 Write property test for meter reading audit trail creation
  - **Property 13: Meter reading audit trail creation**
  - **Validates: Requirements 6.5**
  - Test that any stored reading creates audit trail
  - Run 100+ iterations with randomly generated readings

- [ ]* 26.14 Write property test for invoice calculation from readings
  - **Property 14: Invoice calculation from readings**
  - **Validates: Requirements 7.1**
  - Test that any invoice is calculated from meter readings and tariffs
  - Run 100+ iterations with randomly generated readings and tariffs

- [ ]* 26.15 Write property test for invoice itemization by utility type
  - **Property 15: Invoice itemization by utility type**
  - **Validates: Requirements 7.2**
  - Test that any invoice contains items for each utility type with consumption
  - Run 100+ iterations with randomly generated consumption data

- [ ]* 26.16 Write property test for tariff rate snapshotting
  - **Property 16: Tariff rate snapshotting**
  - **Validates: Requirements 7.3**
  - Test that any invoice items contain snapshotted tariff rates
  - Run 100+ iterations with randomly generated invoices

- [ ]* 26.17 Write property test for invoice immutability after finalization
  - **Property 17: Invoice immutability after finalization**
  - **Validates: Requirements 7.4**
  - Test that any finalized invoice cannot be modified
  - Run 100+ iterations with randomly generated finalized invoices

- [ ]* 26.18 Write property test for finalized invoice tariff independence
  - **Property 18: Finalized invoice tariff independence**
  - **Validates: Requirements 7.5**
  - Test that any finalized invoice remains unchanged when tariffs change
  - Run 100+ iterations with randomly generated tariff changes

- [ ]* 26.19 Write property test for time-of-use zone overlap validation
  - **Property 19: Time-of-use zone overlap validation**
  - **Validates: Requirements 8.1**
  - Test that any tariff with overlapping zones is rejected
  - Run 100+ iterations with randomly generated overlapping zones

- [ ]* 26.20 Write property test for time-of-use zone coverage validation
  - **Property 20: Time-of-use zone coverage validation**
  - **Validates: Requirements 8.2**
  - Test that any tariff with incomplete 24-hour coverage is rejected
  - Run 100+ iterations with randomly generated incomplete zones

- [ ]* 26.21 Write property test for tariff temporal selection
  - **Property 21: Tariff temporal selection**
  - **Validates: Requirements 8.3**
  - Test that any billing date selects correct active tariff
  - Run 100+ iterations with randomly generated billing dates

- [ ]* 26.22 Write property test for tariff precedence with overlaps
  - **Property 22: Tariff precedence with overlaps**
  - **Validates: Requirements 8.4**
  - Test that any date with multiple active tariffs selects most recent
  - Run 100+ iterations with randomly generated overlapping tariffs

- [ ]* 26.23 Write property test for weekend tariff rate application
  - **Property 23: Weekend tariff rate application**
  - **Validates: Requirements 8.5**
  - Test that any weekend consumption uses weekend rates
  - Run 100+ iterations with randomly generated weekend dates

- [ ]* 26.24 Write property test for summer gyvatukas calculation formula
  - **Property 24: Summer gyvatukas calculation formula**
  - **Validates: Requirements 9.1**
  - Test that any summer gyvatukas calculation uses correct formula
  - Run 100+ iterations with randomly generated summer data

- [ ]* 26.25 Write property test for winter gyvatukas norm application
  - **Property 25: Winter gyvatukas norm application**
  - **Validates: Requirements 9.2**
  - Test that any winter gyvatukas uses stored summer average
  - Run 100+ iterations with randomly generated winter data

- [ ]* 26.26 Write property test for circulation cost distribution
  - **Property 26: Circulation cost distribution**
  - **Validates: Requirements 9.4**
  - Test that any circulation cost is distributed correctly
  - Run 100+ iterations with randomly generated buildings and costs

- [ ]* 26.27 Write property test for gyvatukas invoice itemization
  - **Property 27: Gyvatukas invoice itemization**
  - **Validates: Requirements 9.5**
  - Test that any invoice with gyvatukas has separate line item
  - Run 100+ iterations with randomly generated gyvatukas invoices

- [ ] 27. Final checkpoint - Ensure all tests pass




  - Run php artisan test:setup --fresh
  - Run php artisan test to execute all tests
  - Verify all property-based tests pass (100+ iterations each)
  - Verify all unit tests pass
  - Verify all feature tests pass
  - Test manual scenarios from TESTING_GUIDE.md
  - Ensure all tests pass, ask the user if questions arise.
