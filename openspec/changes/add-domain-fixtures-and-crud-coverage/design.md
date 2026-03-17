# Design: Domain Fixtures And CRUD Coverage

## Context

Once the legacy import and Baltic reference-data slices land, the repository
needs a realistic seeded environment and broad regression coverage. The user
specifically requested at least 1,000 random-but-logical records, valid
country/city pairings, and tests for all existing CRUD surfaces.

## Goals

- Seed at least 1,000 total records across the available domain
- Keep all generated records relationship-safe and logically coherent
- Reuse Baltic multilingual geography instead of generic faker output
- Add CRUD coverage only for surfaces that actually exist in the repository

## Non-Goals

- Generating meaningless disconnected bulk rows
- Adding tests for theoretical resources that are not implemented yet
- Expanding beyond the supported language and Baltic geography constraints

## Fixture Strategy

The seed volume should be distributed by domain responsibility rather than by
raw count targets per model. Example clusters include:

- organizations and settings
- buildings and properties
- users and assignments
- meters/readings/invoices
- notifications/logs/reference catalogs

The resulting graph should be usable for manual exploration, not just count
assertions.

## CRUD Coverage Strategy

Coverage should include:

- Filament resources that exist
- controller-driven CRUD pages
- Livewire create/update flows
- authorization, tenant isolation, and filter/download regressions

## Testing Strategy

- verify large seed packs complete and create the target minimum volume
- verify city-country realism for generated geography-bearing records
- verify each CRUD surface can list/view/create/update/delete as appropriate
- verify unauthorized or cross-tenant access is denied
