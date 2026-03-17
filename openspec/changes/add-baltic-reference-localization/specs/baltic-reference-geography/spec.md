# Delta for Baltic Reference Geography

## ADDED Requirements

### Requirement: Baltic-Only Country Coverage

The system SHALL provide reference geography only for Lithuania, Latvia, and
Estonia in this slice.

#### Scenario: Country reference list is limited to Baltic countries

- GIVEN the reference geography seed/import has run
- WHEN the country list is queried
- THEN the list contains Lithuania, Latvia, and Estonia
- AND it does not contain non-Baltic countries

### Requirement: Valid Baltic City Mapping

The system SHALL store only valid city-country combinations for the seeded
Baltic geography.

#### Scenario: Seeded city belongs to its assigned country

- GIVEN a seeded Baltic city record
- WHEN its country association is resolved
- THEN the city belongs to Lithuania, Latvia, or Estonia
- AND the country-city pairing is valid for the seeded reference dataset

### Requirement: Multilingual Country And City Names

The system SHALL expose localized country and city names in English,
Lithuanian, and Russian.

#### Scenario: Localized names exist for supported locales

- GIVEN a seeded Baltic country or city record
- WHEN the display name is requested in `en`, `lt`, or `ru`
- THEN the system returns a localized name for that locale or the English fallback
