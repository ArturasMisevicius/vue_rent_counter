# Requirements Document

## Introduction

Система тестирования аутентификации и авторизации для Vilnius Utilities Billing System предназначена для создания комплексного набора тестовых пользователей и сценариев, которые позволят проверить корректность работы системы на всех уровнях доступа (Admin, Manager, Tenant). Система должна обеспечить возможность быстрого создания тестовых данных, проверки прав доступа и валидации бизнес-логики для каждой роли пользователя.

## Glossary

- **System**: Система управления коммунальными услугами Вильнюса
- **Admin**: Администратор системы с полным доступом ко всем функциям
- **Manager**: Менеджер управляющей компании с правами на ввод показаний и генерацию счетов
- **Tenant**: Арендатор с правами только на просмотр своих счетов и показаний
- **Test Seeder**: Сидер для создания тестовых данных
- **Authentication**: Процесс проверки подлинности пользователя
- **Authorization**: Процесс проверки прав доступа пользователя к ресурсам
- **Test User**: Тестовый пользователь с предопределенными учетными данными
- **Test Scenario**: Набор действий для проверки определенной функциональности
- **Access Control**: Контроль доступа на основе ролей (RBAC)

## Requirements

### Requirement 1

**User Story:** As a developer, I want to create test users for each role with known credentials, so that I can manually test the system at different access levels.

#### Acceptance Criteria

1. WHEN the test seeder runs THEN the System SHALL create at least one Admin user with email "admin@test.com" and password "password"
2. WHEN the test seeder runs THEN the System SHALL create at least one Manager user with email "manager@test.com" and password "password"
3. WHEN the test seeder runs THEN the System SHALL create at least one Tenant user with email "tenant@test.com" and password "password"
4. WHEN test users are created THEN the System SHALL assign them to appropriate tenant_id values for multi-tenancy testing
5. WHEN test users are created THEN the System SHALL output their credentials to the console for easy reference

### Requirement 2

**User Story:** As a developer, I want to test login functionality for each role, so that I can verify authentication works correctly.

#### Acceptance Criteria

1. WHEN a user submits valid credentials THEN the System SHALL authenticate the user and establish a session
2. WHEN a user submits invalid credentials THEN the System SHALL reject authentication and display an error message
3. WHEN an Admin logs in THEN the System SHALL redirect to "/admin/dashboard"
4. WHEN a Manager logs in THEN the System SHALL redirect to "/manager/dashboard"
5. WHEN a Tenant logs in THEN the System SHALL redirect to "/tenant/dashboard"

### Requirement 3

**User Story:** As a developer, I want to test role-based access control, so that I can verify users can only access resources appropriate to their role.

#### Acceptance Criteria

1. WHEN an Admin accesses tariff management pages THEN the System SHALL allow full access
2. WHEN a Manager accesses tariff management pages THEN the System SHALL deny access with 403 error
3. WHEN a Tenant accesses tariff management pages THEN the System SHALL deny access with 403 error
4. WHEN a Manager accesses meter reading entry pages THEN the System SHALL allow access
5. WHEN a Tenant accesses meter reading entry pages THEN the System SHALL deny access with 403 error

### Requirement 4

**User Story:** As a developer, I want to test multi-tenancy data isolation, so that I can verify users cannot access data from other tenants.

#### Acceptance Criteria

1. WHEN a Manager from tenant A views properties THEN the System SHALL display only properties belonging to tenant A
2. WHEN a Manager from tenant A attempts to access a property from tenant B THEN the System SHALL return 404 error
3. WHEN a Tenant views invoices THEN the System SHALL display only invoices associated with that Tenant
4. WHEN a Tenant attempts to access another Tenant's invoice THEN the System SHALL return 404 error
5. WHEN an Admin views data THEN the System SHALL display data from all tenants without filtering

### Requirement 5

**User Story:** As a developer, I want comprehensive test data for each tenant, so that I can test complete workflows from meter reading to invoice generation.

#### Acceptance Criteria

1. WHEN the test seeder runs THEN the System SHALL create at least 2 buildings with multiple properties each
2. WHEN the test seeder runs THEN the System SHALL create meters for each property (electricity, water, heating)
3. WHEN the test seeder runs THEN the System SHALL create historical meter readings spanning at least 3 months
4. WHEN the test seeder runs THEN the System SHALL create tariffs for all three providers (Ignitis, Vilniaus Vandenys, Vilniaus Energija)
5. WHEN the test seeder runs THEN the System SHALL create sample invoices in different states (draft, finalized, paid)

### Requirement 6

**User Story:** As a developer, I want to test meter reading entry workflow, so that I can verify validation and storage work correctly.

#### Acceptance Criteria

1. WHEN a Manager enters a valid meter reading THEN the System SHALL store it with timestamp and user reference
2. WHEN a Manager enters a reading lower than the previous reading THEN the System SHALL reject it with validation error
3. WHEN a Manager enters a reading with future date THEN the System SHALL reject it with validation error
4. WHEN a Manager enters a reading for a multi-zone meter THEN the System SHALL accept separate values for each zone
5. WHEN a reading is stored THEN the System SHALL create an audit trail entry

### Requirement 7

**User Story:** As a developer, I want to test invoice generation workflow, so that I can verify billing calculations are correct.

#### Acceptance Criteria

1. WHEN a Manager generates an invoice for a tenant THEN the System SHALL calculate costs based on meter readings and current tariffs
2. WHEN an invoice is generated THEN the System SHALL create invoice items for each utility type with consumption
3. WHEN an invoice is generated THEN the System SHALL snapshot current tariff rates in invoice_items
4. WHEN a Manager finalizes an invoice THEN the System SHALL mark it as immutable
5. WHEN a finalized invoice exists and tariffs change THEN the System SHALL not recalculate the invoice

### Requirement 8

**User Story:** As a developer, I want to test tariff management workflow, so that I can verify tariff configuration and selection work correctly.

#### Acceptance Criteria

1. WHEN an Admin creates a tariff with time-of-use zones THEN the System SHALL validate that zones do not overlap
2. WHEN an Admin creates a tariff with time-of-use zones THEN the System SHALL validate that zones cover all 24 hours
3. WHEN the System selects a tariff for billing THEN the System SHALL choose the tariff active on the billing date
4. WHEN multiple tariffs are active for a date THEN the System SHALL select the most recent one
5. WHEN a tariff includes weekend logic THEN the System SHALL apply special rates for Saturday and Sunday

### Requirement 9

**User Story:** As a developer, I want to test gyvatukas calculation, so that I can verify seasonal circulation fee logic is correct.

#### Acceptance Criteria

1. WHEN calculating gyvatukas for summer months (May-September) THEN the System SHALL use the formula Q_circ = Q_total - (V_water × c × ΔT)
2. WHEN calculating gyvatukas for winter months (October-April) THEN the System SHALL use the stored summer average
3. WHEN the heating season begins THEN the System SHALL calculate and store the summer average for each building
4. WHEN distributing circulation costs THEN the System SHALL divide equally or proportionally by area
5. WHEN gyvatukas is calculated THEN the System SHALL include it as a separate line item in the invoice

### Requirement 10

**User Story:** As a developer, I want automated tests for authentication and authorization, so that I can verify security controls work correctly.

#### Acceptance Criteria

1. WHEN running authentication tests THEN the System SHALL verify login succeeds with valid credentials for each role
2. WHEN running authentication tests THEN the System SHALL verify login fails with invalid credentials
3. WHEN running authorization tests THEN the System SHALL verify each role can access only permitted resources
4. WHEN running authorization tests THEN the System SHALL verify cross-tenant access is blocked
5. WHEN running authorization tests THEN the System SHALL verify session management works correctly

### Requirement 11

**User Story:** As a developer, I want a test command to quickly reset and populate test data, so that I can start testing from a clean state.

#### Acceptance Criteria

1. WHEN the test command runs THEN the System SHALL drop all existing data
2. WHEN the test command runs THEN the System SHALL run all migrations
3. WHEN the test command runs THEN the System SHALL seed test users with known credentials
4. WHEN the test command runs THEN the System SHALL seed complete test data (buildings, properties, meters, readings, tariffs)
5. WHEN the test command completes THEN the System SHALL display a summary of created test users and their credentials

### Requirement 12

**User Story:** As a developer, I want test scenarios documented, so that I can systematically verify all functionality.

#### Acceptance Criteria

1. WHEN documentation is created THEN the System SHALL include step-by-step test scenarios for each role
2. WHEN documentation is created THEN the System SHALL include expected results for each test step
3. WHEN documentation is created THEN the System SHALL include test data references (user emails, property IDs)
4. WHEN documentation is created THEN the System SHALL include common issues and troubleshooting steps
5. WHEN documentation is created THEN the System SHALL include API endpoint testing examples with curl commands
