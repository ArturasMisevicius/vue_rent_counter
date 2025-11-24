

- [ ] 3. Create Eloquent models with relationships and casts
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

- [ ] 4. Implement multi-tenancy with Global Scopes
  - Create TenantScope class that adds WHERE tenant_id = ? to queries
  - Apply TenantScope to Property, Meter, MeterReading, Invoice, Tenant models
  - Create EnsureTenantContext middleware to validate session tenant_id
  - Modify authentication to set tenant_id in session on login
  - _Requirements: 7.1, 7.2, 7.3, 7.5_



- [ ] 5. Create Form Requests for validation
  - Create StoreMeterReadingRequest with monotonicity and temporal validation
  - Create StoreTariffRequest with JSON schema and time-of-use zone validation
  - Create UpdateMeterReadingRequest for corrections
  - Create FinalizeInvoiceRequest
  - _Requirements: 1.2, 1.3, 2.2_

- [ ] 6. Implement TariffResolver service
  - Create TariffResolver class with resolve() method
  - Implement temporal tariff selection logic (active_from, active_until)
  - Implement calculateCost() method for flat and time-of-use tariffs
  - Add determineZone() helper for day/night/weekend logic
  - _Requirements: 2.3, 2.4, 2.5_

- [ ] 7. Implement GyvatukasCalculator service
  - Create GyvatukasCalculator class
  - Implement isHeatingSeason() method (Oct-Apr check)
  - Implement calculateSummerGyvatukas() with Q_circ = Q_total - (V_water × c × ΔT) formula
  - Implement calculateWinterGyvatukas() using stored summer average
  - Implement distributeCirculationCost() for equal or area-based distribution
  - Add calculateSummerAverage() method for Building model
  - _Requirements: 4.1, 4.2, 4.3, 4.5_

- [ ] 8. Implement BillingService for invoice generation
  - Create BillingService class
  - Implement generateInvoice() method that collects meter readings for period
  - Integrate TariffResolver to snapshot current tariff rates
  - Integrate GyvatukasCalculator for heating/hot water
  - Implement water bill calculation with supply, sewage, and fixed fee
  - Create InvoiceItems with snapshotted prices and meter_reading_snapshot
  - Calculate total_amount and set status to 'draft'
  - Implement finalizeInvoice() method that sets finalized_at and makes immutable
  - _Requirements: 3.1, 3.2, 3.3, 5.1, 5.2, 5.5_

- [ ] 9. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 10. Implement audit trail with Eloquent Observers
  - Create MeterReadingObserver class
  - Implement updating() method to create MeterReadingAudit records
  - Store old_value, new_value, change_reason, changed_by_user_id
  - Register observer in AppServiceProvider
  - _Requirements: 8.1, 8.2_

- [ ] 11. Implement draft invoice recalculation on reading correction
  - Add logic to MeterReadingObserver to find affected draft invoices
  - Recalculate invoice totals when readings change
  - Prevent recalculation for finalized invoices
  - _Requirements: 8.3_

- [ ] 12. Create authorization Policies for RBAC
  - Create TariffPolicy with viewAny, create, update, delete methods
  - Create InvoicePolicy with view, create, finalize methods
  - Create MeterReadingPolicy with create, update methods
  - Implement role-based logic (admin: all, manager: invoices, tenant: view only)
  - Register policies in AuthServiceProvider
  - _Requirements: 11.1, 11.2, 11.3, 11.4_

- [ ] 13. Create controllers for meter reading management
  - Create MeterReadingController with store() method
  - Validate input using StoreMeterReadingRequest
  - Store reading with entered_by user ID and timestamp
  - Handle multi-zone readings for electricity meters
  - Create MeterReadingUpdateController for corrections
  - Return JSON response for API endpoints
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5_

- [ ] 14. Create controllers for tariff management
  - Create TariffController with index, store, update, destroy methods
  - Authorize using TariffPolicy
  - Validate tariff configuration JSON
  - Return tariff list for provider selection
  - _Requirements: 2.1, 2.2, 11.2_

- [ ] 15. Create controllers for invoice management
  - Create InvoiceController with index, show, store methods
  - Integrate BillingService for invoice generation
  - Create FinalizeInvoiceController for finalization
  - Filter invoices by tenant_id (automatic via Global Scope)
  - Support property filtering for multi-property tenants
  - _Requirements: 5.1, 5.2, 5.5, 6.1, 6.5_

- [ ] 16. Create Blade components for meter reading form
  - Create x-meter-reading-form component with Alpine.js
  - Implement dynamic provider/tariff selection without page refresh
  - Add real-time validation for reading monotonicity
  - Add client-side charge preview calculation
  - Display previous reading and consumption
  - Include Alpine.js via CDN in layout
  - _Requirements: 10.1, 10.2, 10.3_

- [ ] 17. Create Blade components for invoice display
  - Create x-invoice-summary component
  - Display itemized breakdown by utility type
  - Show consumption amount and rate applied for each item
  - Display chronologically ordered consumption history
  - Add property filter dropdown for multi-property tenants
  - _Requirements: 6.2, 6.3, 6.4, 6.5_

- [ ] 18. Enhance Blade views with Alpine.js interactivity
  - Enhance dashboard views with role-based content (@can directives)
  - Enhance meter readings views with Alpine.js reactive forms
  - Enhance tariffs views with JSON configuration editor (admin only)
  - Enhance invoices views with itemized breakdown display
  - Create consumption history view for tenants
  - Apply TailwindCSS for styling
  - _Requirements: 10.5, 11.5_

- [ ] 19. Implement scheduled task for summer average calculation
  - Create CalculateSummerAverageCommand artisan command
  - Schedule to run at start of heating season (October 1st)
  - Calculate average gyvatukas for May-September for each building
  - Store in building->gyvatukas_summer_average
  - Update building->gyvatukas_last_calculated
  - Register command in routes/console.php schedule
  - _Requirements: 4.4_



- [ ] 21. Create database seeders with realistic Lithuanian data
  - Create ProvidersSeeder (Ignitis, Vilniaus Vandenys, Vilniaus Energija)
  - Create TariffsSeeder with realistic rates and time-of-use configurations
  - Create UsersSeeder with admin, manager, and tenant users
  - Create BuildingsSeeder with Vilnius addresses
  - Create PropertiesSeeder with apartments and houses
  - Create MetersSeeder with Lithuanian serial number format
  - _Requirements: 2.1, 3.1_

- [ ] 22. Create model factories for testing
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



- [ ] 24. Write remaining property-based tests for correctness properties



