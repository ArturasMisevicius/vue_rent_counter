# Accounting Module Contract

> **AI agent usage:** Read this before adding ledger, owner payout, vendor invoice, credit/debit, or accounting export behavior.

Updated on 2026-06-15.

## Purpose

Accounting owns future ledger, journal, owner statement, vendor invoice, credit, debit, and payout rules. The module is documented now so financial mutations do not spread into UI callbacks while the feature grows.

## Current State

Full accounting workflows are not yet generalized in the live checkout. Billing and payments already carry financial state and must be treated as upstream sources.

## Invariants

- accounting entries must be balanced;
- reversals must be explicit and audited;
- ledger posting must not happen directly from Filament resources;
- payment and invoice state should flow through billing/payment actions.

## Dependencies

Accounting may depend on Billing, Payments, Vendors, Owners, Documents, and Events/Outbox.

## Tests And Scenarios

Future implementation must add unit tests for calculations and feature tests for posting/reversal workflows.
