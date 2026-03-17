# Delta for Legacy Domain Foundation

## ADDED Requirements

### Requirement: Additive Legacy Domain Import

The system SHALL import missing domain structure from `_old` into the current
project as additive changes only, without removing or renaming existing current
project fields.

#### Scenario: Existing fields remain intact during import

- GIVEN a current model or table already exists in the repository
- WHEN missing legacy fields are imported from `_old`
- THEN the import adds only the missing structure
- AND the existing fields remain available under their current names

### Requirement: Current Model Precedence

The system SHALL extend an existing current model when `_old` contains the same
domain concept, instead of creating a duplicate replacement model.

#### Scenario: Overlapping models are merged, not duplicated

- GIVEN `_old` and the current project both contain a model for the same domain concept
- WHEN the import foundation is applied
- THEN the current model remains the canonical model in the project
- AND the missing legacy fields and relations are merged into that model

### Requirement: Import Classification Ledger

The system SHALL classify every top-level `_old` model as imported, merged, or
deferred before later seed and CRUD waves proceed.

#### Scenario: Deferred legacy concepts are explicit

- GIVEN a legacy model depends on workflows that are not yet present in the current project
- WHEN the import inventory is completed
- THEN that legacy model is marked as deferred with rationale
- AND later implementation work does not silently omit it
