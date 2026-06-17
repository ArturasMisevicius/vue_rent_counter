# Storage Module Contract

> **AI agent usage:** Read this before changing disks, file records, downloads, attachments, or retention behavior.

Updated on 2026-06-15.

## Purpose

Storage owns the conventions for private file persistence, metadata, authorized downloads, retention, and future cleanup.

## Invariants

- sensitive tenant, KYC, contract, and payment proof files are private;
- database records carry metadata and ownership;
- downloads go through policies/actions;
- public storage is only for non-sensitive assets.

## Dependencies

Storage supports Documents, KYC, Contracts, Payments, and exports.

## Tests And Scenarios

Use storage fakes plus URL bypass tests for tenant and cross-organization denial.
