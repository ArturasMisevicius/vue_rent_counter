# Delta for Tenant Invoice History

## ADDED Requirements

### Requirement: Tenant Invoice History

The system SHALL provide tenants with a card-based invoice history page that
supports quick status filtering.

#### Scenario: Tenant browses invoice filters

- GIVEN an authenticated tenant with invoice history
- WHEN the tenant opens the invoice history page
- THEN the page shows `My Invoices`
- AND the page includes `All`, `Unpaid`, and `Paid` status filters

#### Scenario: Tenant filters unpaid invoices

- GIVEN an authenticated tenant with a mix of paid and unpaid invoices
- WHEN the tenant applies the unpaid filter
- THEN the page shows only unpaid invoices

#### Scenario: Tenant has no unpaid invoices

- GIVEN an authenticated tenant whose invoices are all paid
- WHEN the tenant opens the invoice history page
- THEN the page shows an all-paid-up empty state for unpaid filtering

#### Scenario: Tenant has an overdue invoice

- GIVEN an authenticated tenant with an overdue invoice
- WHEN the tenant opens the invoice history page
- THEN the overdue invoice is visually marked as overdue

### Requirement: Tenant Invoice Download Authorization

The system SHALL allow tenants to download only invoice documents that belong
to their own tenant-scoped invoice history.

#### Scenario: Tenant downloads an authorized invoice PDF

- GIVEN an authenticated tenant viewing an invoice that belongs to the tenant
- WHEN the tenant requests the invoice PDF download
- THEN the system returns the invoice document response
- AND the document uses the shared invoice PDF path for the domain
