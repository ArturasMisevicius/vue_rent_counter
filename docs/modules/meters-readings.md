# Meters And Readings Module Contract

> **AI agent usage:** Read this before changing meters, meter readings, reading review, corrections, reading reminders, or reading-driven billing.

Updated on 2026-06-15.

## Purpose

Meters/readings owns meter setup, tenant/admin reading submission, reading validation, approval, rejection, correction, voiding, and reading version/audit history.

## Owns

- Models: `Meter`, `MeterReading`, `MeterReadingAudit`, `MeterReadingVersion`.
- Actions: meter CRUD actions, tenant reading submission, `ApproveMeterReading`, `RejectMeterReading`, `CorrectMeterReading`, `VoidMeterReading`.
- Policies: `MeterPolicy`, `MeterReadingPolicy`.

## Invariants

- readings are scoped to organization, property, meter, tenant, invoice, and billing period where applicable;
- finalized/approved billing inputs cannot be changed without correction/version history;
- correction requires a reason;
- negative consumption needs explicit confirmation.

## Tests And Scenarios

Primary tests are billing review tests, meter reading resource tests, tenant reading workflow tests, and architecture wiring tests.
