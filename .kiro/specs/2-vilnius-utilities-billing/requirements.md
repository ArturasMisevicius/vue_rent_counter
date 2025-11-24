# Requirements Document

## Introduction

Система управления коммунальными услугами для рынка аренды недвижимости в Вильнюсе (Литва) представляет собой монолитное веб-приложение на базе Laravel 12 и SQLite. Система предназначена для автоматизации расчета и учета коммунальных платежей с учетом специфики литовского рынка, включая сложные алгоритмы расчета платы за циркуляцию горячей воды ("gyvatukas"), многотарифные планы электроэнергии и регулируемые тарифы водоснабжения.

## Glossary

- **System**: Система управления коммунальными услугами Вильнюса
- **Tenant**: Арендатор квартиры или помещения
- **Property**: Объект недвижимости (квартира, дом)
- **Meter**: Счетчик коммунальных услуг (электричество, вода, отопление)
- **Invoice**: Счет за коммунальные услуги
- **Tariff**: Тарифный план поставщика услуг
- **Provider**: Поставщик коммунальных услуг (Ignitis, Vilniaus Vandenys, Vilniaus Energija)
- **Gyvatukas**: Плата за циркуляцию горячей воды (полотенцесушитель)
- **WAL Mode**: Write-Ahead Logging режим SQLite для конкурентного доступа
- **Manager**: Менеджер управляющей компании
- **Admin**: Администратор системы

## Requirements

### Requirement 1

**User Story:** As a Manager, I want to register meter readings for tenants, so that I can calculate accurate utility bills based on actual consumption.

#### Acceptance Criteria

1. WHEN a Manager submits a meter reading with valid data THEN the System SHALL store the reading with timestamp and link it to the specific Meter and Tenant
2. WHEN a Manager attempts to submit a reading lower than the previous reading THEN the System SHALL reject the submission and display a validation error
3. WHEN a Manager submits a reading THEN the System SHALL validate that the reading date is not in the future
4. WHEN a reading is successfully stored THEN the System SHALL maintain an audit trail of who entered the reading and when
5. WHERE a Meter supports multiple tariff zones (day/night electricity) THEN the System SHALL accept separate readings for each zone

### Requirement 2

**User Story:** As an Admin, I want to configure dynamic tariff plans with time-based pricing, so that the system can accurately calculate costs for providers like Ignitis with day/night rates.

#### Acceptance Criteria

1. WHEN an Admin creates a Tariff THEN the System SHALL store the configuration as JSON structure with flexible zone definitions
2. WHEN a Tariff includes time-of-use zones THEN the System SHALL validate that time ranges do not overlap and cover all 24 hours
3. WHEN an Admin sets an active_from date for a Tariff THEN the System SHALL apply this Tariff only to calculations after that date
4. WHEN multiple Tariffs exist for the same Provider THEN the System SHALL select the correct Tariff based on the billing period date
5. WHERE a Tariff includes weekend logic THEN the System SHALL store and apply special rates for Saturday and Sunday

### Requirement 3

**User Story:** As a Manager, I want the system to automatically calculate water bills using Vilniaus Vandenys tariffs, so that invoices include supply, sewage, and fixed meter fees.

#### Acceptance Criteria

1. WHEN calculating a water bill THEN the System SHALL apply separate rates for water supply (€0.97/m³) and sewage (€1.23/m³)
2. WHEN generating a monthly Invoice THEN the System SHALL add the fixed meter subscription fee (€0.85/month)
3. WHEN a Property is classified as a private house THEN the System SHALL apply house-specific tariffs instead of apartment tariffs
4. WHEN water consumption is zero for a billing period THEN the System SHALL still charge the fixed subscription fee
5. WHEN tariff rates change THEN the System SHALL apply the correct historical rate to past invoices without recalculation

### Requirement 4

**User Story:** As a Manager, I want the system to calculate "gyvatukas" (circulation fee) differently for summer and winter, so that heating season billing follows Lithuanian regulations.

#### Acceptance Criteria

1. WHEN the billing period is in non-heating season (May-September) THEN the System SHALL calculate circulation energy as total building energy minus hot water heating energy
2. WHEN the billing period is in heating season (October-April) THEN the System SHALL use the average summer circulation value as a fixed norm
3. WHEN calculating summer circulation THEN the System SHALL use the formula: Q_circ = Q_total - (V_water × c × ΔT)
4. WHEN the heating season begins THEN the System SHALL automatically compute and store the summer average for each Property
5. WHEN distributing circulation costs THEN the System SHALL divide the total circulation energy equally among all apartments or proportionally by area

### Requirement 5

**User Story:** As a Manager, I want to generate monthly invoices that snapshot all current prices and readings, so that invoices remain accurate even if tariffs change later.

#### Acceptance Criteria

1. WHEN an Invoice is generated THEN the System SHALL copy all current Tariff rates into invoice_items table
2. WHEN an Invoice is created THEN the System SHALL snapshot all Meter readings used in calculations
3. WHEN a Tariff rate changes after Invoice generation THEN the System SHALL not recalculate the existing Invoice
4. WHEN displaying an Invoice THEN the System SHALL show the snapshotted prices, not current Tariff table values
5. WHEN an Invoice is finalized THEN the System SHALL mark it as immutable and prevent modifications

### Requirement 6

**User Story:** As a Tenant, I want to view my utility bills and consumption history, so that I can verify charges and track my usage patterns.

#### Acceptance Criteria

1. WHEN a Tenant logs in THEN the System SHALL display only Invoices and Meters associated with that Tenant
2. WHEN a Tenant views an Invoice THEN the System SHALL show itemized breakdown by utility type (electricity, water, heating, gyvatukas)
3. WHEN a Tenant requests consumption history THEN the System SHALL display a chronological list of Meter readings with dates
4. WHEN displaying costs THEN the System SHALL show both the consumption amount and the rate applied
5. WHEN a Tenant has multiple Properties THEN the System SHALL allow filtering by Property

### Requirement 7

**User Story:** As an Admin, I want to manage multi-tenancy with data isolation, so that different property management companies cannot access each other's data.

#### Acceptance Criteria

1. WHEN a user authenticates THEN the System SHALL establish a tenant_id in the session
2. WHEN any database query executes THEN the System SHALL automatically filter results by the session tenant_id
3. WHEN a Manager attempts to access data from another tenant THEN the System SHALL return empty results or access denied
4. WHEN an Admin creates a new tenant account THEN the System SHALL initialize isolated data structures for that tenant
5. WHERE a Global Scope is applied to a Model THEN the System SHALL enforce tenant_id filtering on all read, update, and delete operations

### Requirement 8

**User Story:** As a Manager, I want to correct erroneous meter readings with full audit trail, so that mistakes can be fixed while maintaining transparency.

#### Acceptance Criteria

1. WHEN a Manager modifies a Meter reading THEN the System SHALL create an audit record in meter_reading_audit table
2. WHEN an audit record is created THEN the System SHALL store original value, new value, reason for change, and user who made the change
3. WHEN a reading is corrected THEN the System SHALL recalculate affected Invoices if they are not yet finalized
4. WHEN displaying reading history THEN the System SHALL show all modifications with timestamps and reasons
5. WHEN a finalized Invoice is affected by a correction THEN the System SHALL require Admin approval to regenerate

### Requirement 9

**User Story:** As a system operator, I want the database to operate in WAL mode with foreign key enforcement, so that concurrent users can read while writes occur and data integrity is maintained.

#### Acceptance Criteria

1. WHEN the System initializes the database connection THEN the System SHALL enable Write-Ahead Logging (WAL) mode
2. WHEN the System establishes a database connection THEN the System SHALL set DB_FOREIGN_KEYS=true to enforce referential integrity
3. WHEN multiple users read data simultaneously with an active write operation THEN the System SHALL allow concurrent access without blocking
4. WHEN a delete operation violates a foreign key constraint THEN the System SHALL reject the operation and return an error
5. WHEN cascading deletes are defined in migrations THEN the System SHALL execute them automatically when parent records are deleted

### Requirement 10

**User Story:** As a Manager, I want to use a reactive web interface without page reloads for meter entry, so that I can quickly input readings for multiple apartments.

#### Acceptance Criteria

1. WHEN a Manager selects a Provider in a form THEN the System SHALL dynamically load available Tariff plans without page refresh
2. WHEN a Manager enters a meter reading THEN the System SHALL validate the input in real-time and show immediate feedback
3. WHEN calculating a preview of charges THEN the System SHALL compute the amount client-side using Alpine.js before submission
4. WHEN form validation fails THEN the System SHALL highlight errors inline without requiring form resubmission
5. WHERE Alpine.js is used for interactivity THEN the System SHALL not require any build step or compilation

### Requirement 11

**User Story:** As an Admin, I want role-based access control with three levels (Admin, Manager, Tenant), so that users can only perform actions appropriate to their role.

#### Acceptance Criteria

1. WHEN a user attempts to access a protected resource THEN the System SHALL verify the user's role using Laravel Gates or Policies
2. WHEN an Admin accesses tariff configuration THEN the System SHALL allow full CRUD operations
3. WHEN a Manager accesses invoice generation THEN the System SHALL allow creation and viewing but not tariff modification
4. WHEN a Tenant accesses the system THEN the System SHALL restrict access to viewing their own Invoices and Meters only
5. WHERE Blade templates render actions THEN the System SHALL use @can directives to conditionally display UI elements

### Requirement 12

**User Story:** As a system operator, I want automated backup of the SQLite database file, so that data can be recovered in case of corruption or accidental deletion.

#### Acceptance Criteria

1. WHEN a scheduled backup task runs THEN the System SHALL use SQLite .backup command to create a consistent snapshot
2. WHEN creating a backup THEN the System SHALL handle active WAL journals correctly to ensure data integrity
3. WHEN a backup completes THEN the System SHALL store the backup file with a timestamp in the filename
4. WHEN backup storage exceeds retention policy THEN the System SHALL automatically delete backups older than the configured period
5. WHERE the spatie/laravel-backup package is used THEN the System SHALL configure it to work with SQLite file-based backups
