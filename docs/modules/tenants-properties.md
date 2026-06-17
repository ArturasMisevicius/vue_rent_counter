# Tenants And Properties Module Contract

> **AI agent usage:** Read this before changing tenants, properties, assignments, move-out, occupancy, manager access, or tenant portal scope.

Updated on 2026-06-15.

## Purpose

This module owns organization properties, tenant profiles, assignments, occupancy, tenant portal access, and move-out lifecycle coordination.

## Owns

- Models: `Property`, `PropertyAssignment`, `User` tenant rows, `MoveOutProcess`.
- Actions: tenant creation/update, assignment, invitation, portal access, tenant move-out actions.
- Policies: `PropertyPolicy`, `PropertyAssignmentPolicy`, `UserPolicy`.

## Invariants

- tenant and property records are organization-scoped;
- active assignments must not be duplicated for the same active tenant/property lifecycle;
- move-out is an explicit lifecycle, not a silent unassign;
- tenant portal access changes must not delete financial/document history.

## Tests And Scenarios

Primary tests include tenant resource tests, tenant onboarding, tenant portal isolation, move-out lifecycle tests, and property boundary tests.
