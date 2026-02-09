## ADDED Requirements

### Requirement: Comprehensive Tenant Dataset SHALL Be Seeded for Tenant 1
The system SHALL provide one comprehensive seeded dataset centered on the canonical existing tenant (`tenant_id = 1`) that is sufficient to operate and validate full project functionality.

#### Scenario: Core tenant dataset is created
- **GIVEN** the comprehensive seeder is executed
- **WHEN** seeding completes successfully
- **THEN** tenant `1` SHALL have organization, role users, subscriptions, buildings, properties, meters, readings, and invoices
- **AND** those records SHALL be logically connected through valid relationships

### Requirement: Auxiliary Project Models SHALL Be Included in Seeding
The system SHALL seed auxiliary/supporting models in addition to core business models so the full project surface has usable data.

#### Scenario: Auxiliary graph is present for tenant workflows
- **GIVEN** the comprehensive seeder is executed
- **WHEN** data inspection is performed
- **THEN** auxiliary models used by audit/activity/system/security/notifications/tasks/comments/tags/translations flows SHALL contain seeded data
- **AND** tenant-scoped auxiliary records SHALL be aligned with tenant `1` where applicable

### Requirement: Seeding SHALL Be Factory-Driven With Full Model Coverage
The system SHALL use factories as the primary seeding mechanism and SHALL add missing factories needed for full model coverage in the comprehensive seeding flow.

#### Scenario: Models without previous factory support can be seeded
- **GIVEN** a model needed by the comprehensive dataset previously lacked a factory
- **WHEN** the change is implemented
- **THEN** a factory SHALL exist for that model
- **AND** the comprehensive seeder SHALL create records through factories instead of ad-hoc inserts where feasible

### Requirement: Seeded Data SHALL Be Realistic Synthetic Data
The system SHALL generate realistic, domain-appropriate synthetic values for seeded records and SHALL not require real customer data.

#### Scenario: Seed values resemble real-world domain usage
- **GIVEN** the comprehensive seeder is executed
- **WHEN** seeded entities are reviewed
- **THEN** names, addresses, utility readings, billing values, and timestamps SHALL follow plausible real-world patterns
- **AND** generated records SHALL remain synthetic/non-production data

### Requirement: Comprehensive Seeding SHALL Be Deterministic and Re-runnable
The system SHALL support safe repeated execution of the comprehensive seeding flow without producing inconsistent tenant graphs.

#### Scenario: Re-running seeding preserves coherent dataset
- **GIVEN** the comprehensive seeder has already been executed
- **WHEN** it is executed again
- **THEN** the process SHALL complete without relationship corruption
- **AND** key seeded entities for tenant `1` SHALL remain valid and usable

### Requirement: DatabaseSeeder SHALL Expose Comprehensive Seeding Entry
The system SHALL expose the comprehensive tenant seeding flow through the main seeding entrypoint.

#### Scenario: Default seeding includes comprehensive tenant data
- **GIVEN** the default application seeding command is run
- **WHEN** `DatabaseSeeder` executes
- **THEN** the comprehensive tenant seeder SHALL be invoked
- **AND** developers SHALL receive the full tenant dataset by default
