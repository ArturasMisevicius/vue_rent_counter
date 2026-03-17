# Delta for Domain Seed Fixtures

## ADDED Requirements

### Requirement: Logical Large-Volume Fixtures

The system SHALL provide seed packs that create at least 1,000 total records
across the available domain using logically valid relationships.

#### Scenario: Seed suite reaches the minimum volume

- GIVEN the large-volume seed suite is executed
- WHEN seeding completes successfully
- THEN the resulting dataset contains at least 1,000 total records
- AND the records are distributed across related domain models rather than a
  single disconnected table

### Requirement: Realistic Geography In Fixtures

The system SHALL use valid Baltic country-city combinations for seeded records
that carry geography.

#### Scenario: Geography-bearing fixture uses valid Baltic location

- GIVEN a seeded record includes country or city data
- WHEN the location is inspected
- THEN the country is Lithuania, Latvia, or Estonia
- AND the city belongs to that country in the reference dataset
