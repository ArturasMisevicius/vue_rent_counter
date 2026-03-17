# Delta for Supported Languages

## ADDED Requirements

### Requirement: Supported Language Set

The system SHALL support only `en`, `lt`, and `ru` as seeded platform
languages for this slice.

#### Scenario: Spanish is no longer a supported seeded language

- GIVEN the platform language seeders have run
- WHEN the supported language list is queried
- THEN the list contains `en`, `lt`, and `ru`
- AND the list does not contain `es`

### Requirement: English Fallback Contract

The system SHALL preserve English as the fallback locale when a localized value
is missing in Lithuanian or Russian.

#### Scenario: Missing localized reference name falls back to English

- GIVEN a localized reference value is unavailable in the selected locale
- WHEN the system resolves the display name
- THEN the system returns the English fallback value
