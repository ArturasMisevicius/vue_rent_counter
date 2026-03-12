## ADDED Requirements
### Requirement: Billing Calculation Pipeline
The system SHALL generate invoice items from service configurations, meters, readings, tariffs, and utility services using the canonical billing engine with time-of-use support.

#### Scenario: Time-of-use invoice items
- **WHEN** a time-of-use service has zone consumption for a billing period
- **THEN** invoice items are generated per zone with zone rates and snapshots

#### Scenario: Flat-rate invoice item
- **WHEN** a flat-rate service is configured for a billing period
- **THEN** a single fixed invoice item is generated with the configured rate

### Requirement: Invoice Generation Idempotency and Audit
The system SHALL reuse existing draft invoices for the same tenant and period and SHALL record an invoice generation audit entry for each run.

#### Scenario: Draft invoice reuse
- **WHEN** invoice generation runs for a tenant and period with an existing draft
- **THEN** the draft invoice is reused and marked as reused in the audit metadata

### Requirement: Meter Reading Validation for Billing
The system SHALL validate meter readings for monotonicity and structural integrity before using them in billing calculations.

#### Scenario: Non-monotonic reading rejected
- **WHEN** a new reading is lower than the previous reading for the same meter
- **THEN** the submission is rejected with a validation error
