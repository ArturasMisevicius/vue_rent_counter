# Delta for Tenant Home Dashboard

## ADDED Requirements

### Requirement: Tenant Home Summary

The system SHALL show tenants a home dashboard with their current balance,
current-month usage summary, recent readings, a reading-submission entry point,
and a link to the assigned property view.

#### Scenario: Tenant has unpaid invoices and recent readings

- GIVEN an authenticated tenant with assigned meters, recent readings, and one
  or more unpaid invoices
- WHEN the tenant opens the home dashboard
- THEN the page shows the tenant greeting
- AND the page shows an outstanding balance summary
- AND the page shows current-month usage or reading status
- AND the page shows recent reading activity
- AND the page includes links to submit a new reading and open `My Property`

#### Scenario: Tenant is paid up and missing a current-month reading

- GIVEN an authenticated tenant with no unpaid invoices and no current-month
  reading for at least one assigned meter
- WHEN the tenant opens the home dashboard
- THEN the page shows an all-paid-up state instead of outstanding-balance debt
- AND the page shows that no reading has been recorded for the current month

### Requirement: Tenant Payment Instructions

The system SHALL derive tenant-facing payment guidance from organization
billing/contact settings instead of initiating an online payment checkout flow.

#### Scenario: Tenant requests payment guidance

- GIVEN an authenticated tenant whose organization has billing or payment
  instructions configured
- WHEN the tenant views the home dashboard
- THEN the page shows payment guidance derived from those organization settings
- AND the tenant is not sent into an online payment gateway flow in this slice
